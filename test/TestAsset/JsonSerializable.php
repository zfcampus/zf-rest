<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\TestAsset;

use JsonSerializable as JsonSerializableInterface;

/**
 * @subpackage UnitTest
 */
class JsonSerializable implements JsonSerializableInterface
{
    public function jsonSerialize()
    {
        return array('foo' => 'bar');
    }
}
