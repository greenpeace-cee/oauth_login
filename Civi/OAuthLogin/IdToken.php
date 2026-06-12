<?php

namespace Civi\OAuthLogin;

/**
 * In OAuthTokenFacade the acces token is converted into an array.
 * The OpenID ID Token is decoded and stored as $tokenRecord['resource_owner']
 * The raw value of the ID token can be found at $tokenRecord['raw']['id_token']
 * 
 * In this class we validate the resource owner with the JWT claims 
 * - iat (Issue at)
 * - exp (Expires at)
 * 
 * This class also holds functionality to login the user. If the user does not exist 
 * to create the user (when configured to do so)
 */
class IdToken {


  public array $tokenRecord;

  public function __construct(array $tokenRecord) {
    $this->tokenRecord = $tokenRecord;
  }

  public function validate(): bool {
    if (isset($this->tokenRecord['resource_owner'])) {
      $resourceOwner = $this->tokenRecord['resource_owner'];
      $leeway = 0;
      $timestamp = \time();
      // Check that this token has been created before 'now'. This prevents
      // using tokens that have been created for later use (and haven't
      // correctly used the nbf claim).
      if (!isset($resourceOwner['nbf']) && isset($resourceOwner['iat']) && floor($resourceOwner['iat']) > ($timestamp + $leeway)) {
        return FALSE;
      }

      // Check if this token has expired.
      if (isset($resourceOwner['exp']) && ($timestamp - $leeway) >= $resourceOwner['exp']) {
        return FALSE;
      }
    }
    return TRUE;
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

}