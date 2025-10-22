<?php

namespace Civi;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use CRM_Utils_System;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides OAuth client login options
 * @internal
 * @service
 */
class OAuthLoginAutoService extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_alterAngular' => ['alterAngular'],
    ];
  }

  public static function alterAngular(GenericHookEvent $event) {
    $oauth_providers = (array) Api4\OAuthProvider::get(FALSE)
      ->addSelect('name', 'title')
      ->addWhere('tags', 'CONTAINS', 'Login')
      ->execute()
      ->indexBy('name');

    $oauth_clients = Api4\OAuthClient::get(FALSE)
      ->addSelect('provider')
      ->addWhere('provider', 'IN', array_keys($oauth_providers))
      ->execute();

    $change_set = Angular\ChangeSet::create('addSSOLinks')->alterHtml(
      '~/crmLogin/crmLogin.html',
      function($doc) use ($oauth_providers, $oauth_clients) {
        $login_form = $doc->find('div.crm-login > form');
        $login_form->append('<ul class="sso-clients">');

        foreach ($oauth_clients as $client) {
          $id = $client['id'];
          $href = CRM_Utils_System::url('civicrm/login/oauth', [ 'id' => $id ]);
          $provider = $oauth_providers[$client['provider']]['title'];

          $login_form->append("<li><a href=\"$href\">Sign in with $provider ($id)</a></li>");
        }

        $login_form->append('</ul>');
      }
    );

    $event->angular->add($change_set);
  }

}
