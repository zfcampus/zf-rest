<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use Zend\ServiceManager\ServiceManager;
use ZF\Rest\Factory\OptionsListenerFactory;

class OptionsListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new OptionsListenerFactory();
    }

    public function seedConfigService()
    {
        return array('zf-rest' => array(
            'some-controller' => array(
                'listener'                => 'SomeListener',
                'route_name'              => 'api.rest.some',
                'route_identifer_name'    => 'some_id',
                'entity_class'            => 'SomeEntity',
                'entity_http_methods'     => array('GET', 'PATCH', 'DELETE'),
                'collection_name'         => 'some',
                'collection_http_methods' => array('GET', 'POST'),
            ),
        ));
    }

    public function testFactoryCreatesOptionsListenerFromRestConfiguration()
    {
        $config = $this->seedConfigService();
        $this->services->setService('Config', $config);

        $listener = $this->factory->createService($this->services);

        $this->assertInstanceOf('ZF\Rest\Listener\OptionsListener', $listener);

        $r = new ReflectionObject($listener);
        $p = $r->getProperty('config');
        $p->setAccessible(true);
        $instanceConfig = $p->getValue($listener);
        $this->assertEquals($config['zf-rest'], $instanceConfig);
    }
}
