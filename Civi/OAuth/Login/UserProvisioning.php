<?php

namespace Civi\OAuth\Login;

class UserProvisioning {

  private ConfigProvider $config;

  public function __construct()
  {
    $this->config = new ConfigProvider();
  }

  public function createUser(IdToken $token):? array {
    $firstName = $token->getFirstName();
    $lastName = $token->getLastName();
    $email = $token->getEmail();
    // Contact first — User.contact_id FK needs it to exist.
    $contactCreate = \Civi\Api4\Contact::create(FALSE)
      ->addValue('contact_type', 'Individual');
    if ($firstName !== NULL) {
      $contactCreate->addValue('first_name', $firstName);
    }
    if ($lastName !== NULL) {
      $contactCreate->addValue('last_name', $lastName);
    }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $contactCreate->addChain('email', \Civi\Api4\Email::create(FALSE)
        ->addValue('contact_id', '$id')
        ->addValue('email', $email)
        ->addValue('is_primary', TRUE));
    }
    $contact = $contactCreate->execute()->first();

    $username = $token->getEmail();
    $ufName = $token->getEmail();
    try {
      $user = \Civi\Api4\User::create(FALSE)
        ->addValue('username', $username)
        ->addValue('uf_name', $ufName)
        ->addValue('contact_id', $contact['id'])
        ->addValue('is_active', TRUE)
        ->execute()
        ->first();
      
      \Civi::log()->debug('OAuth Login: Created new user', ['user_id' => $user['id']]);

      $user['user_id'] = $user['id'];
      $user['is_active'] = TRUE;
      $user['subject'] = '';
      $user = $this->linkUserToOAuthIdentity($user, $token);
      $this->assignDefaultRoles((int) $user['user_id']);
      return $user;

    }
    catch (\Throwable $e) {
      // Clean up the orphan Contact + Email rows we just created. Anything
      // beyond the User INSERT failing leaves a dangling person record on
      // the contact list otherwise.
      \Civi\Api4\Email::delete(FALSE)
        ->addWhere('contact_id', '=', $contact['id'])
        ->execute();
      \Civi\Api4\Contact::delete(FALSE)
        ->addWhere('id', '=', $contact['id'])
        ->setUseTrash(FALSE)
        ->execute();
      return NULL;
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