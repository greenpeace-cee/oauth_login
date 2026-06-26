<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin;

use Civi\Core\Service\AutoService;
use Civi\OAuthLogin\ContactMatcher\BasicCreateContactMatcher;
use Civi\OAuthLogin\ContactMatcher\BasicUpdateContactMatcher;
use Civi\OAuthLogin\ContactMatcher\CreateContactMatcher;
use Civi\OAuthLogin\ContactMatcher\UpdateContactMatcher;
use Civi\OAuthLogin\Event\AuthenticationEvent;
use CRM_Utils_System;
use CRM_Core_Session;
use CRM_OAuthLogin_ExtensionUtil as E;

/**
 * @service civi.oauthlogin
 */
class Service extends AutoService {

  protected $contactCreateMatchers = [];

  protected $contactUpdateMatchers = [];

  protected $contactCreateMatcherList = [];

  protected $contactUpdateMatcherList = [];

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
    $this->addCreateContactMatcher(new BasicCreateContactMatcher());
    $this->addUpdateContactMatcher(new BasicUpdateContactMatcher());
  }

  public function addCreateContactMatcher(CreateContactMatcher $matcher) {
    $this->contactCreateMatchers[$matcher->getName()] = $matcher;
    $this->contactCreateMatcherList[$matcher->getName()] = $matcher->getTitle();
  }

  public function addUpdateContactMatcher(UpdateContactMatcher $matcher) {
    $this->contactUpdateMatchers[$matcher->getName()] = $matcher;
    $this->contactUpdateMatcherList[$matcher->getName()] = $matcher->getTitle();
  }

  /**
   * Get the contact matcher class for creating a contact.
   */
  public function getContactCreateMatcher(?string $name = NULL):? CreateContactMatcher {
    if ($name === NULL) {
      $name = $this->configProvider->getContactCreateMatcher();
    }
    if (!empty($name) && isset($this->contactCreateMatchers[$name])) {
      $matcher = $this->contactCreateMatchers[$name];
      $matcher->setConfiguration($this->configProvider->getContactCreateMatcherConfig());
      return $matcher;
    }
    return NULL;
  }

  /**
   * Get the contact matcher class for updating an existing contact.
   */
  public function getContactUpdateMatcher(?string $name = NULL):? UpdateContactMatcher {
    if ($name === NULL) {
      $name = $this->configProvider->getContactUpdateMatcher();
    }
    if (!empty($name) && isset($this->contactUpdateMatchers[$name])) {
      $matcher = $this->contactUpdateMatchers[$name];
      $matcher->setConfiguration($this->configProvider->getContactUpdateMatcherConfig());
      return $matcher;
    }
    return NULL;
  }

  public function getContactCreateMatcherList(): array {
    return $this->contactCreateMatcherList;
  }

  public function getContactUpdateMatcherList(): array {
    return $this->contactUpdateMatcherList;
  }

  public function getConfigProvider(): ConfigProvider {
    return $this->configProvider;
  }

  /**
   * Login a user based on the IdToken
   * 
   * Checks first if a user exist. If it does it tries to update its related contact 
   * based on the settings. If it does not exist it will create a contact and a user record.
   *
   * It then creates a session. And stores the access token, refresh token and session state in the session.
   */
  public function login(IdToken $idToken): void {
    $userProvisioning = new UserProvisioning();
    $user = $userProvisioning->findExistingUser($idToken);
    $isNewUser = false;
    if (!empty($user)) {
      $user = $userProvisioning->updateUser($idToken, $user);
    } 
    if (empty($user) && $this->configProvider->isProvisioningEnabled()) {
      $user = $userProvisioning->createUser($idToken);
      $isNewUser = true;
    } elseif (empty($user)) {
      $this->loginFailed(E::ts('No active user was found for the provided identity. Make sure your email matches the one used by your identity provider.'));
    }
    if (!empty($user)) {
      if (empty($user['subject']) || $user['subject'] != $idToken->getSubject()) {
        $this->loginFailed(E::ts('User is already linked to a different remote identity of the same identity provider.'));
      }

      /**
       * Call the pre authentication event
       */
      \Civi::dispatcher()->dispatch('civi.oauthlogin.preauthentication', new AuthenticationEvent($idToken, $user['id'], $user['contact_id'], $isNewUser));

      authx_login([
        'flow' => 'login',
        'useSession' => TRUE,
        'principal' => [
          'userId' => $user['id'],
        ]
      ]);
      $session = \CRM_Core_Session::singleton();
      $session->set('oauth_login_is_oauth_session', TRUE);

      /**
       * Call the post authentication event
       */
      \Civi::dispatcher()->dispatch('civi.oauthlogin.postauthentication', new AuthenticationEvent($idToken, $user['id'], $user['contact_id'], $isNewUser));
    }
  }

  public function logout() {
    $userSystem = \CRM_Core_Config::singleton()->userSystem;
    $session = \CRM_Core_Session::singleton();
    $userSystem->logout();
    $postLogoutUrl = $userSystem->postLogoutUrl();
    $session->set('oauth_login_is_oauth_session', NULL);
    \CRM_Core_Session::setStatus(E::ts('You have been logged out.'), E::ts('Logout'), 'info', ['expires' => 0]);
    \CRM_Utils_System::redirect($postLogoutUrl);
  }

  private function loginFailed(string $reason) {
    CRM_Core_Session::setStatus($reason, E::ts('Login failed'), 'error');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/login'));
  }

}