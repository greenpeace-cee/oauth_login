<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin;

use Civi\Core\Service\AutoService;
use Civi\OAuthLogin\ContactMatcher\BasicContactCreation;
use Civi\OAuthLogin\ContactMatcher\ContactMatcher;
use CRM_Utils_System;
use CRM_Core_Session;
use CRM_OauthLogin_ExtensionUtil as E;

/**
 * @service civi.oauthlogin
 */
class Service extends AutoService {

  protected $contactMatchers = [];

  protected $contactMatcherList = [];

  /**
   * @var \Civi\OAuthLogin\ConfigProvider
   */
  protected ConfigProvider $configProvider;

  /**
   * @inject civi.oauthlogin.config
   */
  public function __construct(ConfigProvider $configProvider)
  {
    $this->configProvider = $configProvider;
    $this->addMatcher(new BasicContactCreation());
  }

  public function addMatcher(ContactMatcher $matcher) {
    $this->contactMatchers[$matcher->getName()] = $matcher;
    $this->contactMatcherList[$matcher->getName()] = $matcher->getTitle();
  }

  public function getContactMatcher():? ContactMatcher {
    $name = $this->configProvider->getContactMatchier();
    if (!empty($name) && isset($this->contactMatchers[$name])) {
      return $this->contactMatchers[$name];
    }
    return NULL;
  }

  public function getContactMatcherList(): array {
    return $this->contactMatcherList;
  }

  public function getConfigProvider(): ConfigProvider {
    return $this->configProvider;
  }

  public function login(IdToken $idToken): void {
    $userProvisioning = new UserProvisioning();
    $user = $userProvisioning->findExistingUserId($idToken);
    if (empty($user) && $this->configProvider->isProvisioningEnabled()) {
      $user = $userProvisioning->createUser($idToken);
    } elseif (empty($user)) {
      $this->loginFailed(E::ts('No active user was found for the provided identity. Make sure your email matches the one used by your identity provider.'));
    }
    if (!empty($user)) {
      if (empty($user['subject']) || $user['subject'] != $idToken->getSubject()) {
        $this->loginFailed(E::ts('User is already linked to a different remote identity of the same identity provider.'));
      }
      authx_login([
        'flow' => 'login',
        'useSession' => TRUE,
        'principal' => [
          'userId' => $user['user_id'],
        ]
      ]);
      $session = \CRM_Core_Session::singleton();
      $session->set('oauth_login_is_oauth_session', TRUE);
      $session->set('oauth_login_session_state', $idToken->tokenRecord['raw']['session_state']);
      $session->set('oauth_login_access_token', $idToken->tokenRecord['raw']['access_token']);
      $session->set('oauth_login_refresh_token', $idToken->tokenRecord['raw']['refresh_token']);
    }
  }

  private function loginFailed(string $reason) {
    CRM_Core_Session::setStatus($reason, E::ts('Login failed'), 'error');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/login'));
  }

}