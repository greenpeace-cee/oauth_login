<?php
declare(strict_types = 1);

use CRM_OAuthLogin_ExtensionUtil as E;

final class CRM_OAuthLogin_BAO_OAuthLoginAction extends CRM_OAuthLogin_DAO_OAuthLoginAction {

  public static function getTypes(): array {
    /** @var \Civi\OAuthLogin\LoginAction\Factory $factory */
    $factory = \Civi::service('civi.oauthlogin.loginactions');
    return $factory->getLoginActionTitles();
  }

}
