<?php

namespace Civi\OAuth\Login;

class IdToken {
  private array $tokenRecord;
  private array $validationMessages = [];

  public function __construct(array $tokenRecord) {
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
}