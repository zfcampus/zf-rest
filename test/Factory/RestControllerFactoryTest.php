<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Factory;

use ZF\Rest\Factory\RestControllerFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\ServiceManager;

class RestControllerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services    = $services    = new ServiceManager();
        $this->controllers = $controllers = new ControllerManager();
        $this->factory     = $factory     = new RestControllerFactory();

        $controllers->addAbstractFactory($factory);
        $controllers->setServiceLocator($services);

        $services->setService('Zend\ServiceManager\ServiceLocatorInterface', $services);
        $services->setService('Config', $this->getConfig());
        $services->setService('ControllerLoader', $controllers);
        $services->setFactory('ControllerPluginManager', 'Zend\Mvc\Service\ControllerPluginManagerFactory');
        $services->setInvokableClass('EventManager', 'Zend\EventManager\EventManager');
        $services->setInvokableClass('SharedEventManager', 'Zend\EventManager\SharedEventManager');
        $services->setShared('EventManager', false);
    }

    public function getConfig()
    {
        return array(
            'zf-rest' => array(
                'ApiController' => array(
                    'listener'   => 'ZFTest\Rest\Factory\TestAsset\Listener',
                    'route_name' => 'api',
                ),
            ),
        );
    }

    public function testWillInstantiateListenerIfServiceNotFoundButClassExists()
    {
        $this->assertTrue($this->controllers->has('ApiController'));
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf('ZF\Rest\RestController', $controller);
    }

    public function testWillInstantiateAlternateRestControllerWhenSpecified()
    {
        $config = $this->services->get('Config');
        $config['zf-rest']['ApiController']['controller_class'] = 'ZFTest\Rest\Factory\TestAsset\CustomController';
        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf('ZFTest\Rest\Factory\TestAsset\CustomController', $controller);
    }

    public function testDefaultControllerEventManagerIdentifiersAreAsExpected()
    {
        $controller = $this->controllers->get('ApiController');
        $events = $controller->getEventManager();

        $identifiers = $events->getIdentifiers();

        $this->assertContains('ZF\Rest\RestController', $identifiers);
        $this->assertContains('ApiController', $identifiers);
    }

    public function testControllerEventManagerIdentifiersAreAsSpecified()
    {
        $config = $this->services->get('Config');
        $config['zf-rest']['ApiController']['identifier'] = 'ZFTest\Rest\Factory\TestAsset\ExtraControllerListener';
        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);

        $controller = $this->controllers->get('ApiController');
        $events = $controller->getEventManager();

        $identifiers = $events->getIdentifiers();

        $this->assertContains('ZF\Rest\RestController', $identifiers);
        $this->assertContains('ZFTest\Rest\Factory\TestAsset\ExtraControllerListener', $identifiers);
    }

    public function testDefaultResourceEventManagerIdentifiersAreAsExpected()
    {
        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = array(
            'ZFTest\Rest\Factory\TestAsset\Listener',
            'ZF\Rest\Resource',
            'ZF\Rest\ResourceInterface',
        );
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function testResourceEventManagerIdentifiersAreAsSpecifiedString()
    {
        $config = $this->services->get('Config');
        $config['zf-rest']['ApiController']['resource_identifiers'] =
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener';
        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);

        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = array(
            'ZFTest\Rest\Factory\TestAsset\Listener',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener',
            'ZF\Rest\Resource',
            'ZF\Rest\ResourceInterface',
        );
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function testResourceEventManagerIdentifiersAreAsSpecifiedArray()
    {
        $config = $this->services->get('Config');
        $config['zf-rest']['ApiController']['resource_identifiers'] = array(
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener1',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener2',
        );
        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);

        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = array(
            'ZFTest\Rest\Factory\TestAsset\Listener',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener1',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener2',
            'ZF\Rest\Resource',
            'ZF\Rest\ResourceInterface',
        );
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }
}
