<?php
declare(strict_types = 1);

namespace Civi\Api4;

/**
 * OAuthLoginAction entity.
 *
 * Provided by the OAuth Login extension.
 *
 * @searchable secondary
 * @orderBy weight
 * @package Civi\Api4
 */
class OAuthLoginAction extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;

}
