<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
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
