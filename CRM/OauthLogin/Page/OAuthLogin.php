<?php

use Civi\Api4\OAuthClient;
use CRM_OauthLogin_ExtensionUtil as E;

class CRM_OauthLogin_Page_OAuthLogin extends CRM_Core_Page {

  public function run() {
    if (CRM_Core_Session::getLoggedInContactID()) {
      CRM_Core_Session::setStatus(
        E::ts("You're already logged in."),
        E::ts('Already logged-in'),
        'info'
      );
      CRM_Utils_System::redirect('/civicrm/home?reset=1');
    }
    $start = OAuthClient::authorizationCode(FALSE)
      ->setStorage('OAuthSessionToken')
      ->setTag('Login')
      ->setLandingUrl(CRM_Utils_System::url('civicrm/', NULL, TRUE, NULL, FALSE))
      ->addWhere('id', '=', CRM_Utils_Request::retrieve('id', 'Integer'))
      ->execute()
      ->first();
    CRM_Utils_System::redirect($start['url']);
  }

}
