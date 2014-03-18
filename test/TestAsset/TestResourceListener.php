<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\TestAsset;

use ZF\Rest\AbstractResourceListener;

class TestResourceListener extends AbstractResourceListener
{
    public $testCase;

    public function __construct($testCase)
    {
        $this->testCase = $testCase;
    }

    public function create($data)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function update($id, $data)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function replaceList($data)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function patch($id, $data)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function patchList($data)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function delete($id)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function deleteList($data)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function fetch($id)
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = func_get_args();
    }

    public function fetchAll($params = array())
    {
        $this->testCase->methodInvokedInListener = __METHOD__;
        $this->testCase->paramsPassedToListener  = $params;
    }
}
