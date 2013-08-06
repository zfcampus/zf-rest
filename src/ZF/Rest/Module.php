<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
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
            __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
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
        $events       = $app->getEventManager();
        $sharedEvents = $events->getSharedManager();
        $sharedEvents->attach('ZF\Rest\RestController', $e::EVENT_DISPATCH, array($this, 'onDispatch'), 100);
    }

    /**
     * RestController dispatch listener
     *
     * Attach the ApiProblem RenderErrorListener when a restful controller is detected.
     * 
     * @param  \Zend\Mvc\MvcEvent $e 
     */
    public function onDispatch($e)
    {
        $app      = $e->getApplication();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();
        $listener = $services->get('ZF\ApiProblem\RenderErrorListener');
        $events->attach($listener);
    }
}
