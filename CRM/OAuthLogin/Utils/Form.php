<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

use CRM_OAuthLogin_ExtensionUtil as E;

class CRM_OAuthLogin_Utils_Form {

  public static function addClaimField(CRM_Core_Form $form, string $label, string $fieldName, bool $required, array $configuration, string $defaultClaim = '', string $defaultToken = 'idToken') {
    $form->add('select', $fieldName . 'token', E::ts('Token'), ['idToken' => E::ts('ID'), 'accessToken' => E::ts('Access')], $required, [ 'class' => 'medium']);
    $form->add('text', $fieldName, $label, ['class' => 'huge'], $required);

    if (isset($configuration[$fieldName]) && isset($configuration[$fieldName]['token'])) {
      $form->setDefaults([$fieldName.'token' => $configuration[$fieldName]['token']]);
    } else {
      $form->setDefaults([$fieldName.'token' => $defaultToken]);
    }
    if (isset($configuration[$fieldName]) && isset($configuration[$fieldName]['claim'])) {
      $form->setDefaults([$fieldName => $configuration[$fieldName]['claim']]);
    } else {
      $form->setDefaults([$fieldName => $defaultClaim]);
    }
  }

  public static function processClaimField(array $submittedValues, string $fieldName): array {
    $claim = [];
    if (isset($submittedValues[$fieldName])) {
      $claim['claim'] = $submittedValues[$fieldName];
    }
    if (isset($submittedValues[$fieldName.'token'])) {
      $claim['token'] = $submittedValues[$fieldName.'token'];
    }
    return $claim;
  }

}