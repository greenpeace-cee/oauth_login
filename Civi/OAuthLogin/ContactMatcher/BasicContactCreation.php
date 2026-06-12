<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\ContactMatcher;

use Civi\OAuthLogin\IdToken;
use CRM_OauthLogin_ExtensionUtil as E;

class BasicContactCreation extends ContactMatcher {

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
  public function match(IdToken $idToken):? int {
    $firstName = $idToken->getFirstName();
    $lastName = $idToken->getLastName();
    $email = $idToken->getEmail();
    // Contact first — User.contact_id FK needs it to exist.
    try {
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
    return E::ts('Create a new contact when a user is created');
  }

}