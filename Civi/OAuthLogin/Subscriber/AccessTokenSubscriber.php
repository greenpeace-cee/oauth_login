<?php

namespace Civi\OAuthLogin\Subscriber;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

/**
 * Checks on every request whether the OAuth access token
 * is expired and if so refresh it with a refresh token
 */
class AccessTokenSubscriber extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return ['civi.invoke.auth' => 'onInvokeAuth'];
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
    $doLogout = FALSE;
    $session = \CRM_Core_Session::singleton();
    if ($session->get('oauth_login_is_oauth_session')) {
      try {
        $tokens = civicrm_api4('OAuthSessionToken', 'refresh', [
          'checkPermissions' => FALSE,
          'where' => [
            ['tag', '=', 'Login'],
          ],
        ]);
        $doLogout = $tokens->count() > 0 ? FALSE : TRUE;
      } catch (\Throwable $e) {
        $doLogout = true;
      }
    }
    if ($doLogout) {
      /** @var \Civi\OAuthLogin\Service $service */
      $service = \Civi::service('civi.oauthlogin');
      $service->logout();
    }
  }
  
}