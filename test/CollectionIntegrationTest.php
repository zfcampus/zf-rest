<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\TreeRouteStack as V2TreeRouteStack;
use Zend\Mvc\Router\RouteMatch as V2RouteMatch;
use Zend\Mvc\Service\ControllerPluginManagerFactory;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayPaginator;
use Zend\Paginator\Paginator;
use Zend\Router\Http\TreeRouteStack;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Parameters;
use Zend\Uri;
use Zend\View\HelperPluginManager;
use Zend\View\Helper\ServerUrl as ServerUrlHelper;
use Zend\View\Helper\Url as UrlHelper;
use ZF\ApiProblem\View\ApiProblemRenderer;
use ZF\ContentNegotiation\AcceptListener;
use ZF\Hal\Extractor\LinkCollectionExtractor;
use ZF\Hal\Extractor\LinkExtractor;
use ZF\Hal\Link\LinkUrlBuilder;
use ZF\Hal\Plugin\Hal as HalHelper;
use ZF\Hal\View\HalJsonModel;
use ZF\Hal\View\HalJsonRenderer;
use ZF\Rest\Factory\RestControllerFactory;
use ZF\Rest\Resource;
use ZF\Rest\RestController;

/**
 * @subpackage UnitTest
 */
class CollectionIntegrationTest extends TestCase
{
    use TreeRouteStackFactoryTrait;

    /** @var HalHelper */
    private $linksHelper;

    /** @var HelperPluginManager */
    private $helpers;

    /** @var HalJsonRenderer */
    private $renderer;

    /** @var TreeRouteStack|V2TreeRouteStack */
    private $router;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    /** @var RouteMatch|V2RouteMatch */
    private $matches;

    /** @var TestAsset\CollectionIntegrationListener */
    private $listeners;

    /** @var RestController */
    private $controller;

    public function setUp()
    {
        $this->setUpRenderer();
        $this->setUpController();
    }

    public function setUpHelpers()
    {
        if ($this->helpers) {
            return;
        }
        $this->setUpRouter();

        $urlHelper = new UrlHelper();
        $urlHelper->setRouter($this->router);

        $serverUrlHelper = new ServerUrlHelper();
        $serverUrlHelper->setScheme('http');
        $serverUrlHelper->setHost('localhost.localdomain');

        $linkUrlBuilder = new LinkUrlBuilder($serverUrlHelper, $urlHelper);

        $this->linksHelper = $linksHelper = new HalHelper();
        $linksHelper->setLinkUrlBuilder($linkUrlBuilder);

        $linkExtractor = new LinkExtractor($linkUrlBuilder);
        $linkCollectionExtractor = new LinkCollectionExtractor($linkExtractor);
        $linksHelper->setLinkCollectionExtractor($linkCollectionExtractor);

        $this->helpers = $helpers = new HelperPluginManager(
            $this->prophesize(ContainerInterface::class)->reveal()
        );
        $helpers->setService('url', $urlHelper);
        $helpers->setService('serverUrl', $serverUrlHelper);
        $helpers->setService('hal', $linksHelper);
        if (method_exists($helpers, 'configure')) {
            $helpers->setAlias('Hal', 'hal');
        }
    }

    public function setUpRenderer()
    {
        $this->setUpHelpers();
        $this->renderer = $renderer = new HalJsonRenderer(new ApiProblemRenderer());
        $renderer->setHelperPluginManager($this->helpers);
    }

    public function setUpRouter()
    {
        if ($this->router) {
            return;
        }

        $this->setUpRequest();

        $routes = [
            'resource' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/api/resource[/:id]',
                    'defaults' => [
                        'controller' => 'Api\RestController',
                    ],
                ],
            ],
        ];
        $this->router = $router = $this->createTreeRouteStack();
        $router->addRoutes($routes);

        $matches = $router->match($this->request);
        if (! ($matches instanceof RouteMatch || $matches instanceof V2RouteMatch)) {
            $this->fail('Failed to route!');
        }

        $this->matches = $matches;
    }

    public function setUpCollection()
    {
        $collection = [];
        for ($i = 1; $i <= 10; $i++) {
            $collection[] = (object) [
                'id'   => $i,
                'name' => sprintf('%d of 10', $i),
            ];
        }

        $collection = new Paginator(new ArrayPaginator($collection));

        return $collection;
    }

    public function setUpListeners()
    {
        if ($this->listeners) {
            return;
        }

        $this->listeners = new TestAsset\CollectionIntegrationListener();
        $this->listeners->setCollection($this->setUpCollection());
    }

    public function setUpController()
    {
        $this->setUpRouter();
        $this->setUpListeners();

        $resource = new Resource();
        $events   = $resource->getEventManager();
        $this->listeners->attach($events);

        $controller = $this->controller = new RestController('Api\RestController');
        $controller->setResource($resource);
        $controller->setIdentifierName('id');
        $controller->setPageSize(3);
        $controller->setRoute('resource');
        $controller->setEvent($this->getEvent());
        $this->setUpContentNegotiation($controller);
    }

    public function setUpContentNegotiation($controller)
    {
        $plugins = new ControllerPluginManager($this->prophesize(ContainerInterface::class)->reveal());
        $plugins->setService('hal', $this->linksHelper);
        if (method_exists($plugins, 'configure')) {
            $plugins->setAlias('Hal', 'hal');
        }
        $controller->setPluginManager($plugins);

        $viewModelSelector = $plugins->get('AcceptableViewModelSelector');
        $acceptListener    = new AcceptListener($viewModelSelector, [
            'controllers' => [],
            'selectors'  => [
                'HalJson' => [
                    HalJsonModel::class => [
                        'application/json',
                    ],
                ],
            ],
        ]);
        $controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $acceptListener, -10);
    }

    public function setUpRequest()
    {
        if ($this->request) {
            return;
        }

        $uri = Uri\UriFactory::factory('http://localhost.localdomain/api/resource?query=foo&page=2');

        $request = $this->request = new Request();
        $request->setQuery(new Parameters([
            'query' => 'foo',
            'bar' => 'baz',
            'page'  => 2,
        ]));
        $request->setUri($uri);
        $headers = $request->getHeaders();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');
    }

    public function setUpResponse()
    {
        if ($this->response) {
            return;
        }

        $this->response = new Response();
    }

    public function getEvent()
    {
        $this->setUpResponse();
        $event = new MvcEvent();
        $event->setRequest($this->request);
        $event->setResponse($this->response);
        $event->setRouter($this->router);
        $event->setRouteMatch($this->matches);
        return $event;
    }

    public function testCollectionLinksIncludeFullQueryString()
    {
        $this->controller->getEventManager()->attach('getList.post', function ($e) {
            $request    = $e->getTarget()->getRequest();
            $query = $request->getQuery('query', false);
            if (! $query) {
                return;
            }

            $collection = $e->getParam('collection');
            $collection->setCollectionRouteOptions([
                'query' => [
                    'query' => $query,
                ],
            ]);
        });
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertInstanceOf(HalJsonModel::class, $result);

        $json = $this->renderer->render($result);
        $payload = json_decode($json, true);
        $this->assertArrayHasKey('_links', $payload);
        $links = $payload['_links'];
        foreach ($links as $name => $link) {
            $this->assertArrayHasKey('href', $link);
            if ('first' !== $name) {
                $this->assertContains(
                    'page=',
                    $link['href'],
                    "Link $name ('{$link['href']}') is missing page query param"
                );
            }
            $this->assertContains(
                'query=foo',
                $link['href'],
                "Link $name ('{$link['href']}') is missing query query param"
            );
        }
    }

    public function getServiceManager()
    {
        $services    = new ServiceManager();
        $services->setService('config', [
            'zf-rest' => [
                'Api\RestController' => [
                    'listener'                   => TestAsset\CollectionIntegrationListener::class,
                    'page_size'                  => 3,
                    'route_name'                 => 'resource',
                    'route_identifier_name'      => 'id',
                    'collection_name'            => 'items',
                    'collection_query_whitelist' => ['query'],
                ],
            ],
        ]);

        $services->setFactory('SharedEventManager', function ($container) {
            return new SharedEventManager();
        });

        $services->setFactory(TestAsset\CollectionIntegrationListener::class, InvokableFactory::class);

        $services->setFactory('EventManager', function ($container) {
            $shared = $container->get('SharedEventManager');
            if (method_exists($shared, 'getEvents')) {
                $events = new EventManager();
                $events->setSharedManager($shared);
                return $events;
            }

            return new EventManager($shared);
        });
        $services->setShared('EventManager', false);

        $services->setFactory(
            'ControllerPluginManager',
            ControllerPluginManagerFactory::class
        );

        $collection = $this->setUpCollection();
        $services->addInitializer(function ($first, $second) use ($collection) {
            // Initializer signature varies between v2 and v3
            if ($first instanceof ServiceManager) {
                // v3 signature
                $instance = $second;
            } else {
                // v2 signature
                $instance = $first;
            }

            if (! $instance instanceof TestAsset\CollectionIntegrationListener) {
                return;
            }
            $instance->setCollection($collection);
        });

        $controllers = new ControllerManager($services);
        $controllers->addAbstractFactory(RestControllerFactory::class);
        $services->setService('ControllerManager', $controllers);

        $plugins = $services->get('ControllerPluginManager');
        $plugins->setService('hal', $this->linksHelper);
        if (method_exists($plugins, 'configure')) {
            $plugins->setAlias('Hal', 'hal');
        }

        return $services;
    }

    public function testFactoryEnabledListenerCreatesQueryStringWhitelist()
    {
        $services = $this->getServiceManager();
        $controller = $services->get('ControllerManager')->get('Api\RestController');
        $controller->setEvent($this->getEvent());
        $this->setUpContentNegotiation($controller);

        $result = $controller->dispatch($this->request, $this->response);
        $this->assertInstanceOf(HalJsonModel::class, $result);

        $json = $this->renderer->render($result);
        $payload = json_decode($json, true);
        $this->assertArrayHasKey('_links', $payload);
        $links = $payload['_links'];
        foreach ($links as $name => $link) {
            $this->assertArrayHasKey('href', $link);
            if ('first' !== $name) {
                $this->assertContains(
                    'page=',
                    $link['href'],
                    "Link $name ('{$link['href']}') is missing page query param"
                );
            }
            $this->assertContains(
                'query=foo',
                $link['href'],
                "Link $name ('{$link['href']}') is missing query query param"
            );
            $this->assertNotContains(
                'bar=baz',
                $link['href'],
                "Link $name ('{$link['href']}') includes query param that should have been omitted"
            );
        }
    }

    public function testFactoryEnabledListenerInjectsWhitelistedQueryParams()
    {
        $services = $this->getServiceManager();
        $controller = $services->get('ControllerManager')->get('Api\RestController');
        $controller->setEvent($this->getEvent());
        $this->setUpContentNegotiation($controller);

        $controller->dispatch($this->request, $this->response);
        $resource = $controller->getResource();
        $this->assertInstanceOf(Resource::class, $resource);
        $params = $resource->getQueryParams();

        $this->assertInstanceOf(Parameters::class, $params);
        $this->assertSame('foo', $params->get('query'));
        $this->assertFalse($params->offsetExists('bar'));
    }

    public function testFactoryEnabledListenerMergeWhitelistedQueryParamsWithInputFilterKeys()
    {
        $services = $this->getServiceManager();
        $controller = $services->get('ControllerManager')->get('Api\RestController');
        $controller->setEvent($this->getEvent());
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name' => 'bar',
            'required' => false,
            'allowEmpty' => true
        ]);
        $controller->getResource()->setInputFilter($inputFilter);
        $this->setUpContentNegotiation($controller);

        $controller->dispatch($this->request, $this->response);
        $resource = $controller->getResource();

        $this->assertInstanceOf(Resource::class, $resource);
        $params = $resource->getQueryParams();

        $this->assertSame('foo', $params->get('query'));
        $this->assertSame('baz', $params->get('bar'));
    }
}
