<?php

require_once 'oauth_login.civix.php';

use Civi\OAuthLogin\Service;
use Civi\OAuthLogin\Subscriber\LoginFormSubscriber;
use Civi\OAuthLogin\Subscriber\PasswordAuthBlockSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use CRM_OauthLogin_ExtensionUtil as E;

/**
 * Implements hook_civicrm_container().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function oauth_login_civicrm_container(ContainerBuilder $container): void {
  $container->findDefinition('dispatcher')
    // Comment any line to disable the feature.
    ->addMethodCall('addSubscriber', [new Definition(LoginFormSubscriber::class, [new Reference('civi.oauthlogin.config')])])
    ->addMethodCall('addSubscriber', [new Definition(PasswordAuthBlockSubscriber::class, [new Reference('civi.oauthlogin.config')])])
  ;
}

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
  $files = (array) glob(E::path('oauth-providers') . DIRECTORY_SEPARATOR . '*.json');
  foreach($files as $file) {
    if (!defined('CIVICRM_TEST') && preg_match(';\.test\.json$;', $file)) {
      continue;
    }
    $name = preg_replace(';\.(dist\.|test\.|)json$;', '', basename($file));
    $provider = json_decode(file_get_contents($file), 1);
    if ($provider) {
      $provider['name'] = $name;
      $providers[$name] = $provider;
    }
  }
}

function oauth_login_civicrm_oauthReturn($tokenRecord, &$nextUrl) {
  if ($tokenRecord['tag'] != 'Login') {
    return;
  }
  if (CRM_Core_Session::getLoggedInContactID()) {
    return;
  }
  $idToken = new \Civi\OAuthLogin\IdToken($tokenRecord);
  if (!$idToken->validate()) {
    Civi::log('oauth_login')->error("ID Token validation error for OAuthClient {$tokenRecord['client_id']}");
    CRM_Core_Session::setStatus(
      E::ts('Unable to validate ID claims provided by the identity provider.'),
      E::ts('Login failed'),
      'error'
    );
    $nextUrl = CRM_Utils_System::url('civicrm/login');
    return;
  }

  /** @var Service $service */
  $service = \Civi::service('civi.oauthlogin');
  $service->login($idToken);
}

