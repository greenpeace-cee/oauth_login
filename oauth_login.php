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
  _oauth_login_civix_civicrm_install();
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
  if (CRM_Core_Session::getLoggedInContactID()) {
    return;
  }
  if (!$tokenRecord['resource_owner']['email_verified']) {
    return;
  }
  $existingIdentity = \Civi\Api4\UserOAuthIdentity::get(FALSE)
    ->addSelect('user_id')
    ->addWhere('client_id', '=', $tokenRecord['client_id'])
    ->addWhere('subject', '=', $tokenRecord['resource_owner']['sub'])
    ->execute()
    ->first();
  if (!empty($existingIdentity['user_id'])) {
    $user = ['id' => $existingIdentity['user_id']];
  }
  else {
    $user = \Civi\Api4\User::get(FALSE)
      ->addSelect('id', 'user_oauth_identity.subject')
      ->addJoin(
        'UserOAuthIdentity AS user_oauth_identity',
        'LEFT',
        ['id', '=', 'user_oauth_identity.user_id'],
        ['user_oauth_identity.client_id', '=', $tokenRecord['client_id']]
      )
      ->addWhere('uf_name', '=', $tokenRecord['resource_owner']['email'])
      ->execute()
      ->single();

    if (!empty($user['user_oauth_identity.subject']) && $user['user_oauth_identity.subject'] != $tokenRecord['resource_owner']['sub']) {
      throw new CRM_Core_Exception('User is already linked to a different remote identity of the same issuer.');
    }

    if (empty($user['user_oauth_identity.subject'])) {
      \Civi\Api4\UserOAuthIdentity::create(FALSE)
        ->addValue('user_id', $user['id'])
        ->addValue('client_id', $tokenRecord['client_id'])
        ->addValue('subject', $tokenRecord['resource_owner']['sub'])
        ->execute();
    }

  }

  authx_login([
    'flow' => 'login',
    'useSession' => TRUE,
    'principal' => [
      'userId' => $user['id'],
    ]
  ]);
}
