<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest;

/**
 * ZF2 module
 */
class Module
{
    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Zend\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/',
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Bootstrap listener
     *
     * Attaches a listener to the RestController dispatch event.
     *
     * @param  \Zend\Mvc\MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $app          = $e->getTarget();
        $services     = $app->getServiceManager();
        $events       = $app->getEventManager();

        $events->attachAggregate($services->get('ZF\Rest\OptionsListener'));

        $sharedEvents = $events->getSharedManager();
        $sharedEvents->attachAggregate($services->get('ZF\Rest\RestParametersListener'));
    }
}
