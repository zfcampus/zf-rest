<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
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

        // Get actual argument...
        if ($params instanceof \ArrayObject) {
            $params = $params->getArrayCopy();
        }
        if (1 === count($params)) {
            $params = array_pop($params);
        }

        $this->testCase->paramsPassedToListener  = $params;
    }
}
