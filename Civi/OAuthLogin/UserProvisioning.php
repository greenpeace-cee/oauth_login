<?php

namespace Civi\OAuthLogin;

use Civi\Api4\Contact;
use Civi\Api4\User;
use Civi\OAuthLogin\ConfigProvider;

class UserProvisioning {

  private ConfigProvider $config;

  public function __construct()
  {
    $this->config = new ConfigProvider();
  }

  public function createUser(IdToken $idToken):? array {
    /** @var Service */
    $service = \Civi::service('civi.oauthlogin');
    $matcher = $service->getContactCreateMatcher();
    $contactId = NULL;
    if ($matcher) {
      $contactId = $matcher->findOrCreate($idToken);
    }
    if (!empty($contactId)) {
      $username = $idToken->getUsername();
      try {
        $user = \Civi\Api4\User::create(FALSE)
          ->addValue('username', $username)
          ->addValue('uf_name', $username)
          ->addValue('contact_id', $contactId)
          ->addValue('is_active', TRUE)
          ->execute()
          ->first();
        
        \Civi::log()->debug('OAuth Login: Created new user', ['user_id' => $user['id']]);

        $user['user_id'] = $user['id'];
        $user['is_active'] = TRUE;
        $user['subject'] = '';
        $user = $this->linkUserToOAuthIdentity($user, $idToken);
        $this->assignDefaultRoles((int) $user['user_id']);
        return $user;
      }
      catch (\Throwable $e) {
        $matcher->revert($idToken, $contactId);
      }
    }
    return NULL;
  }

  public function updateUser(IdToken $idToken, array $user):? array {
    /** @var Service */
    $service = \Civi::service('civi.oauthlogin');
    $contactId = NULL;
    $username = $idToken->getUsername();
    if (empty($user['contact_id']) || !$this->doesContactExists($user['contact_id'])) {
      $matcher = $service->getContactCreateMatcher();
      if ($matcher) {
        $contactId = $matcher->findOrCreate($idToken);
      }
    } else {
      $matcher = $service->getContactUpdateMatcher();
      if ($matcher) {
        $contactId = $matcher->update($user['contact_id'], $idToken);
      }
      if (empty($contactId)) {
        $matcher = $service->getContactCreateMatcher();
        if ($matcher) {
          $contactId = $matcher->findOrCreate($idToken);
        }
      }
    }
    if (!empty($contactId)) {
      try {
        User::update(FALSE)
          ->addValue('username', $username)
          ->addValue('uf_name', $username)
          ->addValue('contact_id', $contactId)
          ->addWhere('id', '=', $user['id'])
          ->execute();
        $user['contact_id'] = $contactId;
        return $user;
      } catch (\Exception $e) { }
    }
    return NULL;
  }

  public function findExistingUser(IdToken $token):? array {
    $existingIdentity = \Civi\Api4\UserOAuthIdentity::get(FALSE)
      ->addSelect('user_id', 'subject')
      ->addWhere('client_id', '=', $token->getClientId())
      ->addWhere('subject', '=', $token->getSubject())
      ->execute()
      ->first();
    if (!empty($existingIdentity['user_id'])) {
      $user = User::get(FALSE)
        ->addWhere('id', '=', $existingIdentity['user_id'])
        ->execute()
        ->first();
      $user['subject'] = $existingIdentity['subject'];
      return $user;
    }

    // no existing link found, look up active users by email
    $oauthIdentity = \Civi\Api4\User::get(FALSE)
      ->addSelect('id', 'is_active', 'contact_id')
      ->addWhere('uf_name', '=', $token->getEmail())
      ->execute()
      ->first();

    if (!empty($oauthIdentity)) {
      $oauthIdentity = $this->linkUserToOAuthIdentity($oauthIdentity, $token);
      $oauthIdentity = User::get(FALSE)
        ->addWhere('id', '=', $oauthIdentity['id'])
        ->execute()
        ->first();
      $user['subject'] = $oauthIdentity['subject'];
      return $user;
    }
    

    return NULL;
  }

  private function doesContactExists(int $contactId): bool {
    try {
      $contact = Contact::get(FALSE)
        ->addWhere('is_deleted', '=', FALSE)
        ->addWhere('id', '=', $contactId)
        ->execute()
        ->first();
      return !empty($contact);
    } catch (\Exception $e) { }
    return FALSE;
  }

  private function assignDefaultRoles(int $userId): void {
    $names = $this->config->defaultRoles();
    if ($names === []) {
      return;
    }
    $available = \Civi\Api4\Role::get(FALSE)
      ->addSelect('id', 'name')
      ->execute()
      ->indexBy('name');

    foreach ($names as $name) {
      if (!isset($available[$name])) {
        \Civi::log()->warning('OAuth Login: Default role not found in CiviCRM', ['role' => $name]);
        continue;
      }
      \Civi\Api4\UserRole::create(FALSE)
        ->addValue('user_id', $userId)
        ->addValue('role_id', $available[$name]['id'])
        ->execute();
      \Civi::log()->debug('OAuth Login: Assigned default role', ['user_id' => $userId, 'role' => $name]);
    }
  }

  private function linkUserToOAuthIdentity(array $user, IdToken $token): array {
    \Civi\Api4\UserOAuthIdentity::create(FALSE)
        ->addValue('user_id', $user['id'])
        ->addValue('client_id', $token->getClientId())
        ->addValue('subject', $token->getSubject())
        ->execute();
    $user['subject'] = $token->getSubject();
    return $user;
  }

}