<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

use Civi\OAuthLogin\Service;
use CRM_OAuthLogin_ExtensionUtil as E;

class CRM_OAuthLogin_Form_Settings extends CRM_Admin_Form_Generic {

  private ?string $snippet = NULL;

  /**
   * @var \Civi\OAuthLogin\ContactMatcher\ContactMatcher
   */
  private $createContactMatcher;

  /**
   * @var \Civi\OAuthLogin\ContactMatcher\ContactMatcher
   */
  private $updateContactMatcher;

  public function preProcess() {
    $this->sections = [
      'default' => ['fields' => []],
      'contact_create' => [
        'fields' => [], 
        'title' => E::ts('Contact Create'), 
        'description' => E::ts('When a new user is created it also creates a new contact. This settings defeine how the new contact should be created.')
      ],
      'contact_update' => [
        'fields' => [], 
        'title' => E::ts('Contact Update'),
        'description' => E::ts('When an existing user logs in how should we update its related contact?')
      ],
    ];

    $this->snippet = CRM_Utils_Request::retrieve('snippet', 'String');
    $sectionName = CRM_Utils_Request::retrieve('sectionName', 'String');
    if ($this->snippet && $sectionName) {
      $this->assign('suppressForm', TRUE);
      $this->controller->_generateQFKey = FALSE;
    }
    $this->assign('sectionName', $sectionName);

    /** @var Service */
    $service = \Civi::service('civi.oauthlogin');
    $this->createContactMatcher = $service->getContactCreateMatcher();
    $this->assign('create_contact_matcher_configuration_template', NULL);
    if ($sectionName == 'contact_create') {
      $type = CRM_Utils_Request::retrieve('type', 'String');
      $this->createContactMatcher = NULL;
      if ($type) {
        $this->createContactMatcher = $service->getContactCreateMatcher($type);
      }
    } elseif (!$this->createContactMatcher) {
      $type = array_key_first(CRM_OAuthLogin_Utils_PseudoConstants::contactCreateMatchers());
      if ($type) {
        $this->createContactMatcher = $service->getContactCreateMatcher($type);
      }
    }
    if ($this->createContactMatcher) {
      $this->assign('create_contact_matcher_configuration_template', $this->createContactMatcher->getConfigurationTemplateFileName());
    }
    $this->updateContactMatcher = $service->getContactCreateMatcher();
    if ($sectionName == 'contact_update') {
      $type = CRM_Utils_Request::retrieve('type', 'String');
      $this->updateContactMatcher = NULL;
      if ($type) {
        $this->updateContactMatcher = $service->getContactUpdateMatcher($type);
      }
    }
    $this->assign('update_contact_matcher_configuration_template', NULL);
    if ($this->updateContactMatcher) {
      $this->assign('update_contact_matcher_configuration_template', $this->updateContactMatcher->getConfigurationTemplateFileName());
    }

    parent::preProcess();
  }

  public function buildQuickForm()
  {
    if ($this->createContactMatcher) {
      $this->createContactMatcher->buildConfigurationForm($this, 'create');
    }
    if ($this->updateContactMatcher) {
      $this->updateContactMatcher->buildConfigurationForm($this, 'update');
    }
    parent::buildQuickForm();
  }

  public function postProcess()
  {
    /** @var Service */
    $service = \Civi::service('civi.oauthlogin');
    $values = $this->exportValues();
    $service->getConfigProvider()->setContactUpdateMatcherConfig([]);
    if ($this->createContactMatcher) {
      $createConfiguration = $this->createContactMatcher->processConfiguration($values, 'create');
      $service->getConfigProvider()->setContactUpdateMatcherConfig($createConfiguration);
    }
    $service->getConfigProvider()->setContactCreateMatcherConfig([]);
    if ($this->updateContactMatcher) {
      $updateConfiguration = $this->updateContactMatcher->processConfiguration($values, 'update');
      $service->getConfigProvider()->setContactCreateMatcherConfig($updateConfiguration);
    }
    parent::postProcess();

  }

}