<?php

namespace Civi\OAuthLogin;

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
    $matcher = $service->getContactMatcher();
    $contactId = NULL;
    if ($matcher) {
      $contactId = $matcher->match($idToken);
    }
    if (!empty($contactId)) {
      $username = $idToken->getEmail();
      $ufName = $idToken->getEmail();
      try {
        $user = \Civi\Api4\User::create(FALSE)
          ->addValue('username', $username)
          ->addValue('uf_name', $ufName)
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

  public function findExistingUserId(IdToken $token):? array {
    $existingIdentity = \Civi\Api4\UserOAuthIdentity::get(FALSE)
      ->addSelect('user_id', 'subject', 'user.is_active as is_active')
      ->addWhere('client_id', '=', $token->getClientId())
      ->addWhere('subject', '=', $token->getSubject())
      ->addJoin('User AS user', 'INNER', ['user_id', '=', 'user.id'])
      ->execute()
      ->first();
    if (!empty($existingIdentity['user_id'])) {
      return $existingIdentity;
    }

    // no existing link found, look up active users by email
    $user = \Civi\Api4\User::get(FALSE)
      ->addSelect('id as user_id', 'user_oauth_identity.subject as subject', 'is_active')
      ->addJoin('UserOAuthIdentity AS user_oauth_identity', 'LEFT', ['id', '=', 'user_oauth_identity.user_id'])
      ->addWhere('user_oauth_identity.client_id', '=', $token->getClientId())
      ->addWhere('uf_name', '=', $token->getEmail())
      ->execute()
      ->first();

    if (!empty($user)) {
      if (empty($user['subject'])) {
        $user = $this->linkUserToOAuthIdentity($user, $token);
      }
      return $user;
    }
    

    return NULL;
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
        ->addValue('user_id', $user['user_id'])
        ->addValue('client_id', $token->getClientId())
        ->addValue('subject', $token->getSubject())
        ->execute();
    $user['subject'] = $token->getSubject();
    return $user;
  }

}