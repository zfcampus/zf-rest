<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest\Listener;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;

class OptionsListener extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param  array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param  EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'), -100);
    }

    /**
     * @param  MvcEvent $event
     * @return void|\Zend\Http\Response
     */
    public function onRoute(MvcEvent $event)
    {
        $request = $event->getRequest();
        if (!$request instanceof Request) {
            // Not an HTTP request? nothing to do
            return;
        }

        $matches = $event->getRouteMatch();
        if (!$matches) {
            // No matches, nothing to do
            return;
        }

        $controller = $matches->getParam('controller', false);
        if (!$controller) {
            // No controller in the matches, nothing to do
            return;
        }

        if (!array_key_exists($controller, $this->config)) {
            // No matching controller in our configuration, nothing to do
            return;
        }

        $config  = $this->getConfigForControllerAndMatches($this->config[$controller], $matches);
        $methods = $this->normalizeMethods($config);

        $method = $request->getMethod();
        if ($method === Request::METHOD_OPTIONS) {
            // OPTIONS request? return response with Allow header
            return $this->getOptionsResponse($event, $methods);
        }

        if (in_array($method, $methods)) {
            // Valid HTTP method; nothing to do
            return;
        }

        // Invalid method; return 405 response
        return $this->get405Response($event, $methods);
    }

    /**
     * Normalize an array of HTTP methods
     *
     * If a string is provided, create an array with that string.
     *
     * Ensure all options in the array are UPPERCASE.
     *
     * @param  string|array $methods
     * @return array
     */
    protected function normalizeMethods($methods)
    {
        if (is_string($methods)) {
            $methods = (array) $methods;
        }

        array_walk($methods, function (&$value) {
            return strtoupper($value);
        });
        return $methods;
    }

    /**
     * Create the Allow header
     *
     * @param  array $options
     * @param  Response $response
     */
    protected function createAllowHeader(array $options, Response $response)
    {
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Allow', implode(',', $options));
    }

    /**
     * Prepare and return an OPTIONS response
     *
     * Creates an empty response with an Allow header.
     *
     * @param  MvcEvent $event
     * @param  array $options
     * @return Response
     */
    protected function getOptionsResponse(MvcEvent $event, array $options)
    {
        $response = $event->getResponse();
        $this->createAllowHeader($options, $response);
        return $response;
    }

    /**
     * Prepare a 405 response
     *
     * @param  MvcEvent $event
     * @param  array $options
     * @return Response
     */
    protected function get405Response(MvcEvent $event, array $options)
    {
        $response = $this->getOptionsResponse($event, $options);
        $response->setStatusCode(405, 'Method Not Allowed');
        return $response;
    }

    /**
     * Retrieve the HTTP method configuration for the selected controller and request
     *
     * Determines if this was a request to a collection or an entity, and returns the
     * appropriate HTTP method configuration.
     *
     * If an entity request was detected, but no entity configuration exists, returns
     *
     * @param mixed $config
     * @param mixed $matches
     * @return void
     */
    protected function getConfigForControllerAndMatches($config, $matches)
    {
        $collectionConfig = array();
        if (array_key_exists('collection_http_methods', $config)
            && is_array($config['collection_http_methods'])
        ) {
            $collectionConfig = $config['collection_http_methods'];
            // Ensure the HTTP method names are normalized
            array_walk($collectionConfig, function (&$value) {
                $value = strtoupper($value);
            });
        }

        $identifier = false;
        if (array_key_exists('route_identifier_name', $config)) {
            $identifier = $config['route_identifier_name'];
        }

        if (! $identifier || $matches->getParam($identifier, false) === false) {
            return $collectionConfig;
        }

        if (array_key_exists('entity_http_methods', $config)
            && is_array($config['entity_http_methods'])
        ) {
            $entityConfig = $config['entity_http_methods'];
            // Ensure the HTTP method names are normalized
            array_walk($entityConfig, function (&$value) {
                $value = strtoupper($value);
            });
            return $entityConfig;
        }

        return array();
    }
}
