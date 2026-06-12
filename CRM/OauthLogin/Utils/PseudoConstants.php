<?php

use Civi\Api4\Role;
use Civi\OAuthLogin\Service;

class CRM_OauthLogin_Utils_PseudoConstants {

  public static function roles(): array {
    static $roles = [];
    if (empty($roles)) {
      $roles = Role::get(FALSE)
        ->addSelect('id', 'name', 'label')
        ->addWhere('name', '!=', 'everyone')
        ->execute()
        ->column('label', 'name');
    }
    return $roles;
  }

  public static function contactCreateMatchers(): array {
    /** @var Service $service */
    $service = \Civi::service('civi.oauthlogin');
    return $service->getContactCreateMatcherList();
  }

  public static function contactUpdateMatchers(): array {
    /** @var Service $service */
    $service = \Civi::service('civi.oauthlogin');
    return $service->getContactUpdateMatcherList();
  }
}