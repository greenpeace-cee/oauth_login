<?php

use Civi\Api4\OAuthClient;
use CRM_OauthLogin_ExtensionUtil as E;

class CRM_OauthLogin_Page_OAuthLogin extends CRM_Core_Page {

  public function run() {
    $start = OAuthClient::authorizationCode(FALSE)
      ->setStorage('OAuthSessionToken')
      ->setTag('login')
      ->setLandingUrl(CRM_Utils_System::url('civicrm/', NULL, TRUE, NULL, FALSE))
      ->addWhere('id', '=', CRM_Utils_Request::retrieve('id', 'Integer'))
      ->execute()
      ->first();
    CRM_Utils_System::redirect($start['url']);
  }

}
