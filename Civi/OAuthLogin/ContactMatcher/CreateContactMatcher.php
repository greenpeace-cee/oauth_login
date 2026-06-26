<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\ContactMatcher;

use Civi\OAuthLogin\IdToken;

abstract class CreateContactMatcher extends ContactMatcher {

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

}