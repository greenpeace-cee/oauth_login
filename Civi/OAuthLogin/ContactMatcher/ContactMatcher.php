<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\ContactMatcher;

use Civi\OAuthLogin\IdToken;

abstract class ContactMatcher {

  protected array $configuation = [];

  /**
   * Returns the transalted title of this matcher
   */
  abstract public function getTitle(): string;

  /**
   * Reverts the contact creation because something went wrong when 
   * creating the user record.
   * 
   * @param IdToken $idToken
   *   The user ID token coming from the OAuth Provider
   *   The IdToke class is ouw facade around the above.
   *   Making getting data easier.
   * @param int $contactId
   */
  public function revert(IdToken $idToken, int $contactId) {
    // Do nothing
  }

  public function setConfiguration(array $configuration) {
    $this->configuation = $configuration;
  }

  public function getName(): string {
    $name = str_replace('\\', '.', get_class($this));
    return strtolower($name);
  }

  protected function requiredClaims(): array {
    return [];
  }

  public function buildConfigurationForm(\CRM_Core_Form $form, string $fieldNamePrefix) {
    $claimFields = [];
    foreach($this->requiredClaims() as $claim ) {
      $fieldName = $fieldNamePrefix.$claim['claim'];
      $config = [];
      if (isset($this->configuation[$claim['claim']])) {
        $config[$fieldName] = $this->configuation[$claim['claim']];
      }
      \CRM_OAuthLogin_Utils_Form::addClaimField($form, $claim['label'], $fieldName, $claim['required'] ?? false, $config, $claim['default'], $claim['default_token']);
      $claimFields[] = $fieldName;
    }
    $form->assign('claimfields', $claimFields);
  }

  public function getConfigurationTemplateFileName(): ?string {
    return "CRM/OAuthLogin/Form/ContactMatcher/ContactMatcher.tpl";
  }

  public function processConfiguration(array $submittedValues, string $fieldNamePrefix): ?array {
    $configuration = [];
    foreach($this->requiredClaims() as $claim ) {
      $fieldName = $fieldNamePrefix.$claim['claim'];
      $configuration[$claim['claim']] = \CRM_OAuthLogin_Utils_Form::processClaimField($submittedValues, $fieldName);
    }
    return $configuration;
  }

}