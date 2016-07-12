<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest;

use Zend\Loader\StandardAutoloader;
use Zend\Mvc\MvcEvent;

/**
 * ZF2 module
 */
class Module
{
    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Bootstrap listener
     *
     * Attaches a listener to the RestController dispatch event.
     *
     * @param  MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        $app      = $e->getTarget();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $services->get('ZF\Rest\OptionsListener')->attach($events);

        $sharedEvents = $events->getSharedManager();
        $services->get('ZF\Rest\RestParametersListener')->attachShared($sharedEvents);
    }
}
