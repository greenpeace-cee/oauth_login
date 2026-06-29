<?php

namespace Civi\OAuthLogin\LoginAction;

use Civi\Api4\Generic\AutocompleteAction;
use Civi\OAuthLogin\IdToken;

abstract class AbstractLoginAction {

  protected $configuration = array();

  protected ?IdToken $idToken = NULL;

  protected ?int $userId = NULL;

  protected ?int $contactId = NULL;

  /**
   * Returns the title of this mapping
   * 
   * @return String
   */
  abstract public function getTitle(): string;

  /**
   * Execute the action
   */
  abstract public function execute();

  /**
   * Sets configuration
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  public function setIdToken(IdToken $idToken) {
    $this->idToken = $idToken;
  }

  public function setUserId(int $userId) {
    $this->userId = $userId;
  }

  public function setContactId(int $contactId) {
    $this->contactId = $contactId;
  }

  public function hasConfiguration(): bool {
    return FALSE;
  }

  public function buildConfigurationForm(\CRM_Core_Form $form, array $action) {

  }

  public function getConfigurationTemplateFileName(): ?string {
    return NULL;
  }

  public function processConfiguration(array $submittedValues): ?array {
    return NULL;
  }

  /**
   * This function can be used in child classes to specify the filters for the autocomplete.
   * See the SyncAclGroups class for an example.
   */
  public function prepareAutocomplete(AutocompleteAction $apiRequest, string $field) {
    // Do nothing.
  }
}