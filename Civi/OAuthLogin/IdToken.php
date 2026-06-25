<?php

namespace Civi\OAuthLogin;

use League\OAuth2\Client\Token\AccessToken;

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

  public function getUsername(): string {
    return $this->tokenRecord['resource_owner']['preferred_username'];
  }

  public function getEmail(): string {
    return $this->tokenRecord['resource_owner']['email'];
  }

  public function getFirstName():? string {
    return $this->tokenRecord['resource_owner']['given_name'] ?? NULL;
  }

  public function getMiddleName():? string {
    return $this->tokenRecord['resource_owner']['middle_name'] ?? NULL;
  }

  public function getLastName():? string {
    return $this->tokenRecord['resource_owner']['family_name'] ?? NULL;
  }

  public function getTokenRecord(): array {
    return $this->tokenRecord;
  }

  public function getClaim(array $claim): mixed {
    $token = $claim['token'] ?? 'idToken';
    $strClaim = $claim['claim'] ?? 'sub';
    if ($token == 'accessToken') {
      return static::getSubValueOfClaim($strClaim, $this->tokenRecord['raw']);
    } else {
      return static::getSubValueOfClaim($strClaim, $this->tokenRecord['resource_owner']);
    }
    return NULL;
  }

  /**
   * If claim is realm_access.roles 
   * and in data this is $data[realm_access][roles] =...
   * then that value will be return.
   */
  private static function getSubValueOfClaim(string $claim, array $data) {
    [$firstClaim, $nextClaim] = explode(".", $claim, 2);
    if (array_key_exists($firstClaim, $data)) {
      if (!strlen($nextClaim)) {
        return $data[$firstClaim];
      } elseif (strlen($nextClaim) && is_array($data[$firstClaim])) {
        return static::getSubValueOfClaim($nextClaim, $data[$firstClaim]);
      }
    }
    if (array_key_exists($claim, $data)) {
      return $data[$claim];
    }
    return NULL;
  }

}