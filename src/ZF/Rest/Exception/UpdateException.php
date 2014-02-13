<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest\Exception;

use ZF\ApiProblem\Exception\DomainException;

/**
 * Throw this exception from a "update" resource listener in order to indicate
 * an inability to update an item and automatically report it.
 */
class UpdateException extends DomainException
{
}
