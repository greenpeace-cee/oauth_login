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

class BasicCreateContactMatcher extends CreateContactMatcher {

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
   * Match a contact.
   * 
   * @param IdToken $idToken
   *   The user ID token coming from the OAuth Provider
   *   The IdToke class is ouw facade around the above.
   *   Making getting data easier.
   * 
   * @return int 
   *  Returns the Contact ID
   */
  public function findOrCreate(IdToken $idToken):? int {
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
      $contactCreate = Contact::create(FALSE)
        ->addValue('contact_type', 'Individual');
      if ($firstName !== NULL) {
        $contactCreate->addValue('first_name', $firstName);
      }
      if ($middleName !== NULL) {
        $contactCreate->addValue('middle_name', $middleName);
      }
      if ($lastName !== NULL) {
        $contactCreate->addValue('last_name', $lastName);
      }
      if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactCreate->addChain('email', Email::create(FALSE)
          ->addValue('contact_id', '$id')
          ->addValue('email', $email)
          ->addValue('is_primary', TRUE));
      }
      $contact = $contactCreate->execute()->first();
      return $contact['id'];
    } catch (\Exception $e) {
      // Do nothing;
    }
    return NULL;
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
    return E::ts('Create contact with email (and optional name)');
  }

}