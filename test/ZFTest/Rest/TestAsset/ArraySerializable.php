<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\TestAsset;

/**
 * @subpackage UnitTest
 */
class ArraySerializable
{
    public function getHijinx()
    {
        return 'should not get this';
    }

    public function getArrayCopy()
    {
        return array('foo' => 'bar');
    }
}
