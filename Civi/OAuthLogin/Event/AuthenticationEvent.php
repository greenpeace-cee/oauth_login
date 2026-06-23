<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\Event;

use Civi\Core\Event\GenericHookEvent;
use Civi\OAuthLogin\IdToken;

class AuthenticationEvent extends GenericHookEvent {

  public int $userId;

  public int $contactId;

  public IdToken $idToken;

  public bool $isNewUser = false;

  public function __construct(IdToken $idToken, int $userId, int $contactId, bool $isNewUser)
  {
    $this->idToken = $idToken;
    $this->userId = $userId;
    $this->contactId = $contactId;
    $this->isNewUser = $isNewUser;
  }

}