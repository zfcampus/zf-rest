<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Listener;

use ZF\Rest\Listener\RestParametersListener;
use ZF\Rest\Resource;
use ZF\Rest\RestController;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Parameters;

/**
 * @subpackage UnitTest
 */
class RestParametersListenerTest extends TestCase
{
    public function setUp()
    {
        $this->resource   = $resource   = new Resource();
        $this->controller = $controller = new RestController();
        $controller->setResource($resource);

        $this->matches    = $matches    = new RouteMatch(array());
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
        $controller = $this->getMock('Zend\Mvc\Controller\AbstractRestfulController');
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
}
