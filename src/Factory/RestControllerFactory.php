<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest\Factory;

use Zend\EventManager\Event;
use Zend\Stdlib\Parameters;
use ZF\Hal\Collection;
use ZF\Rest\Resource;
use ZF\Rest\RestController;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class RestControllerFactory
 */
class RestControllerFactory implements AbstractFactoryInterface
{
    /**
     * Cache of canCreateServiceWithName lookups
     * @var array
     */
    protected $lookupCache = array();

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $controllers
     * @param string                  $name
     * @param string                  $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $controllers, $name, $requestedName)
    {
        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        $services = $controllers->getServiceLocator();

        if (!$services->has('Config') || !$services->has('EventManager')) {
            // Config and EventManager are required
            return false;
        }

        $config = $services->get('Config');
        if (!isset($config['zf-rest'])
            || !is_array($config['zf-rest'])
        ) {
            $this->lookupCache[$requestedName] = false;
            return false;
        }
        $config = $config['zf-rest'];

        if (!isset($config[$requestedName])
            || !isset($config[$requestedName]['listener'])
            || !isset($config[$requestedName]['route_name'])
        ) {
            // Configuration, and specifically the listener and route_name
            // keys, is required
            $this->lookupCache[$requestedName] = false;
            return false;
        }

        if (!$services->has($config[$requestedName]['listener'])
            && !class_exists($config[$requestedName]['listener'])
        ) {
            // Service referenced by listener key is required
            $this->lookupCache[$requestedName] = false;
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "listener" service be specified for controller %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        $this->lookupCache[$requestedName] = true;
        return true;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $controllers
     * @param string                  $name
     * @param string                  $requestedName
     * @return RestController
     * @throws ServiceNotCreatedException if listener specified is not a ListenerAggregate
     */
    public function createServiceWithName(ServiceLocatorInterface $controllers, $name, $requestedName)
    {
        $services = $controllers->getServiceLocator();
        $config   = $services->get('Config');
        $config   = $config['zf-rest'][$requestedName];

        if ($services->has($config['listener'])) {
            $listener = $services->get($config['listener']);
        } else {
            $listener = new $config['listener'];
        }

        if (!$listener instanceof ListenerAggregateInterface) {
            throw new ServiceNotCreatedException(sprintf(
                '%s expects that the "listener" reference a service that implements '
                . 'Zend\EventManager\ListenerAggregateInterface; received %s',
                __METHOD__,
                (is_object($listener) ? get_class($listener) : gettype($listener))
            ));
        }

        $resourceIdentifiers = array(get_class($listener));
        if (isset($config['resource_identifiers'])) {
            if (!is_array($config['resource_identifiers'])) {
                $config['resource_identifiers'] = (array) $config['resource_identifiers'];
            }
            $resourceIdentifiers = array_merge($resourceIdentifiers, $config['resource_identifiers']);
        }

        $events = $services->get('EventManager');
        $events->attach($listener);
        $events->setIdentifiers($resourceIdentifiers);

        $resource = new Resource();
        $resource->setEventManager($events);

        $identifier = $requestedName;
        if (isset($config['identifier'])) {
            $identifier = $config['identifier'];
        }

        $controllerClass = isset($config['controller_class']) ? $config['controller_class'] : 'ZF\Rest\RestController';
        $controller      = new $controllerClass($identifier);

        if (!$controller instanceof RestController) {
            throw new ServiceNotCreatedException(sprintf(
                '"%s" must be an implementation of ZF\Rest\RestController',
                $controllerClass
            ));
        }

        $controller->setResource($resource);
        $this->setControllerOptions($config, $controller);

        if (isset($config['entity_class'])) {
            $listener->setEntityClass($config['entity_class']);
        }

        if (isset($config['collection_class'])) {
            $listener->setCollectionClass($config['collection_class']);
        }

        return $controller;
    }

    /**
     * Loop through configuration to discover and set controller options.
     *
     * @param  array $config
     * @param  RestController $controller
     */
    protected function setControllerOptions(array $config, RestController $controller)
    {
        foreach ($config as $option => $value) {
            switch ($option) {
                case 'collection_http_methods':
                    $controller->setCollectionHttpMethods($value);
                    break;

                case 'collection_name':
                    $controller->setCollectionName($value);
                    break;

                case 'collection_query_whitelist':
                    if (is_string($value)) {
                        $value = (array) $value;
                    }
                    if (!is_array($value)) {
                        break;
                    }

                    // Create a listener that checks the query string against
                    // the whitelisted query parameters in order to seed the
                    // collection route options.
                    $whitelist = $value;
                    $controller->getEventManager()->attach('getList.pre', function (Event $e) use ($whitelist) {
                        $controller = $e->getTarget();
                        $resource   = $controller->getResource();
                        if (! $resource instanceof Resource) {
                            // ResourceInterface does not define setQueryParams, so we need
                            // specifically a Resource instance
                            return;
                        }

                        $request = $controller->getRequest();
                        if (! method_exists($request, 'getQuery')) {
                            return;
                        }

                        $query  = $request->getQuery();
                        $params = new Parameters(array());
                        foreach ($query as $key => $value) {
                            if (! in_array($key, $whitelist)) {
                                continue;
                            }
                            $params->set($key, $value);
                        }
                        $resource->setQueryParams($params);
                    });

                    $controller->getEventManager()->attach('getList.post', function (Event $e) use ($whitelist) {
                        $controller = $e->getTarget();
                        $resource   = $controller->getResource();
                        if (! $resource instanceof Resource) {
                            // ResourceInterface does not define setQueryParams, so we need
                            // specifically a Resource instance
                            return;
                        }

                        $collection = $e->getParam('collection');
                        if (! $collection instanceof Collection) {
                            return;
                        }

                        $params = $resource->getQueryParams()->getArrayCopy();

                        // Set collection route options with the captured query whitelist, to
                        // ensure paginated links are generated correctly
                        $collection->setCollectionRouteOptions(array(
                            'query' => $params,
                        ));

                        // If no self link defined, set the options in the collection and return
                        $links = $collection->getLinks();
                        if (! $links->has('self')) {
                            return;
                        }

                        // If self link is defined, but is not route-based, return
                        $self = $links->get('self');
                        if (! $self->hasRoute()) {
                            return;
                        }

                        // Otherwise, merge the query string parameters with
                        // the self link's route options
                        $self    = $links->get('self');
                        $options = $self->getRouteOptions();
                        $self->setRouteOptions(array_merge($options, array(
                            'query' => $params,
                        )));
                    });
                    break;

                case 'entity_http_methods':
                    $controller->setEntityHttpMethods($value);
                    break;

                /**
                 * The identifierName is a property of the ancestor
                 * and is described by Apigility as route_identifier_name
                 */
                case 'route_identifier_name':
                    $controller->setIdentifierName($value);
                    break;
                    
                case 'min_page_size':
                    $controller->setMinPageSize($value);
                    break;

                case 'page_size':
                    $controller->setPageSize($value);
                    break;

                case 'max_page_size':
                    $controller->setMaxPageSize($value);
                    break;

                case 'page_size_param':
                    $controller->setPageSizeParam($value);
                    break;

                /**
                 * @todo Remove this by 1.0; BC only, starting in 0.9.0
                 */
                case 'resource_http_methods':
                    $controller->setEntityHttpMethods($value);
                    break;

                case 'route_name':
                    $controller->setRoute($value);
                    break;
            }
        }
    }
}
