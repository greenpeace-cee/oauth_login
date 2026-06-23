<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\LoginAction;

use Civi\Api4\Role;
use Civi\Api4\UserRole;
use CRM_OauthLogin_ExtensionUtil as E;

class SyncUserRoles extends AbstractLoginAction {

  /**
   * Returns the title of this mapping
   * 
   * @return String
   */
  public function getTitle(): string {
    return E::ts('Sync user roles');
  }

  /**
   * Execute the action
   */
  public function execute() {
    $allRoles = static::getAllCiviRoles();
    if (!empty($this->configuration['limit_to_roles'])) {
      $allRoles = $this->configuration['selected_roles'] ?? [];
      $allRoles = static::getSelectedRoles($allRoles);
    }
    $userRoles = static::getUserRoles($this->userId);
    $rolesToRemove = $allRoles;
    $roles = $this->idToken->getClaim($this->configuration['claim']);
    if (is_array($roles)) {
      foreach($roles as $role) {
        if (isset($allRoles[$role])) {
          unset($rolesToRemove[$role]);
        }
        if (isset($allRoles[$role]) && !isset($userRoles[$allRoles[$role]['id']])) {
          try {
            UserRole::create(FALSE)
              ->addValue('user_id', $this->userId)
              ->addValue('role_id', $allRoles[$role]['id'])
              ->execute();
          } catch (\Exception $e) {
            // Do nothing.
          }
        }
      }
    }
    foreach($rolesToRemove as $role) {
      if (isset($userRoles[$role['id']])) {
        try {
          UserRole::delete(FALSE)
            ->addWhere('id', '=', $userRoles[$role['id']]['id'])
            ->execute();
        } catch (\Exception $e) {
          // Do nothing
        }
      }
    }
  }

  public function hasConfiguration(): bool {
    return TRUE;
  }

  public function buildConfigurationForm(\CRM_Core_Form $form, array $action) {
    $allRoles = static::getAllCiviRoles();
    $options = [];
    foreach($allRoles as $role) {
      if ($role['name'] == 'everyone') {
        continue;
      }
      $options[$role['label']] = $role['name'];
    }
    $form->add('select', 'token', E::ts('Token'), ['idToken' => E::ts('ID'), 'accessToken' => E::ts('Access')], TRUE, [ 'class' => 'medium']);
    $form->add('text', 'claim', E::ts('Claim'), ['class' => 'huge'], TRUE);
    $form->addYesNo('limit_to_roles', E::ts('Limit to specific CiviCRM roles'), FALSE, TRUE);
    $form->addCheckBox('selected_roles', E::ts('Only synchronize these'), $options);

    if (isset($action['configuration']['claim'])) {
      $form->setDefaults(['claim' => $action['configuration']['claim']['token']]);
    }
    if (isset($action['configuration']['claim'])) {
      $form->setDefaults(['claim' => $action['configuration']['claim']['claim']]);
    }
    if (isset($action['configuration']['limit_to_roles'])) {
      $form->setDefaults(['limit_to_roles' => $action['configuration']['limit_to_roles']]);
    } else {
      $form->setDefaults(['limit_to_roles' => 0]);
    }
    if (isset($action['configuration']['selected_roles'])) {
      $selectedRoles = [];
      foreach($action['configuration']['selected_roles'] as $role) {
        $selectedRoles[$role] = $role;
      }
      $form->setDefaults(['selected_roles' => $selectedRoles]);
    }

  }

  public function getConfigurationTemplateFileName(): ?string {
    return "CRM/OAuthLogin/Form/LoginAction/SyncUserRoles.tpl";
  }

  public function processConfiguration(array $submittedValues): ?array {
    $configuration =[];
    $selectedRoles = [];
    foreach($submittedValues['selected_roles'] as $selectedRoleName => $selected) {
      if ($selected) {
        $selectedRoles[] = $selectedRoleName; 
      }
    }
    $configuration['claim']['token'] = $submittedValues['token'];
    $configuration['claim']['claim'] = $submittedValues['claim'];
    $configuration['limit_to_roles'] = $submittedValues['limit_to_roles'];
    $configuration['selected_roles'] = $selectedRoles;
    return $configuration;
  }

  protected static function getUserRoles(int $userId) {
    $userRoles = [];
    try {
      $userRoles = UserRole::get(FALSE)
        ->addWhere('user_id', '=', $userId)
        ->execute()
        ->indexBy('role_id')
        ->getArrayCopy();
    } catch (\Exception $e) {

    }
    return $userRoles;
  }

  protected static function getSelectedRoles(array $selectedRoles) {
    return array_filter(static::getAllCiviRoles(), function($role) use ($selectedRoles) {
      return in_array($role['name'], $selectedRoles);
    });
  }

  /**
   * Return all the CiviCRM roles
   */
  protected static function getAllCiviRoles(): array {
    static $roles = [];
    if (empty($roles)) {
      try {
        $roles = Role::get(FALSE)
          ->execute()
          ->indexBy('name')
          ->getArrayCopy();
      } catch (\Exception $e) {

      }
    }
    return $roles;
  }

}