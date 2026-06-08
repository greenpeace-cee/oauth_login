<?php

namespace Civi\OAuth\Login;

use CRM_Utils_System;
use CRM_Core_Session;
use CRM_OauthLogin_ExtensionUtil as E;

class IdToken {


  private array $tokenRecord;

  private array $validationMessages = [];

  private ConfigProvider $config;

  public function __construct(array $tokenRecord) {
    $this->config = new ConfigProvider();
    $this->tokenRecord = $tokenRecord;
  }

  public function validate(): bool {
    $this->validationMessages = [];
    // TODO: check and validate ID token
    return TRUE;
  }

  public function getValidationMessages(): array {
    return $this->validationMessages;
  }

  public function getClientId(): string {
    return $this->tokenRecord['client_id'];
  }

  public function getSubject(): string {
    return $this->tokenRecord['resource_owner']['sub'];
  }

  public function getEmail(): string {
    return $this->tokenRecord['resource_owner']['email'];
  }

  public function getFirstName():? string {
    return NULL;
  }

  public function getLastName():? string {
    return NULL;
  }

  public function login(): void {
    $userProvisioning = new UserProvisioning();
    $user = $userProvisioning->findExistingUserId($this);
    if (empty($user) && $this->config->isProvisioningEnabled()) {
      $user = $userProvisioning->createUser($this);
    } elseif (empty($user)) {
      $this->loginFailed(E::ts('No active user was found for the provided identity. Make sure your email matches the one used by your identity provider.'));
    }
    if (!empty($user)) {
      if (empty($user['subject']) || $user['subject'] != $this->getSubject()) {
        $this->loginFailed(E::ts('User is already linked to a different remote identity of the same identity provider.'));
      }
      authx_login([
        'flow' => 'login',
        'useSession' => TRUE,
        'principal' => [
          'userId' => $user['user_id'],
        ]
      ]);
    }
  }

  private function loginFailed(string $reason) {
    CRM_Core_Session::setStatus($reason, E::ts('Login failed'), 'error');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/login'));
  }
}