<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Listener;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\SharedEventManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use ZF\Rest\Listener\RestParametersListener;
use ZF\Rest\Resource;
use ZF\Rest\RestController;
use ZFTest\Rest\RouteMatchFactoryTrait;

/**
 * @subpackage UnitTest
 */
class RestParametersListenerTest extends TestCase
{
    use RouteMatchFactoryTrait;

    public function setUp()
    {
        $this->resource   = $resource   = new Resource();
        $this->controller = $controller = new RestController();
        $controller->setResource($resource);

        $this->matches    = $matches    = $this->createRouteMatch([]);
        $this->query      = $query      = new Parameters();
        $this->request    = $request    = new Request();
        $request->setQuery($query);

        $this->event    = new MvcEvent();
        $this->event->setTarget($controller);
        $this->event->setRouteMatch($matches);
        $this->event->setRequest($request);

        $this->listener = new RestParametersListener();
    }

    public function testIgnoresNonRestControllers()
    {
        $controller = $this->getMockBuilder(AbstractRestfulController::class)->getMock();
        $this->event->setTarget($controller);
        $this->listener->onDispatch($this->event);
        $this->assertNull($this->resource->getRouteMatch());
        $this->assertNull($this->resource->getQueryParams());
    }

    public function testInjectsRouteMatchOnDispatchOfRestController()
    {
        $this->listener->onDispatch($this->event);
        $this->assertSame($this->matches, $this->resource->getRouteMatch());
    }

    public function testInjectsQueryParamsOnDispatchOfRestController()
    {
        $this->listener->onDispatch($this->event);
        $this->assertSame($this->query, $this->resource->getQueryParams());
    }

    public function testAttachSharedAttachOneListenerOnEventDispatch()
    {
        $sharedEventManager = new SharedEventManager();
        $this->listener->attachShared($sharedEventManager);

        // Vary identifiers based on zend-eventmanager version
        $identifiers = method_exists($sharedEventManager, 'getEvents')
            ? RestController::class
            : [RestController::class];
        $listeners = $sharedEventManager->getListeners($identifiers, MvcEvent::EVENT_DISPATCH);

        $this->assertEquals(1, count($listeners));
    }

    public function testDetachSharedDetachAttachedListener()
    {
        $sharedEventManager = new SharedEventManager();
        $this->listener->attachShared($sharedEventManager);

        $this->listener->detachShared($sharedEventManager);

        // Vary identifiers based on zend-eventmanager version
        $identifiers = method_exists($sharedEventManager, 'getEvents')
            ? RestController::class
            : [RestController::class];
        $listeners = $sharedEventManager->getListeners($identifiers, MvcEvent::EVENT_DISPATCH);

        $this->assertEquals(0, count($listeners));
    }
}
