<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\LoginAction;

use Civi\Api4\Generic\AutocompleteAction;
use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use CRM_OAuthLogin_ExtensionUtil as E;

class SyncAclGroups extends AbstractLoginAction {

  /**
   * Returns the title of this mapping
   * 
   * @return String
   */
  public function getTitle(): string {
    return E::ts('Synchronize CiviCRM ACL Groups');
  }

  /**
   * Execute the action
   */
  public function execute() {
    $groupsToSynchronize = [];
    if (!empty($this->configuration['limit_to_groups'])) {
      $groupsToSynchronize = $this->configuration['selected_groups'] ?? [];
      $groupsToSynchronize = static::getSelectedGroups($groupsToSynchronize);
    } else {
      $groupsToSynchronize = static::getAllAclGroups();
    }
    $contactGroups = static::getContactGroups($this->contactId);
    $groupsToRemove = $groupsToSynchronize;
    $groups = $this->idToken->getClaim($this->configuration['claim']);
    if (is_array($groups)) {
      foreach($groups as $group) {
        if (isset($groupsToSynchronize[$group])) {
          unset($groupsToRemove[$group]);
        }
        if (isset($groupsToSynchronize[$group]) && !isset($contactGroups[$groupsToSynchronize[$group]['id']])) {
          try {
            GroupContact::create(FALSE)
              ->addValue('contact_id', $this->contactId)
              ->addValue('group_id', $groupsToSynchronize[$group]['id'])
              ->execute();
          } catch (\Exception $e) {
            // Do nothing.
          }
        }
      }
    }
    foreach($groupsToRemove as $group) {
      if (isset($contactGroups[$group['id']])) {
        try {
          GroupContact::delete(FALSE)
            ->addWhere('id', '=', $contactGroups[$group['id']]['id'])
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
    $form->add('select', 'token', E::ts('Token'), ['idToken' => E::ts('ID'), 'accessToken' => E::ts('Access')], TRUE, [ 'class' => 'medium']);
    $form->add('text', 'claim', E::ts('Claim'), ['class' => 'huge'], TRUE);
    $form->addYesNo('limit_to_groups', E::ts('Limit to specific CiviCRM Groups'), FALSE, TRUE);
    $form->addAutocomplete('selected_groups', E::ts('Only synchronize these'), [
      'entity' => 'Group',
      'placeholder' => E::ts('- Select groups -'),
      'select' => ['minimumInputLength' => 0, 'multiple' => TRUE,],
      'api' => [
        'fieldName' => static::class . '.selected_groups',
      ]
    ]);

    if (isset($action['configuration']['claim'])) {
      $form->setDefaults(['claim' => $action['configuration']['claim']['token']]);
    }
    if (isset($action['configuration']['claim'])) {
      $form->setDefaults(['claim' => $action['configuration']['claim']['claim']]);
    }
    if (isset($action['configuration']['limit_to_groups'])) {
      $form->setDefaults(['limit_to_groups' => $action['configuration']['limit_to_groups']]);
    } else {
      $form->setDefaults(['limit_to_groups' => 0]);
    }
    if (isset($action['configuration']['selected_groups'])) {
      $form->setDefaults(['selected_groups' => implode(",", $action['configuration']['selected_groups'])]);
    }

  }

  /**
   * This function can be used in child classes to specify the filters for the autocomplete.
   * See the SyncAclGroups class for an example.
   */
  public function prepareAutocomplete(AutocompleteAction $apiRequest, string $field) {
    foreach(static::getGroupWhereParameters() as $whereClause) {
      $apiRequest->addFilter($whereClause[0], $whereClause);
    }
  }

  public function getConfigurationTemplateFileName(): ?string {
    return "CRM/OAuthLogin/Form/LoginAction/SyncAclGroups.tpl";
  }

  public function processConfiguration(array $submittedValues): ?array {
    $configuration['claim']['token'] = $submittedValues['token'];
    $configuration['claim']['claim'] = $submittedValues['claim'];
    $configuration['limit_to_groups'] = $submittedValues['limit_to_groups'];
    $configuration['selected_groups'] = explode(",", $submittedValues['selected_groups']);
    return $configuration;
  }

  private static function getGroupWhereParameters($prefix=''): array {
    return [
      [$prefix . 'is_active', '=', TRUE],
      [$prefix . 'is_hidden', '=', FALSE],
      [$prefix . 'group_type:name', '=', 'Access Control'],
    ];
  }

  protected static function getContactGroups(int $contactId) {
    $userGroups = [];
    try {
      $userGroups = GroupContact::get(FALSE)
        ->addWhere('contact_id', '=', $contactId)
        ->addClause('AND', static::getGroupWhereParameters('group_id.'))
        ->execute()
        ->indexBy('group_id')
        ->getArrayCopy();
    } catch (\Exception $e) {

    }
    return $userGroups;
  }

  protected static function getSelectedGroups(array $selectedGroups) {
    return array_filter(static::getAllAclGroups(), function($group) use ($selectedGroups) {
      return in_array($group['id'], $selectedGroups);
    });
  }

  /**
   * Return all the CiviCRM ACL Groups
   */
  protected static function getAllAclGroups(): array {
    static $groups = [];
    if (empty($groups)) {
      try {
        $groups = Group::get(FALSE)
          ->addClause('AND', static::getGroupWhereParameters())
          ->execute()
          ->indexBy('title')
          ->getArrayCopy();
      } catch (\Exception $e) {

      }
    }
    return $groups;
  }

}