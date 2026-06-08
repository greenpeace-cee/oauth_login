<?php

use Civi\Api4\Role;

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
}