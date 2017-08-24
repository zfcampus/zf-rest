<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Factory;

use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Service\ControllerPluginManagerFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Rest\Factory\RestControllerFactory;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\ServiceManager;
use ZF\Rest\Resource;
use ZF\Rest\ResourceInterface;
use ZF\Rest\RestController;

class RestControllerFactoryTest extends TestCase
{
    /** @var ServiceManager */
    private $services;

    /** @var ControllerManager */
    private $controllers;

    /** @var RestControllerFactory */
    private $factory;

    public function setUp()
    {
        $this->services    = $services    = new ServiceManager();
        $this->controllers = $controllers = new ControllerManager($this->services);
        $this->factory     = $factory     = new RestControllerFactory();

        $controllers->addAbstractFactory($factory);

        $services->setService(ServiceLocatorInterface::class, $services);
        $services->setService('config', $this->getConfig());
        $services->setService('ControllerManager', $controllers);
        $services->setFactory('ControllerPluginManager', ControllerPluginManagerFactory::class);
        $services->setInvokableClass('EventManager', EventManager::class);
        $services->setInvokableClass('SharedEventManager', SharedEventManager::class);
        $services->setShared('EventManager', false);
    }

    public function getConfig()
    {
        return [
            'zf-rest' => [
                'ApiController' => [
                    'listener'   => TestAsset\Listener::class,
                    'route_name' => 'api',
                ],
            ],
        ];
    }

    public function testWillInstantiateListenerIfServiceNotFoundButClassExists()
    {
        $this->assertTrue($this->controllers->has('ApiController'));
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf(RestController::class, $controller);
    }

    public function testWillInstantiateAlternateRestControllerWhenSpecified()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['controller_class'] = TestAsset\CustomController::class;
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf(TestAsset\CustomController::class, $controller);
    }

    public function testDefaultControllerEventManagerIdentifiersAreAsExpected()
    {
        $controller = $this->controllers->get('ApiController');
        $events = $controller->getEventManager();

        $identifiers = $events->getIdentifiers();

        $this->assertContains(RestController::class, $identifiers);
        $this->assertContains('ApiController', $identifiers);
    }

    public function testControllerEventManagerIdentifiersAreAsSpecified()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['identifier'] = TestAsset\ExtraControllerListener::class;
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);

        $controller = $this->controllers->get('ApiController');
        $events = $controller->getEventManager();

        $identifiers = $events->getIdentifiers();

        $this->assertContains(RestController::class, $identifiers);
        $this->assertContains(TestAsset\ExtraControllerListener::class, $identifiers);
    }

    public function testDefaultResourceEventManagerIdentifiersAreAsExpected()
    {
        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = [
            TestAsset\Listener::class,
            Resource::class,
            ResourceInterface::class,
        ];
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function testResourceEventManagerIdentifiersAreAsSpecifiedString()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['resource_identifiers'] = TestAsset\ExtraResourceListener::class;
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);

        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = [
            TestAsset\Listener::class,
            TestAsset\ExtraResourceListener::class,
            Resource::class,
            ResourceInterface::class,
        ];
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function testResourceEventManagerIdentifiersAreAsSpecifiedArray()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['resource_identifiers'] = [
            TestAsset\ExtraResourceListener1::class,
            TestAsset\ExtraResourceListener2::class,
        ];
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);

        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = [
            TestAsset\Listener::class,
            TestAsset\ExtraResourceListener1::class,
            TestAsset\ExtraResourceListener2::class,
            Resource::class,
            ResourceInterface::class,
        ];
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }
}
