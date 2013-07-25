<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZF\Rest\Listener;

use ZF\Rest\ResourceController;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\Mvc\MvcEvent;

class ResourceParametersListener implements
    ListenerAggregateInterface,
    SharedListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $sharedListeners = array();

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('dispatch', array($this, 'onDispatch'), 100);
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * @param SharedEventManagerInterface $events
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->sharedListeners[] = $events->attach('ZF\Rest\ResourceController', 'dispatch', array($this, 'onDispatch'), 100);
    }

    /**
     * @param SharedEventManagerInterface $events
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->sharedListeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->sharedListeners[$index]);
            }
        }
    }

    /**
     * Listen to the dispatch event
     *
     * @param MvcEvent $e
     */
    public function onDispatch(MvcEvent $e)
    {
        $controller = $e->getTarget();
        if (!$controller instanceof ResourceController) {
            return;
        }

        $request  = $e->getRequest();
        $query    = $request->getQuery();
        $matches  = $e->getRouteMatch();
        $resource = $controller->getResource();
        $resource->setQueryParams($query);
        $resource->setRouteMatch($matches);
    }
}
