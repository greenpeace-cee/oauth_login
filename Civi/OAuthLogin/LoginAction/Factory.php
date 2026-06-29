<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\LoginAction;

use Civi\Api4\Generic\AutocompleteAction;
use Civi\Api4\OAuthLoginAction;
use Civi\API\Event\PrepareEvent;
use Civi\Core\Service\AutoSubscriber;
use Civi\OAuthLogin\Event\AuthenticationEvent;

/**
 * @service civi.oauthlogin.loginactions
 * 
 * Factory class to execute post login actions.
 * A post login action is a configurable action such as sync roles
 */
class Factory extends AutoSubscriber {

  protected $loginActions = [];

  public function __construct()
  {
    $this->addLoginAction(SyncUserRoles::class);
    $this->addLoginAction(SyncAclGroups::class);
  }

  public static function getSubscribedEvents(): array {
    return [
      'civi.oauthlogin.preauthentication' => 'preAuthentication',
      'civi.api.prepare' => ['onApiPrepareForAutoComplete'],
    ];
  }

  public function preAuthentication(AuthenticationEvent $e) {
    $actions = [];
    try {
      $actions = OAuthLoginAction::get(FALSE)
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('weight')
        ->execute();
    } catch (\Exception $e) {
      // Do nothing
    }
    foreach($actions as $action) {
      if ($action['client_id'] !== NULL && $action['client_id'] != $e->idToken->getClientId()) {
        continue;
      }
      try {
        $class = $this->getLoginAction($action['type']);
        if (!$class) {
          continue;
        }
        $class->setConfiguration($action['configuration'] ?? []);
        $class->setIdToken($e->idToken);
        $class->setUserId($e->userId);
        $class->setContactId($e->contactId);
        $class->execute();
      } catch (\Exception $e) {
        // Do nothing
      }
    }
  }

  public function onApiPrepareForAutoComplete(PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && $apiRequest instanceof AutocompleteAction) {
      if (str_starts_with($apiRequest->getFormName(), 'qf:CRM_OAuthLogin_Form_OAuthLoginAction')) {
        $fieldName = $apiRequest->getFieldName();
        list($class, $field) = explode(".", $fieldName);
        $name = $this->classToName($class);
        $loginAction = $this->getLoginAction($name);
        if ($loginAction !== NULL) {
          $loginAction->prepareAutocomplete($apiRequest, $field);
        }
      }
    }
  }

  /**
   * Add a Post Login Action class.
   * 
   * @param string $class
   *   The name of the class
   */
  public function addLoginAction(string $class) {
    $name = $this->classToName($class);
    $this->loginActions[$name] = $class;
  }

  /**
   * Returns an instance of a post login action.
   * 
   * @param string $name
   * @return AbstractLoginAction|null
   *   Returns null when the class is not found.
   */
  public function getLoginAction(string $name):? AbstractLoginAction {
    if (isset($this->loginActions[$name])) {
      $class = $this->loginActions[$name];
      return new $class();
    }
    return NULL;
  }

  /**
   * Return a list of all login actions
   * 
   * @return array
   */
  public function getLoginActionTitles(): array {
    $titles = [];
    foreach($this->loginActions as $name => $loginAction) {
      $class = $this->getLoginAction($name);
      if ($class) {
        $titles[$name] = $class->getTitle();
      }
    }
    return $titles;
  }

  private function classToName(string $class): string {
    $name = str_replace('\\', '.', $class);
    $name = str_replace(".loginaction.", ".", strtolower($name));
    return $name;
  }

}