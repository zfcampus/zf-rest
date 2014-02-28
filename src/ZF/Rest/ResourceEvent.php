<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest;

use Zend\EventManager\Event;
use Zend\InputFilter\InputFilterInterface;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Parameters;

class ResourceEvent extends Event
{
    /**
     * @var null|InputFilterInterface
     */
    protected $inputFilter;

    /**
     * @var null|Parameters
     */
    protected $queryParams;

    /**
     * @var null|RouteMatch
     */
    protected $routeMatch;

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
