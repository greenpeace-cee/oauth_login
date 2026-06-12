<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\Subscriber;

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthProvider;
use Civi\Core\Event\GenericHookEvent;
use Civi\OAuthLogin\ConfigProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use CRM_OauthLogin_ExtensionUtil as E;

/**
 * Modifies the Standalone auth pages to honour oauthlogin_auth_mode.
 *
 * MODE_DISABLED: this subscriber is a no-op.
 *
 * MODE_OPTIONAL: injects the "Login with SSO" button onto the login page.
 * No other page is touched.
 *
 * MODE_REQUIRED: 302s the password/MFA self-service pages so users can't
 * bypass SSO via direct URL access. There are two distinct contexts:
 *
 *   Anonymous-flow pages (no logged-in user):
 *     /civicrm/login                  CRM_Standaloneusers_Page_Login
 *     /civicrm/login/password         CRM_Standaloneusers_Page_ResetPassword
 *     /civicrm/mfa/totp-setup         CRM_Standaloneusers_Page_TOTPSetup
 *   These all 302 to /civicrm/oauth/login — anonymous user gets sent to the IdP.
 *
 *   Authenticated-only page:
 *     /civicrm/my-account/password    CRM_Standaloneusers_Page_ChangePassword
 *   Sending a logged-in user to /civicrm/auth/login is wrong — they're already
 *   authenticated; we redirect them to /civicrm with a status message
 *   explaining that local password changes are disabled.
 *
 * Standalone serves these pages via CRM_Core_Page subclasses that load
 * Angular modules — no CRM_Core_Form, so we listen on hook_civicrm_pageRun.
 */
class LoginFormSubscriber implements EventSubscriberInterface {

  /**
   * Pages that flow before login completes. Anonymous user → SSO login URL.
   * Logged-in user lands here only by typing the URL; same redirect is fine
   * (the Page classes themselves bounce logged-in users to /civicrm/home,
   *  but in MODE_REQUIRED we just take them straight to the OAuth Login entrypoint
   *  which will detect the existing session and bounce them home anyway).
   */
  private const ANONYMOUS_FLOW_PAGES = [
    \CRM_Standaloneusers_Page_Login::class,
    \CRM_Standaloneusers_Page_ResetPassword::class,
    \CRM_Standaloneusers_Page_TOTPSetup::class,
  ];

  /**
   * Pages that only authenticated users reach. Always redirect to /civicrm
   * with a status message — never to the IdP, since the user already has
   * a session and bouncing through SSO is pointless.
   */
  private const AUTHENTICATED_PAGES = [
    \CRM_Standaloneusers_Page_ChangePassword::class,
  ];

  public function __construct(private readonly ConfigProvider $config) {}

  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_pageRun' => 'onPageRun'];
  }

  public function onPageRun(GenericHookEvent $event): void {
    $page = $event->page;
    $isAnonFlow = $this->matches($page, self::ANONYMOUS_FLOW_PAGES);
    $isAuthOnly = $this->matches($page, self::AUTHENTICATED_PAGES);
    if (!$isAnonFlow && !$isAuthOnly) {
      return;
    }

    $oauthLoginLinks = $this->getOAuthLoginLinks();
    $mode = $this->config->mode();
    if ($mode === ConfigProvider::MODE_DISABLED || !count($oauthLoginLinks)) {
      // OAuth Login is disabled or there or no OAuth Login Clients configured
      return;
    }

    // MODE_OPTIONAL — only inject the SSO button(s) on the login page; nothing
    // else needs to change.
    if ($mode === ConfigProvider::MODE_OPTIONAL) {
      if ($page instanceof \CRM_Standaloneusers_Page_Login) {
        $page->assign('oauthLoginLinks', $oauthLoginLinks);
        \CRM_Core_Region::instance('page-body')->add([
          'template' => 'CRM/OAuthLogin/SsoLoginButtons.tpl',
        ]);
      }
      return;
    }

    // MODE_REQUIRED.
    if ($isAuthOnly) {
      \CRM_Core_Session::setStatus(
        ts('Local password changes are disabled. Your account password is managed by your single sign-on provider.'),
        ts('SSO Enabled'),
        'info'
      );
      \Civi::log()->debug('OAuthLogin: Required mode — redirecting authenticated user away from password self-service', ['from' => get_class($page)]);
      \CRM_Utils_System::redirect(\CRM_Utils_System::url('civicrm/home', '', TRUE, NULL, FALSE));
      return;
    }

    // Anonymous-flow page → SSO entrypoint.
    $oauthLoginLink = reset($oauthLoginLinks);
    \Civi::log()->debug('OAuthLogin: Required mode — redirecting to IdP: ' . $oauthLoginLink['provider'], ['from' => get_class($page)]);
    \CRM_Utils_System::redirect($oauthLoginLink['url']);
  }

  private function matches(object $page, array $classes): bool {
    foreach ($classes as $class) {
      if ($page instanceof $class) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private function getOAuthLoginLinks(): array {
    $links = [];
    $oauthProviders = (array) OAuthProvider::get(FALSE)
      ->addSelect('name', 'title')
      ->addWhere('tags', 'CONTAINS', 'Login')
      ->execute()
      ->indexBy('name');

    $oauthClients = OAuthClient::get(FALSE)
      ->addSelect('provider')
      ->addWhere('provider', 'IN', array_keys($oauthProviders))
      ->execute();
    foreach($oauthClients as $oauthClient) {
      $url = \CRM_Utils_System::url('civicrm/login/oauth', ['id' => $oauthClient['id']]);
      $provider = $oauthProviders[$oauthClient['provider']]['title'];
      $links[] = [
        'url' => $url, 
        'provider' => $provider, 
        'title' => E::ts('Login with %1', [1=>$provider])
      ];
    }
    return $links;
  }

}