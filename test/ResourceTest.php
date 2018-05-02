<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\Http\Response;
use Zend\Stdlib\ArrayObject;
use Zend\Stdlib\Parameters;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\Exception\InvalidArgumentException;
use ZF\Rest\Resource;
use ZF\Rest\ResourceEvent;
use ZF\Rest\ResourceInterface;

/**
 * @subpackage UnitTest
 */
class ResourceTest extends TestCase
{
    use RouteMatchFactoryTrait;

    /** @var EventManager */
    private $events;

    /** @var Resource */
    private $resource;

    public function setUp()
    {
        $this->events   = new EventManager();
        $this->resource = new Resource();
        $this->resource->setEventManager($this->events);
    }

    public function testEventManagerIdentifiersAreAsExpected()
    {
        $expected = [
            Resource::class,
            ResourceInterface::class,
        ];
        $identifiers = $this->events->getIdentifiers();
        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function badData()
    {
        return [
            'null'   => [null],
            'bool'   => [true],
            'int'    => [1],
            'float'  => [1.0],
            'string' => ['data'],
        ];
    }

    /**
     * @dataProvider badData
     *
     * @param mixed $data
     */
    public function testCreateRaisesExceptionWithInvalidData($data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resource->create($data);
    }

    public function testEventParamsReturnDefaultValueOnNonExistingParam()
    {
        $this->assertEquals('world', $this->resource->getEventParam('hello', 'world'));
    }

    public function testSameInstanceReturnedByEventParams()
    {
        $instance = new ArrayObject();

        $this->resource->setEventParam('instance', $instance);

        $this->assertEquals($instance, $this->resource->getEventParam('instance'));
    }

    public function testClearOldParamsOnSetEventParams()
    {
        $this->resource->setEventParam('world', 'hello');

        $params = ['hello' => 'world'];

        $this->resource->setEventParams($params);

        $this->assertEquals($params, $this->resource->getEventParams());
    }

    public function testCreateReturnsResultOfLastListener()
    {
        $this->events->attach('create', function ($e) {
            return null;
        });
        $object = new stdClass();
        $this->events->attach('create', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->create([]);
        $this->assertSame($object, $test);
    }

    public function testCreateReturnsDataIfLastListenerDoesNotReturnResource()
    {
        $data = new stdClass();
        $object = new stdClass();
        $this->events->attach('create', function ($e) use ($object) {
            return $object;
        });
        $this->events->attach('create', function ($e) {
            return null;
        });

        $test = $this->resource->create($data);
        $this->assertSame($data, $test);
    }

    /**
     * @dataProvider badData
     */
    public function testUpdateRaisesExceptionWithInvalidData($data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resource->update('foo', $data);
    }

    public function testUpdateReturnsResultOfLastListener()
    {
        $this->events->attach('update', function ($e) {
            return null;
        });
        $object = new stdClass();
        $this->events->attach('update', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->update('foo', []);
        $this->assertSame($object, $test);
    }

    public function testUpdateReturnsDataIfLastListenerDoesNotReturnResource()
    {
        $data = new stdClass();
        $object = new stdClass();
        $this->events->attach('update', function ($e) use ($object) {
            return $object;
        });
        $this->events->attach('update', function ($e) {
            return null;
        });

        $test = $this->resource->update('foo', $data);
        $this->assertSame($data, $test);
    }

    /**
     * @dataProvider badUpdateCollectionData
     *
     * @param mixed $data
     */
    public function testReplaceListRaisesExceptionWithInvalidData($data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data');
        $this->expectExceptionCode(400);

        $this->resource->replaceList($data);
    }

    public function testReplaceListReturnsResultOfLastListener()
    {
        $this->events->attach('replaceList', function ($e) {
            return null;
        });
        $object = [new stdClass()];
        $this->events->attach('replaceList', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->replaceList([[]]);
        $this->assertSame($object, $test);
    }

    public function testReplaceListReturnsDataIfLastListenerDoesNotReturnResource()
    {
        $data = [new stdClass()];
        $object = new stdClass();
        $this->events->attach('replaceList', function ($e) use ($object) {
            return $object;
        });
        $this->events->attach('replaceList', function ($e) {
            return null;
        });

        $test = $this->resource->replaceList($data);
        $this->assertSame($data, $test);
    }

    /**
     * @dataProvider badData
     *
     * @param mixed $data
     */
    public function testPatchRaisesExceptionWithInvalidData($data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resource->patch('foo', $data);
    }

    public function testPatchReturnsResultOfLastListener()
    {
        $this->events->attach('patch', function ($e) {
            return null;
        });
        $object = new stdClass();
        $this->events->attach('patch', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->patch('foo', []);
        $this->assertSame($object, $test);
    }

    public function testPatchReturnsDataIfLastListenerDoesNotReturnResource()
    {
        $data = new stdClass();
        $object = new stdClass();
        $this->events->attach('patch', function ($e) use ($object) {
            return $object;
        });
        $this->events->attach('patch', function ($e) {
            return null;
        });

        $test = $this->resource->patch('foo', $data);
        $this->assertSame($data, $test);
    }

    public function testDeleteReturnsResultOfLastListenerIfBoolean()
    {
        $this->events->attach('delete', function ($e) {
            return new stdClass();
        });
        $this->events->attach('delete', function ($e) {
            return true;
        });

        $test = $this->resource->delete('foo', []);
        $this->assertTrue($test);
    }

    public function testDeleteReturnsFalseIfLastListenerDoesNotReturnBoolean()
    {
        $this->events->attach('delete', function ($e) {
            return true;
        });
        $this->events->attach('delete', function ($e) {
            return new stdClass();
        });

        $test = $this->resource->delete('foo');
        $this->assertFalse($test);
    }

    public function badDeleteCollections()
    {
        return [
            'true'     => [true],
            'int'      => [1],
            'float'    => [1.1],
            'string'   => ['string'],
            'stdClass' => [new stdClass()],
        ];
    }

    /**
     * @dataProvider badDeleteCollections
     *
     * @param mixed $data
     */
    public function testDeleteListRaisesInvalidArgumentExceptionForInvalidData($data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('::deleteList');
        $this->resource->deleteList($data);
    }

    public function testDeleteListReturnsResultOfLastListenerIfBoolean()
    {
        $this->events->attach('deleteList', function ($e) {
            return new stdClass();
        });
        $this->events->attach('deleteList', function ($e) {
            return true;
        });

        $test = $this->resource->deleteList([]);
        $this->assertTrue($test);
    }

    public function testDeleteListReturnsFalseIfLastListenerDoesNotReturnBoolean()
    {
        $this->events->attach('deleteList', function ($e) {
            return true;
        });
        $this->events->attach('deleteList', function ($e) {
            return new stdClass();
        });

        $test = $this->resource->deleteList([]);
        $this->assertFalse($test);
    }

    public function testFetchReturnsResultOfLastListener()
    {
        $this->events->attach('fetch', function ($e) {
            return true;
        });
        $object = new stdClass();
        $this->events->attach('fetch', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->fetch('foo');
        $this->assertSame($object, $test);
    }

    /**
     * @dataProvider badData
     *
     * @param mixed $return
     */
    public function testFetchReturnsFalseIfLastListenerDoesNotReturnArrayOrObject($return)
    {
        $this->events->attach('fetch', function ($e) use ($return) {
            return $return;
        });
        $test = $this->resource->fetch('foo');
        $this->assertFalse($test);
    }

    public function invalidCollection()
    {
        return [
            'null'   => [null],
            'bool'   => [true],
            'int'    => [1],
            'float'  => [1.0],
            'string' => ['data'],
        ];
    }

    /**
     * @group 31
     *
     * @dataProvider invalidCollection
     *
     * @param mixed $return
     */
    public function testFetchAllReturnsEmptyArrayIfLastListenerReturnsScalar($return)
    {
        $this->events->attach('fetchAll', function ($e) use ($return) {
            return $return;
        });
        $test = $this->resource->fetchAll();
        $this->assertEquals([], $test);
    }

    public function testFetchAllReturnsResultOfLastListener()
    {
        $this->events->attach('fetchAll', function ($e) {
            return true;
        });
        $object = new ArrayIterator([]);
        $this->events->attach('fetchAll', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->fetchAll();
        $this->assertSame($object, $test);
    }

    public function eventsToTrigger()
    {
        $id = 'resource_id';

        $resource = [
            'id'  => $id,
            'foo' => 'foo',
            'bar' => 'bar',
        ];

        $collection = [$resource];

        return [
            'create' => ['create', [$resource], false],
            'update' => ['update', [$id, $resource], true],
            'replaceList' => ['replaceList', [$collection], false],
            'patch' => ['patch', [$id, $resource], true],
            'patchList' => ['patchList', [$collection], false],
            'delete' => ['delete', [$id], true],
            'deleteList' => ['deleteList', [$collection], false],
            'fetch' => ['fetch', [$id], true],
            'fetchAll' => ['fetchAll', [], false],
        ];
    }

    /**
     * @dataProvider eventsToTrigger
     *
     * @param string $eventName
     * @param array $args
     */
    public function testEventTerminateIfApiProblemIsReturned($eventName, array $args)
    {
        $called = false;

        $this->events->attach($eventName, function () {
            return new ApiProblem(400, 'Random error');
        }, 10);

        $this->events->attach($eventName, function () use (&$called) {
            $called = true;
        }, 0);

        call_user_func_array([$this->resource, $eventName], $args);

        $this->assertFalse($called);
    }

    /**
     * @dataProvider eventsToTrigger
     *
     * @param string $eventName
     * @param array $args
     * @param bool $idIsPresent
     */
    public function testEventParametersAreInjectedIntoEventWhenTriggered($eventName, array $args, $idIsPresent)
    {
        $test = (object) [];
        $this->events->attach($eventName, function ($e) use ($test) {
            $test->event = $e;
        });
        $this->resource->setEventParam('id', 'OVERWRITTEN');
        $this->resource->setEventParam('parent_id', 'parent_id');

        call_user_func_array([$this->resource, $eventName], $args);

        $this->assertObjectHasAttribute('event', $test);
        $e = $test->event;

        if ($idIsPresent) {
            $this->assertNotFalse($e->getParam('id', false));
            $this->assertNotEquals('OVERWRITTEN', $e->getParam('id'));
        }

        $this->assertNotFalse($e->getParam('parent_id', false));
        $this->assertEquals('parent_id', $e->getParam('parent_id'));
    }

    /**
     * @dataProvider eventsToTrigger
     *
     * @param string $eventName
     * @param array $args
     */
    public function testComposedQueryParametersAndRouteMatchesAreInjectedIntoEvent($eventName, array $args)
    {
        $test = (object) [];
        $this->events->attach($eventName, function ($e) use ($test) {
            $test->event = $e;
        });
        $matches     = $this->createRouteMatch([]);
        $queryParams = new Parameters();
        $this->resource->setRouteMatch($matches);
        $this->resource->setQueryParams($queryParams);

        call_user_func_array([$this->resource, $eventName], $args);

        $this->assertObjectHasAttribute('event', $test);
        $e = $test->event;

        $this->assertInstanceOf(ResourceEvent::class, $e);
        $this->assertSame($matches, $e->getRouteMatch());
        $this->assertSame($queryParams, $e->getQueryParams());
    }

    public function badUpdateCollectionData()
    {
        return [
            'object'    => [new stdClass()],
            'notnested' => [[null]],
        ];
    }

    /**
     * @dataProvider badData
     * @dataProvider badUpdateCollectionData
     *
     * @param mixed $data
     */
    public function testPatchListListRaisesExceptionWithInvalidData($data)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data');
        $this->expectExceptionCode(400);

        $this->resource->patchList($data);
    }

    public function testPatchListReturnsResultOfLastListener()
    {
        $this->events->attach('patchList', function ($e) {
            return null;
        });
        $object = [new stdClass];
        $this->events->attach('patchList', function ($e) use ($object) {
            return $object;
        });

        $test = $this->resource->patchList([[]]);
        $this->assertSame($object, $test);
    }

    public function testPatchListReturnsDataIfLastListenerDoesNotReturnResource()
    {
        $data = [new stdClass()];
        $object = new stdClass();
        $this->events->attach('patchList', function ($e) use ($object) {
            return $object;
        });
        $this->events->attach('patchList', function ($e) {
            return null;
        });

        $test = $this->resource->patchList($data);
        $this->assertSame($data, $test);
    }

    /**
     * @group 31
     */
    public function testFetchAllShouldAllowReturningArbitraryObjects()
    {
        $return = (object) ['foo' => 'bar'];
        $this->events->attach('fetchAll', function ($e) use ($return) {
            return $return;
        });
        $test = $this->resource->fetchAll();
        $this->assertSame($return, $test);
    }

    public function actions()
    {
        return [
            'get-list'    => ['fetchAll', [null]],
            'get'         => ['fetch', [1]],
            'post'        => ['create', [[]]],
            'put-list'    => ['replaceList', [[]]],
            'put'         => ['update', [1, []]],
            'patch-list'  => ['patchList', [[]]],
            'patch'       => ['patch', [1, []]],
            'delete-list' => ['deleteList', [[]]],
            'delete'      => ['delete', [1]],
        ];
    }

    /**
     * @group 68
     *
     * @dataProvider actions
     *
     * @param string $action
     * @param array $argv
     */
    public function testAllowsReturningResponsesReturnedFromResources($action, array $argv)
    {
        $response = new Response();
        $response->setStatusCode(418);

        $events = $this->resource->getEventManager();
        $events->attach($action, function ($e) use ($response) {
            return $response;
        });

        $result = call_user_func_array([$this->resource, $action], $argv);
        $this->assertSame($response, $result);
    }
}
