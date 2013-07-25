<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZFTest\Rest\Factory;

use ZF\Rest\Factory\ResourceControllerFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\ServiceManager;

class ResourceControllerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services    = $services    = new ServiceManager();
        $this->controllers = $controllers = new ControllerManager();
        $this->factory     = $factory     = new ResourceControllerFactory();

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
                'resources' => array(
                    'ApiController' => array(
                        'listener'   => 'ZFTest\Rest\Factory\TestAsset\Listener',
                        'route_name' => 'api',
                    ),
                ),
            ),
        );
    }

    /**
     * @group fail
     */
    public function testWillInstantiateListenerIfServiceNotFoundButClassExists()
    {
        $this->assertTrue($this->controllers->has('ApiController'));
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf('ZF\Rest\ResourceController', $controller);
    }

    public function testWillInstantiateAlternateResourceControllerWhenSpecified()
    {
        $config = $this->services->get('Config');
        $config['zf-rest']['resources']['ApiController']['controller_class'] = 'ZFTest\Rest\Factory\TestAsset\CustomController';
        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf('ZFTest\Rest\Factory\TestAsset\CustomController', $controller);
    }
}
