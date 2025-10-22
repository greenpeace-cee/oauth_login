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
  if (!$tokenRecord['resource_owner']['email_verified']) {
    return;
  }
  $user = \Civi\Api4\User::get(FALSE)
    ->addSelect('id')
    ->addWhere('uf_name', '=', $tokenRecord['resource_owner']['email'])
    ->execute()
    ->single();

  authx_login([
    'flow' => 'login',
    'useSession' => TRUE,
    'principal' => [
      'userId' => $user['id'],
    ]
  ]);
}
