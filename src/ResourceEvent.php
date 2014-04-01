<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest;

use ArrayAccess;
use Zend\EventManager\Event;
use Zend\EventManager\Exception\InvalidArgumentException;
use Zend\InputFilter\InputFilterInterface;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\RequestInterface;
use ZF\MvcAuth\Identity\IdentityInterface;

class ResourceEvent extends Event
{
    /**
     * @var null|IdentityInterface
     */
    protected $identity;

    /**
     * @var null|InputFilterInterface
     */
    protected $inputFilter;

    /**
     * @var null|Parameters
     */
    protected $queryParams;

    /**
     * @var null|RequestInterface
     */
    protected $request;

    /**
     * @var null|RouteMatch
     */
    protected $routeMatch;

    /**
     * Overload setParams to inject request object, if passed via params
     *
     * @param array|ArrayAccess|object $params
     * @return self
     */
    public function setParams($params)
    {
        if (! is_array($params) && ! is_object($params)) {
            throw new InvalidArgumentException(sprintf(
                'Event parameters must be an array or object; received "%s"',
                gettype($params)
            ));
        }

        if (is_array($params) || $params instanceof ArrayAccess) {
            if (isset($params['request'])) {
                $this->setRequest($params['request']);
                unset($params['request']);
            }
        }

        parent::setParams($params);
        return $this;
    }

    /**
     * @param null|IdentityInterface $identity
     * @return self
     */
    public function setIdentity(IdentityInterface $identity = null)
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * @return null|IdentityInterface
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @param null|InputFilterInterface $inputFilter
     * @return self
     */
    public function setInputFilter(InputFilterInterface $inputFilter = null)
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    /**
     * @return null|InputFilterInterface
     */
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    /**
     * @param Parameters $params
     * @return self
     */
    public function setQueryParams(Parameters $params = null)
    {
        $this->queryParams = $params;
        return $this;
    }

    /**
     * @return null|Parameters
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Retrieve a single query parameter by name
     *
     * If not present, returns the $default value provided.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam($name, $default = null)
    {
        $params = $this->getQueryParams();
        if (null === $params) {
            return $default;
        }

        return $params->get($name, $default);
    }

    /**
     * @param null|RequestInterface $request
     * @return self
     */
    public function setRequest(RequestInterface $request = null)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return null|RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param RouteMatch $matches
     * @return self
     */
    public function setRouteMatch(RouteMatch $matches = null)
    {
        $this->routeMatch = $matches;
        return $this;
    }

    /**
     * @return null|RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->routeMatch;
    }

    /**
     * Retrieve a single route match parameter by name.
     *
     * If not present, returns the $default value provided.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getRouteParam($name, $default = null)
    {
        $matches = $this->getRouteMatch();
        if (null === $matches) {
            return $default;
        }

        return $matches->getParam($name, $default);
    }
}
