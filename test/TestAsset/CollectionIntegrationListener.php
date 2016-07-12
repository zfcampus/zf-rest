<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\TestAsset;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;

class CollectionIntegrationListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    public $collection;

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('fetchAll', [$this, 'onFetchAll']);
    }

    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    public function onFetchAll($e)
    {
        return $this->collection;
    }
}
