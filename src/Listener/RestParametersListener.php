<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest\Listener;

use ZF\Rest\RestController;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;

class RestParametersListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = [];

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $sharedListeners = [];

    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 100);
    }

    /**
     * @param SharedEventManagerInterface $events
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $listener = $events->attach(
            RestController::class,
            MvcEvent::EVENT_DISPATCH,
            [$this, 'onDispatch'],
            100
        );

        if (! $listener) {
            $listener = [$this, 'onDispatch'];
        }

        $this->sharedListeners[] = $listener;
    }

    /**
     * @param SharedEventManagerInterface $events
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        $eventManagerVersion = method_exists($events, 'getEvents') ? 2 : 3;
        foreach ($this->sharedListeners as $index => $listener) {
            switch ($eventManagerVersion) {
                case 2:
                    if ($events->detach(RestController::class, $listener)) {
                        unset($this->sharedListeners[$index]);
                    }
                    break;
                case 3:
                    if ($events->detach($listener, RestController::class, MvcEvent::EVENT_DISPATCH)) {
                        unset($this->sharedListeners[$index]);
                    }
                    break;
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
        if (! $controller instanceof RestController) {
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
