<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZF\Rest\Exception;

/**
 * Throw this exception from a "update" resource listener in order to indicate
 * an inability to update an item and automatically report it.
 */
class UpdateException extends DomainException
{
}
