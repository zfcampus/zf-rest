<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZF\Rest;

use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\HydratorPluginManager;

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
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Retrieve Service Manager configuration
     *
     * Defines ZF\Rest\RestfulJsonStrategy service factory.
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array('factories' => array(
            'ZF\Rest\ApiProblemListener' => function ($services) {
                $config = array();
                if ($services->has('config')) {
                    $config = $services->get('config');
                }

                $filter = null;
                if (isset($config['zf-rest'])
                    && isset($config['zf-rest']['accept_filter'])
                ) {
                    $filter = $config['zf-rest']['accept_filter'];
                }

                return new Listener\ApiProblemListener($filter);
            },
            'ZF\Rest\MetadataMap' => function ($services) {
                $config = array();
                if ($services->has('config')) {
                    $config = $services->get('config');
                }

                if ($services->has('HydratorManager')) {
                    $hydrators = $services->get('HydratorManager');
                } else {
                    $hydrators = new HydratorPluginManager();
                }

                $map = array();
                if (isset($config['zf-rest'])
                    && isset($config['zf-rest']['metadata_map'])
                    && is_array($config['zf-rest']['metadata_map'])
                ) {
                    $map = $config['zf-rest']['metadata_map'];
                }

                return new MetadataMap($map, $hydrators);
            },
            'ZF\Rest\JsonRenderer' => function ($services) {
                $helpers  = $services->get('ViewHelperManager');
                $config   = $services->get('Config');

                $displayExceptions = false;
                if (isset($config['view_manager'])
                    && isset($config['view_manager']['display_exceptions'])
                ) {
                    $displayExceptions = (bool) $config['view_manager']['display_exceptions'];
                }

                $renderer = new View\RestfulJsonRenderer();
                $renderer->setHelperPluginManager($helpers);
                $renderer->setDisplayExceptions($displayExceptions);

                return $renderer;
            },
            'ZF\Rest\RestfulJsonStrategy' => function ($services) {
                $renderer = $services->get('ZF\Rest\JsonRenderer');
                return new View\RestfulJsonStrategy($renderer);
            },
        ));
    }

    /**
     * Define factories for controller plugins
     *
     * Defines the "HalLinks" plugin.
     *
     * @return array
     */
    public function getControllerPluginConfig()
    {
        return array('factories' => array(
            'HalLinks' => function ($plugins) {
                $services = $plugins->getServiceLocator();
                $helpers  = $services->get('ViewHelperManager');
                return $helpers->get('HalLinks');
            },
        ));
    }

    /**
     * Defines the "HalLinks" view helper
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return array('factories' => array(
            'HalLinks' => function ($helpers) {
                $serverUrlHelper = $helpers->get('ServerUrl');
                $urlHelper       = $helpers->get('Url');

                $services        = $helpers->getServiceLocator();
                $config          = $services->get('Config');
                $metadataMap     = $services->get('ZF\Rest\MetadataMap');
                $hydrators       = $metadataMap->getHydratorManager();

                $helper          = new Plugin\HalLinks($hydrators);
                $helper->setMetadataMap($metadataMap);
                $helper->setServerUrlHelper($serverUrlHelper);
                $helper->setUrlHelper($urlHelper);

                if (isset($config['zf-rest'])
                    && isset($config['zf-rest']['renderer'])
                ) {
                    $config = $config['zf-rest']['renderer'];

                    if (isset($config['default_hydrator'])) {
                        $hydratorServiceName = $config['default_hydrator'];

                        if (!$hydrators->has($hydratorServiceName)) {
                            throw new Exception\DomainException(
                                sprintf(
                                    'Cannot locate default hydrator by name "%s" via the HydratorManager',
                                    $hydratorServiceName
                                )
                            );
                        }

                        $hydrator = $hydrators->get($hydratorServiceName);
                        $helper->setDefaultHydrator($hydrator);
                    }

                    if (isset($config['hydrators']) && is_array($config['hydrators'])) {
                        $hydratorMap = $config['hydrators'];
                        foreach ($hydratorMap as $class => $hydratorServiceName) {
                            $helper->addHydrator($class, $hydratorServiceName);
                        }
                    }
                }

                return $helper;
            }
        ));
    }

    /**
     * Listener for bootstrap event
     *
     * Attaches a render event.
     *
     * @param  \Zend\Mvc\MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $app      = $e->getTarget();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $events->attach('render', array($this, 'onRender'), 100);
        $sharedEvents = $events->getSharedManager();
        $sharedEvents->attach('ZF\Rest\ResourceController', 'dispatch', function($e) use ($services) {
            $eventManager = $e->getApplication()->getEventManager();
            $eventManager->attach($services->get('ZF\Rest\ApiProblemListener'));
        }, 300);
        $sharedEvents->attachAggregate($services->get('ZF\Rest\ResourceParametersListener'));
    }

    /**
     * Listener for the render event
     *
     * Attaches a rendering/response strategy to the View.
     *
     * @param  \Zend\Mvc\MvcEvent $e
     */
    public function onRender($e)
    {
        $result = $e->getResult();
        if (!$result instanceof View\RestfulJsonModel) {
            return;
        }

        $app                 = $e->getTarget();
        $services            = $app->getServiceManager();
        $view                = $services->get('View');
        $restfulJsonStrategy = $services->get('ZF\Rest\RestfulJsonStrategy');
        $events              = $view->getEventManager();

        // register at high priority, to "beat" normal json strategy registered
        // via view manager
        $events->attach($restfulJsonStrategy, 200);
    }
}
