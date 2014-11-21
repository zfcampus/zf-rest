<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use Zend\Http\Request;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\Mvc\Router\Http\TreeRouteStack;
use Zend\View\HelperPluginManager;
use Zend\View\Helper\ServerUrl as ServerUrlHelper;
use Zend\View\Helper\Url as UrlHelper;
use ZF\ApiProblem\View\ApiProblemRenderer;
use ZF\Hal\Collection as HalCollection;
use ZF\Hal\Entity as HalEntity;
use ZF\Hal\Link\Link;
use ZF\Hal\Plugin\Hal as HalHelper;
use ZF\Hal\View\HalJsonModel;
use ZF\Hal\View\HalJsonRenderer;
use ZF\Rest\Resource;
use ZF\Rest\RestController;

/**
 * @subpackage UnitTest
 */
class ChildResourcesIntegrationTest extends TestCase
{
    public function setUp()
    {
        $this->setupRouter();
        $this->setupHelpers();
        $this->setupRenderer();
    }

    public function setupHelpers()
    {
        if (!$this->router) {
            $this->setupRouter();
        }

        $urlHelper = new UrlHelper();
        $urlHelper->setRouter($this->router);

        $serverUrlHelper = new ServerUrlHelper();
        $serverUrlHelper->setScheme('http');
        $serverUrlHelper->setHost('localhost.localdomain');

        $linksHelper = new HalHelper();
        $linksHelper->setUrlHelper($urlHelper);
        $linksHelper->setServerUrlHelper($serverUrlHelper);

        $this->helpers = $helpers = new HelperPluginManager();
        $helpers->setService('url', $urlHelper);
        $helpers->setService('serverUrl', $serverUrlHelper);
        $helpers->setService('hal', $linksHelper);

        $this->plugins = $plugins = new ControllerPluginManager();
        $plugins->setService('hal', $linksHelper);
    }

    public function setupRenderer()
    {
        if (!$this->helpers) {
            $this->setupHelpers();
        }
        $this->renderer = $renderer = new HalJsonRenderer(new ApiProblemRenderer());
        $renderer->setHelperPluginManager($this->helpers);
    }

    public function setupRouter()
    {
        $routes = array(
            'parent' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/api/parent[/:parent]',
                    'defaults' => array(
                        'controller' => 'Api\ParentController',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'child' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/child[/:child]',
                            'defaults' => array(
                                'controller' => 'Api\ChildController',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $this->router = $router = new TreeRouteStack();
        $router->addRoutes($routes);
    }

    public function setUpParentResource()
    {
        $this->parent = (object) array(
            'id'   => 'anakin',
            'name' => 'Anakin Skywalker',
        );
        $resource = new HalEntity($this->parent, 'anakin');

        $link = new Link('self');
        $link->setRoute('parent');
        $link->setRouteParams(array('parent'=> 'anakin'));
        $resource->getLinks()->add($link);

        return $resource;
    }

    public function setUpChildResource($id, $name)
    {
        $this->child = (object) array(
            'id'   => $id,
            'name' => $name,
        );
        $resource = new HalEntity($this->child, $id);

        $link = new Link('self');
        $link->setRoute('parent/child');
        $link->setRouteParams(array('child'=> $id));
        $resource->getLinks()->add($link);

        return $resource;
    }

    public function setUpChildCollection()
    {
        $children = array(
            array('luke', 'Luke Skywalker'),
            array('leia', 'Leia Organa'),
        );
        $this->collection = array();
        foreach ($children as $info) {
            $collection[] = call_user_func_array(array($this, 'setUpChildResource'), $info);
        }
        $collection = new HalCollection($this->collection);
        $collection->setCollectionRoute('parent/child');
        $collection->setEntityRoute('parent/child');
        $collection->setPage(1);
        $collection->setPageSize(10);
        $collection->setCollectionName('child');

        $link = new Link('self');
        $link->setRoute('parent/child');
        $collection->getLinks()->add($link);

        return $collection;
    }

    public function setUpAlternateRouter()
    {
        $routes = array(
            'parent' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/api/parent[/:id]',
                    'defaults' => array(
                        'controller' => 'Api\ParentController',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'child' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/child[/:child_id]',
                            'defaults' => array(
                                'controller' => 'Api\ChildController',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $this->router = $router = new TreeRouteStack();
        $router->addRoutes($routes);
        $this->helpers->get('url')->setRouter($router);
    }

    public function testChildResourceObjectIdentifierMappingViaControllerReturn()
    {
        $this->setUpAlternateRouter();

        $resource = new Resource();
        $resource->getEventManager()->attach('fetch', function ($e) {
            return (object) array(
                'id'   => 'luke',
                'name' => 'Luke Skywalker',
            );
        });
        $controller = new RestController();
        $controller->setPluginManager($this->plugins);
        $controller->setResource($resource);
        $controller->setIdentifierName('child_id');
        $r = new ReflectionObject($controller);
        $m = $r->getMethod('getIdentifier');
        $m->setAccessible(true);

        $uri = 'http://localhost.localdomain/api/parent/anakin/child/luke';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $matches);
        $this->assertEquals('anakin', $matches->getParam('id'));
        $this->assertEquals('luke', $matches->getParam('child_id'));
        $this->assertEquals('parent/child', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        // Ensure we matched an identifier!
        $id = $m->invoke($controller, $matches, $request);
        $this->assertEquals('luke', $id);

        $result = $controller->get('luke');
        $this->assertInstanceOf('ZF\Hal\Entity', $result);
        $self = $result->getLinks()->get('self');
        $params = $self->getRouteParams();
        $this->assertArrayHasKey('child_id', $params);
        $this->assertEquals('luke', $params['child_id']);
    }

    public function testChildResourceObjectIdentifierMappingInCollectionsViaControllerReturn()
    {
        $this->setUpAlternateRouter();

        $resource = new Resource();
        $resource->getEventManager()->attach('fetchAll', function ($e) {
            return array(
                (object) array(
                    'id'   => 'luke',
                    'name' => 'Luke Skywalker',
                ),
                (object) array(
                    'id'   => 'leia',
                    'name' => 'Leia Organa',
                ),
            );
        });
        $controller = new RestController();
        $controller->setPluginManager($this->plugins);
        $controller->setResource($resource);
        $controller->setRoute('parent/child');
        $controller->setIdentifierName('child_id');
        $controller->setCollectionName('children');
        $r = new ReflectionObject($controller);
        $m = $r->getMethod('getIdentifier');
        $m->setAccessible(true);

        $uri = 'http://localhost.localdomain/api/parent/anakin/child';
        $request = new Request();
        $request->setUri($uri);
        $matches = $this->router->match($request);
        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $matches);
        $this->assertEquals('anakin', $matches->getParam('id'));
        $this->assertNull($matches->getParam('child_id'));
        $this->assertEquals('parent/child', $matches->getMatchedRouteName());

        // Emulate url helper factory and inject route matches
        $this->helpers->get('url')->setRouteMatch($matches);

        $result = $controller->getList();
        $this->assertInstanceOf('ZF\Hal\Collection', $result);

        // Now, what happens if we render this?
        $model = new HalJsonModel();
        $model->setPayload($result);

        $json = $this->renderer->render($model);
        $test = json_decode($json);
        $this->assertObjectHasAttribute('_links', $test);
        $this->assertObjectHasAttribute('self', $test->_links);
        $this->assertObjectHasAttribute('href', $test->_links->self);
        $this->assertEquals('http://localhost.localdomain/api/parent/anakin/child', $test->_links->self->href);

        $this->assertObjectHasAttribute('_embedded', $test);
        $this->assertObjectHasAttribute('children', $test->_embedded);
        $this->assertInternalType('array', $test->_embedded->children);

        foreach ($test->_embedded->children as $child) {
            $this->assertObjectHasAttribute('_links', $child);
            $this->assertObjectHasAttribute('self', $child->_links);
            $this->assertObjectHasAttribute('href', $child->_links->self);
            $this->assertRegexp(
                '#^http://localhost.localdomain/api/parent/anakin/child/[^/]+$#',
                $child->_links->self->href
            );
        }
    }
}
