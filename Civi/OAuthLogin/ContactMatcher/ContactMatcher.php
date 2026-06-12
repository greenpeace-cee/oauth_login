<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\ContactMatcher;

use Civi\OAuthLogin\IdToken;

abstract class ContactMatcher {

  protected array $config = [];

  /**
   * Match a contact.
   * 
   * @param IdToken $idToken
   *   The user ID token coming from the OAuth Provider
   *   The IdToke class is ouw facade around the above.
   *   Making getting data easier.
   * 
   *   When NULL is returned no user is created.
   * 
   * @return int 
   *  Returns the Contact ID. Return NULL when creating a contact failed.
   */
  abstract public function findOrCreate(IdToken $idToken):? int;

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
  abstract public function update(int $existingContactId, IdToken $idToken): int;

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
    // Do nothing
  }

  /**
   * Returns the transalted title of this matcher
   */
  abstract public function getTitleForCreate(): string;

  /**
   * Returns the transalted title of this matcher
   */
  abstract public function getTitleForUpdate(): string;

  public function setConfig(array $config) {
    $this->config;
  }

  public function getName(): string {
    $name = str_replace('\\', '.', get_class($this));
    return strtolower($name);
  }

}