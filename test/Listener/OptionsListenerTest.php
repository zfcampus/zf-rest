<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Listener;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Request as StdlibRequest;
use ZF\Rest\Listener\OptionsListener;

class OptionsListenerTest extends TestCase
{
    public function testListenerRegistersAtExpectedPriority()
    {
        $listener = new OptionsListener([]);
        $events   = new EventManager();
        $listener->attach($events);
        $listeners = $events->getListeners('route');
        $this->assertEquals(1, count($listeners));
        foreach ($listeners as $test) {
            break;
        }
        $this->assertInstanceOf('Zend\Stdlib\CallbackHandler', $test);
        $this->assertEquals([$listener, 'onRoute'], $test->getCallback());
        $this->assertEquals(-100, $test->getMetadatum('priority'));
    }

    public function seedListenerConfig()
    {
        return [
            'controller-without-config' => [],
            'controller-with-entity-config' => [
                'route_identifier_name' => 'entity_id',
                'entity_http_methods' => [
                    'GET',
                    'PATCH',
                    'DELETE',
                ],
            ],
            'controller-with-collection-config' => [
                'collection_http_methods' => [
                    'GET',
                    'POST',
                ],
            ],
            'controller-with-all-config' => [
                'route_identifier_name' => 'entity_id',
                'entity_http_methods' => [
                    'GET',
                    'PATCH',
                    'DELETE',
                ],
                'collection_http_methods' => [
                    'GET',
                    'POST',
                ],
            ],
            'controller-with-all-config-except-entity-id' => [
                'entity_http_methods' => [
                    'GET',
                    'PATCH',
                    'DELETE',
                ],
                'collection_http_methods' => [
                    'GET',
                    'POST',
                ],
            ],
        ];
    }

    public function validMethodsProvider()
    {
        return [
            'collection-get' => ['GET', [
                'controller' => 'controller-with-collection-config',
            ]],
            'collection-post' => ['POST', [
                'controller' => 'controller-with-collection-config',
            ]],
            'entity-get' => ['GET', [
                'controller' => 'controller-with-entity-config',
                'entity_id'  => 'foo',
            ]],
            'entity-patch' => ['PATCH', [
                'controller' => 'controller-with-entity-config',
                'entity_id'  => 'foo',
            ]],
            'entity-delete' => ['DELETE', [
                'controller' => 'controller-with-entity-config',
                'entity_id'  => 'foo',
            ]],
            'all-collection-get' => ['GET', [
                'controller' => 'controller-with-all-config',
            ]],
            'all-collection-post' => ['POST', [
                'controller' => 'controller-with-all-config',
            ]],
            'all-entity-get' => ['GET', [
                'controller' => 'controller-with-all-config',
                'entity_id'  => 'foo',
            ]],
            'all-entity-patch' => ['PATCH', [
                'controller' => 'controller-with-all-config',
                'entity_id'  => 'foo',
            ]],
            'all-entity-delete' => ['DELETE', [
                'controller' => 'controller-with-all-config',
                'entity_id'  => 'foo',
            ]],
            'all-except-collection-get' => ['GET', [
                'controller' => 'controller-with-all-config-except-entity-id',
            ]],
            'all-except-collection-post' => ['POST', [
                'controller' => 'controller-with-all-config-except-entity-id',
            ]],
        ];
    }

    public function invalidMethodsProvider()
    {
        return [
            'collection-patch' => ['PATCH', [
                'controller' => 'controller-with-collection-config',
            ], ['GET', 'POST']],
            'collection-put' => ['PUT', [
                'controller' => 'controller-with-collection-config',
            ], ['GET', 'POST']],
            'collection-delete' => ['DELETE', [
                'controller' => 'controller-with-collection-config',
            ], ['GET', 'POST']],
            'entity-post' => ['POST', [
                'controller' => 'controller-with-entity-config',
                'entity_id'  => 'foo',
            ], ['GET', 'PATCH', 'DELETE']],
            'entity-put' => ['PUT', [
                'controller' => 'controller-with-entity-config',
                'entity_id'  => 'foo',
            ], ['GET', 'PATCH', 'DELETE']],
            'all-collection-patch' => ['PATCH', [
                'controller' => 'controller-with-all-config',
            ], ['GET', 'POST']],
            'all-collection-put' => ['PUT', [
                'controller' => 'controller-with-all-config',
            ], ['GET', 'POST']],
            'all-collection-delete' => ['DELETE', [
                'controller' => 'controller-with-all-config',
            ], ['GET', 'POST']],
            'all-entity-post' => ['POST', [
                'controller' => 'controller-with-all-config',
                'entity_id'  => 'foo',
            ], ['GET', 'PATCH', 'DELETE']],
            'all-entity-put' => ['PUT', [
                'controller' => 'controller-with-all-config',
                'entity_id'  => 'foo',
            ], ['GET', 'PATCH', 'DELETE']],
            'except-collection-patch' => ['PATCH', [
                'controller' => 'controller-with-all-config-except-entity-id',
            ], ['GET', 'POST']],
            'except-collection-put' => ['PUT', [
                'controller' => 'controller-with-all-config-except-entity-id',
            ], ['GET', 'POST']],
            'except-collection-delete' => ['DELETE', [
                'controller' => 'controller-with-all-config-except-entity-id',
            ], ['GET', 'POST']],
        ];
    }

    /**
     * @dataProvider validMethodsProvider
     */
    public function testListenerReturnsNullWhenMethodIsAllowedForCurrentRequest($method, $matchParams)
    {
        $listener = new OptionsListener($this->seedListenerConfig());
        $request  = new Request();
        $request->setMethod($method);
        $matches  = new RouteMatch($matchParams);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $mvcEvent->setRouteMatch($matches);
        $mvcEvent->setResponse(new Response());

        $this->assertNull($listener->onRoute($mvcEvent));
    }

    /**
     * @dataProvider invalidMethodsProvider
     */
    public function testListenerReturnsNullIfNotAnHttpRequest($method, $matchParams)
    {
        $listener = new OptionsListener($this->seedListenerConfig());
        $request  = new StdlibRequest();
        $matches  = new RouteMatch($matchParams);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $mvcEvent->setRouteMatch($matches);
        $mvcEvent->setResponse(new Response());

        $this->assertNull($listener->onRoute($mvcEvent));
    }

    /**
     * @dataProvider invalidMethodsProvider
     */
    public function testListenerReturnsNullIfNoRouteMatches($method, $matchParams, $expectedAllow)
    {
        $listener = new OptionsListener($this->seedListenerConfig());
        $request  = new Request();
        $request->setMethod($method);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $mvcEvent->setResponse(new Response());

        $this->assertNull($listener->onRoute($mvcEvent));
    }

    public function testListenerReturnsNullIfNoMatchingControllerInRouteMatches()
    {
        $listener = new OptionsListener($this->seedListenerConfig());
        $request  = new Request();
        $request->setMethod('GET');
        $matches  = new RouteMatch([]);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $mvcEvent->setResponse(new Response());

        $this->assertNull($listener->onRoute($mvcEvent));
    }

    public function testListenerReturnsNullIfMatchingControllerInRouteMatchesButNoConfigForController()
    {
        $listener = new OptionsListener($this->seedListenerConfig());
        $request  = new Request('GET');
        $matches  = new RouteMatch([
            'controller' => 'controller-without-config',
        ]);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);

        $this->assertNull($listener->onRoute($mvcEvent));
    }

    /**
     * @dataProvider invalidMethodsProvider
     */
    public function testListenerReturns405ResponseWithAllowHeaderForInvalidRequestMethod(
        $method,
        $matchParams,
        $expectedAllow
    ) {
        $listener = new OptionsListener($this->seedListenerConfig());
        $request  = new Request();
        $request->setMethod($method);
        $matches  = new RouteMatch($matchParams);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $mvcEvent->setRouteMatch($matches);
        $mvcEvent->setResponse(new Response());

        $result = $listener->onRoute($mvcEvent);
        $this->assertInstanceOf('Zend\Http\Response', $result);
        $this->assertEquals(405, $result->getStatusCode());
        $headers = $result->getHeaders();
        $this->assertTrue($headers->has('Allow'));
        $allow = $headers->get('Allow');
        $allow = $allow->getFieldValue();
        $allow = explode(',', $allow);
        array_walk($allow, function (&$value) {
            $value = trim($value);
        });
        sort($allow);
        sort($expectedAllow);
        $this->assertEquals($expectedAllow, $allow);
    }
}
