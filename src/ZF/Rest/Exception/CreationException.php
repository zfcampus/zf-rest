<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZF\Rest\Exception;

/**
 * Throw this exception from a "create" resource listener in order to indicate
 * an inability to create an item and automatically report it.
 */
class CreationException extends DomainException
{
}
