<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\Stdlib\Parameters;
use ZF\Rest\Resource;
use ZF\Rest\ResourceEvent;

/**
 * @subpackage UnitTest
 */
class AbstractResourceListenerTest extends TestCase
{
    public $methodInvokedInListener;
    public $paramsPassedToListener;

    public function setUp()
    {
        $this->methodInvokedInListener = null;
        $this->paramsPassedToListener  = null;

        $this->resource = new Resource();
        $this->events   = $events = new EventManager();
        $this->resource->setEventManager($events);

        $this->listener = new TestAsset\TestResourceListener($this);
        $events->attach($this->listener);
    }

    public function events()
    {
        // Casting arrays to objects when the associated Resource method will
        // cast to object.
        return [
            'create'      => ['create', ['data' => (object) ['foo' => 'bar']]],
            'update'      => ['update', ['id' => 'identifier', 'data' => (object) ['foo' => 'bar']]],
            'replaceList' => ['replaceList', ['data' => [(object) ['foo' => 'bar']]]],
            'patchList'   => ['patchList', ['data' => [(object) ['foo' => 'bar']]]],
            'patch'       => ['patch', ['id' => 'identifier', 'data' => (object) ['foo' => 'bar']]],
            'delete'      => ['delete', ['id' => 'identifier']],
            'deleteList'  => ['deleteList', ['data' => ['foo' => 'bar']]],
            'fetch'       => ['fetch', ['id' => 'identifier']],
            'fetchAll'    => ['fetchAll', []],
        ];
    }

    /**
     * @dataProvider events
     */
    public function testResourceMethodsAreInvokedWhenEventsAreTriggered($method, $eventArgs)
    {
        $this->methodInvokedInListener = null;
        $this->paramsPassedToListener  = null;

        switch ($method) {
            case 'create':
                $this->resource->create($eventArgs['data']);
                break;
            case 'update':
                $this->resource->update($eventArgs['id'], $eventArgs['data']);
                break;
            case 'replaceList':
                $this->resource->replaceList($eventArgs['data']);
                break;
            case 'patch':
                $this->resource->patch($eventArgs['id'], $eventArgs['data']);
                break;
            case 'patchList':
                $this->resource->patchList($eventArgs['data']);
                break;
            case 'delete':
                $this->resource->delete($eventArgs['id']);
                break;
            case 'deleteList':
                $this->resource->deleteList($eventArgs['data']);
                break;
            case 'fetch':
                $this->resource->fetch($eventArgs['id']);
                break;
            case 'fetchAll':
                $this->resource->fetchAll($eventArgs);
                break;
        }

        $expectedMethod = get_class($this->listener) . '::' . $method;
        $expectedParams = array_values($eventArgs);

        if ($method == 'patchList') {
            $expectedParams = $this->paramsPassedToListener;
        }

        $this->assertEquals($expectedMethod, $this->methodInvokedInListener);
        $this->assertEquals(
            $expectedParams,
            $this->paramsPassedToListener,
            var_export($this->paramsPassedToListener, 1)
        );
    }

    /**
     * @group 7
     */
    public function testDispatchShouldPassWhitelistedQueryParamsToFetchAllMethod()
    {
        $queryParams = new Parameters(['foo' => 'bar']);
        $event = new ResourceEvent();
        $event->setName('fetchAll');
        $event->setQueryParams($queryParams);

        $this->listener->dispatch($event);

        $this->assertEquals($queryParams, $this->listener->testCase->paramsPassedToListener);
    }
}
