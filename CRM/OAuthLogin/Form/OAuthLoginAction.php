<?php

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthLoginAction;
use Civi\Api4\OAuthProvider;
use CRM_OAuthLogin_ExtensionUtil as E;

class CRM_OAuthLogin_Form_OAuthLoginAction extends CRM_Core_Form {

  private ?int $id = NULL;

  private array $loginAction = [];

  private ?string $snippet = NULL;

  /**
   * @var \Civi\OAuthLogin\LoginAction\AbstractLoginAction
   */
  private $loginActionClass;

  public function preProcess()
  {
    $this->snippet = CRM_Utils_Request::retrieve('snippet', 'String');
    $type = CRM_Utils_Request::retrieve('type', 'String');
    if ($this->snippet && $type) {
      $this->assign('suppressForm', TRUE);
      $this->controller->_generateQFKey = FALSE;
    }
    $this->assign('snippet', $this->snippet);

    $this->id = CRM_Utils_Request::retrieve('id', 'Integer');
    $this->assign('id', $this->id);

    if ($this->id) {
      $this->loginAction = OAuthLoginAction::get(FALSE)
        ->addWhere('id', '=', $this->id)
        ->execute()
        ->single();
    }
    $this->assign('login_action', $this->loginAction);

    /**
     * @var \Civi\OAuthLogin\LoginAction\Factory $factory;
     */
    $factory = \Civi::service('civi.oauthlogin.loginactions');

    if (!$type && $this->loginAction && isset($this->loginAction['type'])) {
      $type = $this->loginAction['type'];
    }
    $this->assign('has_configuration', FALSE);
    if ($type) {
      $this->loginActionClass = $factory->getLoginAction($type);
      if ($this->loginActionClass->hasConfiguration()) {
        $this->assign('has_configuration', TRUE);
        $this->assign('configuration_template', $this->loginActionClass->getConfigurationTemplateFileName());
      }
    }

    $title = E::ts('OAuth Login Action');
    CRM_Utils_System::setTitle($title);

    return parent::preProcess();
  }

  public function buildQuickForm()
  {
    $this->add('hidden', 'id');
    if ($this->_action != CRM_Core_Action::DELETE) {
      $this->add('select', 'client_id', E::ts('Restrict to client'), $this->getClients(), FALSE, ['class' => 'huge crm-select2','placeholder' => E::ts('- All Clients -'),]);
      $this->add('text', 'label', E::ts('Label'), ['class' => 'huge']);
      $this->add('select', 'type', E::ts('Type'), $this->getTypes(), TRUE, ['class' => 'huge crm-select2','placeholder' => E::ts('- select -'),]);
      $this->addYesNo('is_active', E::ts('Is active'), FALSE, TRUE);

      if ($this->loginActionClass && $this->loginActionClass->hasConfiguration()) {
        $this->loginActionClass->buildConfigurationForm($this, $this->loginAction);
      }

      $this->addButtons(array(
        array('type' => 'next', 'name' => E::ts('Save'), 'isDefault' => TRUE,),
        array('type' => 'cancel', 'name' => E::ts('Cancel'))));


    } else {
      $this->addButtons(array(
        array('type' => 'next', 'name' => E::ts('Delete'), 'isDefault' => TRUE,),
        array('type' => 'cancel', 'name' => E::ts('Cancel'))));

    }
    
    return parent::buildQuickForm();
  }

  function setDefaultValues() {
    $defaults = [];
    $defaults['id'] = $this->id;

    if ($this->loginAction) {
      if (isset($this->loginAction['type'])) {
        $defaults['type'] = $this->loginAction['type'];
      }
      if (isset($this->loginAction['label'])) {
        $defaults['label'] = $this->loginAction['label'];
      }
      if (isset($this->loginAction['client_id'])) {
        $defaults['client_id'] = $this->loginAction['client_id'];
      }
    }
    if ($this->_action == CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
    } elseif (isset($this->loginAction['is_active'])) {
      $defaults['is_active'] = $this->loginAction['is_active'] ? '1' : '0';
    }
    return $defaults;
  }


  public function postProcess()
  {
    $session = CRM_Core_Session::singleton();
    $redirectUrl = CRM_Utils_System::url('civicrm/admin/oauth/oauthloginactions', ['reset' => 1]);
    if ($this->_action == CRM_Core_Action::DELETE) {
      OAuthLoginAction::delete(FALSE)
        ->addWhere('id', '=', $this->id)
        ->execute();
      $session->setStatus(E::ts('OAuth Login Action removed'), E::ts('Removed'), 'success');
      CRM_Utils_System::redirect($redirectUrl);
    }

    $values = $this->exportValues();
    $params['label'] = $values['label'];
    $params['type'] = $values['type'];
    $params['client_id'] = $values['client_id'] ?? NULL;
    $params['is_active'] = !empty($values['is_active']);
    $params['configuration'] = NULL;
    if ($this->loginActionClass && $this->loginActionClass->hasConfiguration()) {
      $params['configuration'] = $this->loginActionClass->processConfiguration($values);
    }

    if ($this->id) {
      OAuthLoginAction::update(FALSE)
        ->setValues($params)
        ->addWhere('id', '=', $this->id)
        ->execute();
    } else {
      OAuthLoginAction::create(FALSE)
        ->setValues($params)
        ->execute();
    }
    if (empty($this->snippet)) {
      CRM_Utils_System::redirect($redirectUrl);
    }
    parent::postProcess();

  }

  private function getTypes(): array {
    /**
     * @var \Civi\OAuthLogin\LoginAction\Factory $factory;
     */
    $factory = \Civi::service('civi.oauthlogin.loginactions');
    return $factory->getLoginActionTitles();
  }

  private function getClients(): array {
    $providers = OAuthProvider::get(FALSE)
      ->addWhere('tags', 'CONTAINS', 'Login')
      ->execute();
    $result = [];
    foreach($providers as $provider) {
      $clients = OAuthClient::get(FALSE)
        ->addWhere('provider', '=', $provider['name'])
        ->execute();
      foreach($clients as $client) {
        $result[$client['id']] = E::ts('OAuth Client %1 (%2)', [1=>$client['id'], 2=>$provider['title']]);
      }  
    }
    return $result;
  }

}