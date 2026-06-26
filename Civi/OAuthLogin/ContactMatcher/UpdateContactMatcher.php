<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\OAuthLogin\ContactMatcher;

use Civi\OAuthLogin\IdToken;

abstract class UpdateContactMatcher extends ContactMatcher {

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
  
}