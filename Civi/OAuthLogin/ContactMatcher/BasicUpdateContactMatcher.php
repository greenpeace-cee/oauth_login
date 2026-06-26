<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\ContactMatcher;

use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\OAuthLogin\IdToken;
use CRM_OAuthLogin_ExtensionUtil as E;

class BasicUpdateContactMatcher extends UpdateContactMatcher {

  protected function requiredClaims(): array {
    return [
      [
        'claim' => 'firstname',
        'label' => E::ts('First name Claim'),
        'required' => FALSE,
        'default' => 'given_name',
        'default_token' => 'idToken',
      ],
      [
        'claim' => 'middlename',
        'label' => E::ts('Middle name Claim'),
        'required' => FALSE,
        'default' => 'middle_name',
        'default_token' => 'idToken',
      ],
      [
        'claim' => 'lastname',
        'label' => E::ts('Last Name Claim'),
        'required' => FALSE,
        'default' => 'family_name',
        'default_token' => 'idToken',
      ],
    ];
  }

  /**
   * Update an existing contact upon login.
   * 
   * @param $existingContactId
   *   The existing contact ID of the user
   * @param IdToken $idToken
   *   The user ID token coming from the OAuth Provider
   *   The IdToke class is ouw facade around the above.
   *   Making getting data easier.
   * 
   * @return int 
   *  Returns the Contact ID
   */
  public function update(int $existingContactId, IdToken $idToken): int {
    $firstName = NULL;
    $lastName = NULL;
    $middleName = NULL;
    if (!empty($this->configuation['firstname'])) {
      $firstName = $idToken->getClaim($this->configuation['firstname']);
    }
    if (!empty($this->configuation['lastname'])) {
      $lastName = $idToken->getClaim($this->configuation['lastname']);
    }
    if (!empty($this->configuation['middlename'])) {
      $middleName = $idToken->getClaim($this->configuation['middlename']);
    }
    $email = $idToken->getEmail();
    // Contact first — User.contact_id FK needs it to exist.
    try {
      $contactCreate = Contact::update(FALSE);
      $contactCreate->addWhere('id', '=', $existingContactId);
      $doContactUpdate = false;
      if ($firstName !== NULL) {
        $doContactUpdate = true;
        $contactCreate->addValue('first_name', $firstName);
      }
      if ($lastName !== NULL) {
        $doContactUpdate = true;
        $contactCreate->addValue('last_name', $lastName);
      }
      if ($middleName !== NULL) {
        $doContactUpdate = true;
        $contactCreate->addValue('middle_name', $middleName);
      }
      if ($doContactUpdate) {
        try {
          $contactCreate->execute();
        } catch (\Exception $e) { }
      }

      if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $existingEmail = [];
        try {
          $existingEmail = Email::get(FALSE)
            ->addWhere('is_primary', '=', true)
            ->addWhere('contact_id', '=', $existingContactId)
            ->execute()
            ->first();
        } catch (\Exception $e ) {} 
        if (!empty($existingEmail['id'])) {
          Email::update(FALSE)
            ->addValue('email', $email)
            ->addWhere('id', '=', $existingEmail['id'])
            ->execute();
        } else {  
          Email::create(FALSE)
            ->addValue('contact_id', $existingContactId)
            ->addValue('is_primary', true)
            ->addValue('email', $email)
            ->execute();
        }
      }
    } catch (\Exception $e) {
      // Do nothing;
    }
    return $existingContactId;
  }

  /**
   * Reverts the contact creation because something went wrong when 
   * creating the user record.
   * 
   * @param IdToken $idToken
   *   The user ID token coming from the OAuth Provider
   *   The IdToke class is ouw facade around the above.
   *   Making getting data easier.
   * @param int $contactId
   */
  public function revert(IdToken $idToken, int $contactId) {
    // Clean up the orphan Contact + Email rows we just created. Anything
    // beyond the User INSERT failing leaves a dangling person record on
    // the contact list otherwise.
    \Civi\Api4\Email::delete(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->execute();
    \Civi\Api4\Contact::delete(FALSE)
      ->addWhere('id', '=', $contactId)
      ->setUseTrash(FALSE)
      ->execute();
  }

  /**
   * Returns the transalted title of this matcher
   */
  public function getTitle(): string {
    return E::ts('Update contact name and email');
  }
}