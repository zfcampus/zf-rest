<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as HttpRequest;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Parameters;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\Rest\ResourceEvent;

class ResourceEventTest extends TestCase
{
    public function setUp()
    {
        $this->matches = new RouteMatch(array(
            'foo' => 'bar',
            'baz' => 'inga',
        ));
        $this->query = new Parameters(array(
            'foo' => 'bar',
            'baz' => 'inga',
        ));

        $this->event = new ResourceEvent();
    }

    public function testRouteMatchIsNullByDefault()
    {
        $this->assertNull($this->event->getRouteMatch());
    }

    public function testQueryParamsAreNullByDefault()
    {
        $this->assertNull($this->event->getQueryParams());
    }

    public function testRouteMatchIsMutable()
    {
        $this->event->setRouteMatch($this->matches);
        $this->assertSame($this->matches, $this->event->getRouteMatch());
        return $this->event;
    }

    public function testQueryParamsAreMutable()
    {
        $this->event->setQueryParams($this->query);
        $this->assertSame($this->query, $this->event->getQueryParams());
        return $this->event;
    }

    public function testRequestIsNullByDefault()
    {
        $this->assertNull($this->event->getRequest());
    }

    public function testRequestIsMutable()
    {
        $request = new HttpRequest();
        $this->event->setRequest($request);
        $this->assertSame($request, $this->event->getRequest());
        return $this->event;
    }

    /**
     * @depends testRouteMatchIsMutable
     */
    public function testRouteMatchIsNullable(ResourceEvent $event)
    {
        $event->setRouteMatch(null);
        $this->assertNull($event->getRouteMatch());
    }

    /**
     * @depends testQueryParamsAreMutable
     */
    public function testQueryParamsAreNullable(ResourceEvent $event)
    {
        $event->setQueryParams(null);
        $this->assertNull($event->getQueryParams());
    }

    /**
     * @depends testRequestIsMutable
     */
    public function testRequestIsNullable(ResourceEvent $event)
    {
        $event->setRequest(null);
        $this->assertNull($event->getRequest());
    }

    public function testCanInjectRequestViaSetParams()
    {
        $request = new HttpRequest();
        $this->event->setParams(array('request' => $request));
        $this->assertSame($request, $this->event->getRequest());
    }

    public function testCanFetchIndividualRouteParameter()
    {
        $this->event->setRouteMatch($this->matches);
        $this->assertEquals('bar', $this->event->getRouteParam('foo'));
        $this->assertEquals('inga', $this->event->getRouteParam('baz'));
    }

    public function testCanFetchIndividualQueryParameter()
    {
        $this->event->setQueryParams($this->query);
        $this->assertEquals('bar', $this->event->getQueryParam('foo'));
        $this->assertEquals('inga', $this->event->getQueryParam('baz'));
    }

    public function testReturnsDefaultParameterWhenPullingUnknownRouteParameter()
    {
        $this->assertNull($this->event->getRouteParam('foo'));
        $this->assertEquals('bat', $this->event->getRouteParam('baz', 'bat'));
    }

    public function testReturnsDefaultParameterWhenPullingUnknownQueryParameter()
    {
        $this->assertNull($this->event->getQueryParam('foo'));
        $this->assertEquals('bat', $this->event->getQueryParam('baz', 'bat'));
    }

    public function testInputFilterIsUndefinedByDefault()
    {
        $this->assertNull($this->event->getInputFilter());
    }

    /**
     * @depends testInputFilterIsUndefinedByDefault
     */
    public function testCanComposeInputFilter()
    {
        $inputFilter = new InputFilter();
        $this->event->setInputFilter($inputFilter);
        $this->assertSame($inputFilter, $this->event->getInputFilter());
    }

    /**
     * @depends testCanComposeInputFilter
     */
    public function testCanNullifyInputFilter()
    {
        $this->event->setInputFilter(null);
        $this->assertNull($this->event->getInputFilter());
    }

    public function testIdentityIsUndefinedByDefault()
    {
        $this->assertNull($this->event->getIdentity());
    }

    /**
     * @depends testIdentityIsUndefinedByDefault
     */
    public function testCanComposeIdentity()
    {
        $identity = new GuestIdentity();
        $this->event->setIdentity($identity);
        $this->assertSame($identity, $this->event->getIdentity());
    }

    /**
     * @depends testCanComposeIdentity
     */
    public function testCanNullifyIdentity()
    {
        $this->event->setIdentity(null);
        $this->assertNull($this->event->getIdentity());
    }
}
