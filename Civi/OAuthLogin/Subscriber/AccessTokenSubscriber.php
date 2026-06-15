<?php

namespace Civi\OAuthLogin\Subscriber;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;
use Civi\OAuth\OAuthLeagueFacade;

/**
 * Checks on every request whether the OAuth access token
 * is expired and if so refresh it with a refresh token
 */
class AccessTokenSubscriber extends AutoSubscriber {

  private $syncFields = ['access_token', 'refresh_token', 'expires', 'token_type'];

  public static function getSubscribedEvents(): array {
    return ['civi.invoke.auth' => [['onInvokeAuth', 110]]];
  }

  /**
   * Event listener for civi.invoke.auth
   * 
   * Checks whether the user is logged.
   * Checks all access tokens in the session
   * and refreshes them when they are expired.
   * 
   * This event runs on every page request. 
   */
  public function onInvokeAuth(GenericHookEvent $e) {
    $userSystem = \CRM_Core_Config::singleton()->userSystem;
    if (!$userSystem->isUserLoggedIn()) {
      return;
    }
    $session = \CRM_Core_Session::singleton();
    $allTokens = $session->get('OAuthSessionTokens');
    $OAuthSessionTokenCount = $session->get('OAuthSessionTokenCount');
    if (!is_array($allTokens) || empty($allTokens)) {
      return;
    }
    /** @var OAuthLeagueFacade $oauth **/
    $oauth = \Civi::service('oauth2.league');
    foreach($allTokens as $index => $tokenRecord) {
      $provider = $oauth->createProvider(['id' => $tokenRecord['client_id']]);
      $token = new \League\OAuth2\Client\Token\AccessToken($tokenRecord['raw']);
      if ($token->hasExpired()) {
        try {
          $newToken = $provider->getAccessToken('refresh_token', ['refresh_token' => $tokenRecord['refresh_token']]);
          $raw = $newToken->jsonSerialize();
          $allTokens[$index]['raw'] = $raw;
          foreach ($this->syncFields as $field) {
            if (isset($raw[$field])) {
              $allTokens[$index][$field] = $raw[$field];
            }
          }
          \CRM_OAuth_Hook::oauthToken('refresh', 'OAuthSessionToken', $allTokens[$index]['raw']);
        } catch (\Exception $e) {
          // Removed the token from allTokens.
          unset($allTokens[$index]);
          $OAuthSessionTokenCount--;
        }
      }
    }
    $session->set('OAuthSessionTokens', $allTokens);
    $session->set('OAuthSessionTokenCount', $OAuthSessionTokenCount);
  }
  
}