<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use ZF\ApiProblem\ApiProblem;

abstract class AbstractResourceListener extends AbstractListenerAggregate
{
    /**
     * @var ResourceEvent
     */
    protected $event;

    /**
     * The entity_class config for the calling controller zf-rest config
     */
    protected $entityClass;

    /**
     * The collection_class config for the calling controller zf-rest config
     */
    protected $collectionClass;

    /**
     * Current identity, if discovered in the resource event.
     *
     * @var \ZF\MvcAuth\Identity\IdentityInterface
     */
    protected $identity;

    /**
     * Input filter, if discovered in the resource event.
     *
     * @var \Zend\InputFilter\InputFilterInterface
     */
    protected $inputFilter;

    /**
     * Set the entity_class for the controller config calling this resource
     */
    public function setEntityClass($className)
    {
        $this->entityClass = $className;
        return $this;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function setCollectionClass($className)
    {
        $this->collectionClass = $className;
        return $this;
    }

    public function getCollectionClass()
    {
        return $this->collectionClass;
    }

    /**
     * Retrieve the current resource event, if any
     *
     * @return ResourceEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Retrieve the identity, if any
     *
     * Proxies to the resource event to find the identity, if not already
     * composed, and composes it.
     *
     * @return null|\ZF\MvcAuth\Identity\IdentityInterface
     */
    public function getIdentity()
    {
        if ($this->identity) {
            return $this->identity;
        }

        $event = $this->getEvent();
        if (! $event instanceof ResourceEvent) {
            return null;
        }

        $this->identity = $event->getIdentity();
        return $this->identity;
    }

    /**
     * Retrieve the input filter, if any
     *
     * Proxies to the resource event to find the input filter, if not already
     * composed, and composes it.
     *
     * @return null|\Zend\InputFilter\InputFilterInterface
     */
    public function getInputFilter()
    {
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $event = $this->getEvent();
        if (! $event instanceof ResourceEvent) {
            return null;
        }

        $this->inputFilter = $event->getInputFilter();
        return $this->inputFilter;
    }

    /**
     * Attach listeners for all Resource events
     *
     * @param  EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $events->attach('create', array($this, 'dispatch'));
        $events->attach('delete', array($this, 'dispatch'));
        $events->attach('deleteList', array($this, 'dispatch'));
        $events->attach('fetch', array($this, 'dispatch'));
        $events->attach('fetchAll', array($this, 'dispatch'));
        $events->attach('patch', array($this, 'dispatch'));
        $events->attach('patchList', array($this, 'dispatch'));
        $events->attach('replaceList', array($this, 'dispatch'));
        $events->attach('update', array($this, 'dispatch'));
    }

    /**
     * Dispatch an incoming event to the appropriate method
     *
     * Marshals arguments from the event parameters.
     *
     * @param  ResourceEvent $event
     * @return mixed
     */
    public function dispatch(ResourceEvent $event)
    {
        $this->event = $event;
        switch ($event->getName()) {
            case 'create':
                $data = $event->getParam('data', array());
                return $this->create($data);
            case 'delete':
                $id   = $event->getParam('id', null);
                return $this->delete($id);
            case 'deleteList':
                $data = $event->getParam('data', array());
                return $this->deleteList($data);
            case 'fetch':
                $id   = $event->getParam('id', null);
                return $this->fetch($id);
            case 'fetchAll':
                $queryParams = $event->getQueryParams() ?: array();
                return $this->fetchAll($queryParams);
            case 'patch':
                $id   = $event->getParam('id', null);
                $data = $event->getParam('data', array());
                return $this->patch($id, $data);
            case 'patchList':
                $data = $event->getParam('data', array());
                return $this->patchList($data);
            case 'replaceList':
                $data = $event->getParam('data', array());
                return $this->replaceList($data);
            case 'update':
                $id   = $event->getParam('id', null);
                $data = $event->getParam('data', array());
                return $this->update($id, $data);
            default:
                throw new Exception\RuntimeException(sprintf(
                    '%s has not been setup to handle the event "%s"',
                    __METHOD__,
                    $event->getName()
                ));
        }
    }

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function create($data)
    {
        return new ApiProblem(405, 'The POST method has not been defined');
    }

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for individual resources');
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        return new ApiProblem(405, 'The GET method has not been defined for individual resources');
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($params = array())
    {
        return new ApiProblem(405, 'The GET method has not been defined for collections');
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patch($id, $data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for individual resources');
    }

    /**
     * Patch (partial in-place update) a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patchList($data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for collections');
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for individual resources');
    }
}
