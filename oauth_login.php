<?php

require_once 'oauth_login.civix.php';

use CRM_OauthLogin_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function oauth_login_civicrm_config(&$config): void {
  _oauth_login_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function oauth_login_civicrm_install(): void {
  // grant "create OAuth tokens via auth code flow" to the "everyone" group
  $everyoneExists = \Civi\Api4\Role::get(FALSE)
    ->selectRowCount()
    ->addWhere('name', '=', 'everyone')
    ->execute()
    ->count() == 1;
  if ($everyoneExists) {
    \Civi\Api4\RolePermission::update(FALSE)
      ->addValue('granted_everyone', TRUE)
      ->addWhere('name', '=', 'create OAuth tokens via auth code flow')
      ->execute();
    CRM_Core_Session::setStatus(
      E::ts('The "create OAuth tokens via auth code flow" permission was granted to the "everyone" role to allow unauthenticated users to login using OAuth.'),
      E::ts('OAuth Login Permission Added'),
      'info'
    );
  }
  _oauth_login_civix_civicrm_install();
}

function oauth_login_civicrm_check(&$messages, $statusNames, $includeDisabled) {
  if ($statusNames && !in_array('oauth_login_permission', $statusNames)) {
    return;
  }
  $everyoneExists = \Civi\Api4\Role::get(FALSE)
      ->selectRowCount()
      ->addWhere('name', '=', 'everyone')
      ->execute()
      ->count() == 1;

  if ($everyoneExists) {
    $rolePermission = \Civi\Api4\RolePermission::get(FALSE)
      ->addWhere('name', '=', 'create OAuth tokens via auth code flow')
      ->addSelect('granted_everyone')
      ->execute()
      ->single();
    if ($rolePermission['granted_everyone']) {
      // permission is granted to everyone, ok
      return;
    }
  }
  $message = new CRM_Utils_Check_Message(
    'oauth_login_permission',
    E::ts('To login using OAuth, unauthenticated users need to be allowed to use the auth code flow. The "create OAuth tokens via auth code flow" permission should be granted to the "everyone" role.'),
    E::ts('Missing Permission for OAuth Login'),
    \Psr\Log\LogLevel::WARNING,
    'fa-flag'
  );
  $message->addAction(
    E::ts('Administer Roles'),
    FALSE,
    'href',
    ['path' => 'civicrm/admin/roles']
  );
  $messages[] = $message;
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function oauth_login_civicrm_enable(): void {
  _oauth_login_civix_civicrm_enable();
}


function oauth_login_civicrm_oauthProviders(&$providers) {
  $providers['google_login'] = [
    'name' => 'google_login',
    'title' => 'Google',
    'class' => 'League\OAuth2\Client\Provider\Google',
    'tags' => ['Login'],
    'options' => [
      'urlAuthorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
      'urlAccessToken' => 'https://www.googleapis.com/oauth2/v4/token',
      'urlResourceOwnerDetails' => 'https://openidconnect.googleapis.com/v1/userinfo',
      'accessType' => 'offline',
      'scopeSeparator' => ' ',
      'scopes' => ['openid', 'profile', 'email'],
      'prompt' => 'select_account consent',
      'templates' => [
        'Contact' => [
          'email' => '{{token.raw.email}}',
        ]
      ]
    ]
  ];
}

function oauth_login_civicrm_oauthReturn($tokenRecord, &$nextUrl) {
  if ($tokenRecord['tag'] != 'Login') {
    return;
  }
  if (CRM_Core_Session::getLoggedInContactID()) {
    return;
  }
  $idToken = new \Civi\OAuth\Login\IdToken($tokenRecord);
  if (!$idToken->validate()) {
    foreach ($idToken->getValidationMessages() as $message) {
      Civi::log('oauth_login')->error("ID Token validation error for OAuthClient {$tokenRecord['client_id']}: {$message}");
    }
    CRM_Core_Session::setStatus(
      E::ts('Unable to validate ID claims provided by the identity provider.'),
      E::ts('Login failed'),
      'error'
    );
    $nextUrl = CRM_Utils_System::url('civicrm/login');
    return;
  }
  // TODO: refactor this into \Civi\OAuth\Login\IdToken and/or other classes
  if (!$tokenRecord['resource_owner']['email_verified']) {
    // only accept verified emails
    // TODO: this may not work across other identity providers
    return;
  }
  // do we have an existing link between a user and the sub(ject) for this provider?
  $existingIdentity = \Civi\Api4\UserOAuthIdentity::get(FALSE)
    ->addSelect('user_id')
    ->addWhere('client_id', '=', $tokenRecord['client_id'])
    ->addWhere('subject', '=', $tokenRecord['resource_owner']['sub'])
    ->addJoin('User AS user', 'INNER', ['user_id', '=', 'user.id'], ['user.is_active', '=', 1])
    ->execute()
    ->first();
  if (!empty($existingIdentity['user_id'])) {
    $user = ['id' => $existingIdentity['user_id']];
  }
  else {
    // no existing link found, look up active users by email
    $user = \Civi\Api4\User::get(FALSE)
      ->addSelect('id', 'user_oauth_identity.subject')
      ->addJoin(
        'UserOAuthIdentity AS user_oauth_identity',
        'LEFT',
        ['id', '=', 'user_oauth_identity.user_id'],
        ['user_oauth_identity.client_id', '=', $tokenRecord['client_id']]
      )
      ->addWhere('uf_name', '=', $tokenRecord['resource_owner']['email'])
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    if (!empty($user['user_oauth_identity.subject']) && $user['user_oauth_identity.subject'] != $tokenRecord['resource_owner']['sub']) {
      CRM_Core_Session::setStatus(
        E::ts('User is already linked to a different remote identity of the same identity provider.'),
        E::ts('Login failed'),
        'error'
      );
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/login'));
    }

    // link subject to user for this provider
    if (!empty($user['id']) && empty($user['user_oauth_identity.subject'])) {
      \Civi\Api4\UserOAuthIdentity::create(FALSE)
        ->addValue('user_id', $user['id'])
        ->addValue('client_id', $tokenRecord['client_id'])
        ->addValue('subject', $tokenRecord['resource_owner']['sub'])
        ->execute();
    }
  }

  if (empty($user['id'])) {
    CRM_Core_Session::setStatus(
      E::ts('No active user was found for the provided identity. Make sure your email matches the one used by your identity provider.'),
      E::ts('Login failed'),
      'error'
    );
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/login'));
  }

  authx_login([
    'flow' => 'login',
    'useSession' => TRUE,
    'principal' => [
      'userId' => $user['id'],
    ]
  ]);
}
