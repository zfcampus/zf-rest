<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use Zend\Mvc\Router\Http\SegmentRoute as V2SegmentRoute;
use Zend\Router\Http\Segment as SegmentRoute;

trait SegmentRouteFactoryTrait
{
    /**
     * Create and return a version-specific SegmentRoute instance.
     *
     * Passes all provided arguments to the constructor.
     *
     * @return SegmentRoute|V2SegmentRoute
     */
    public function createSegmentRoute(...$params)
    {
        $class = class_exists(V2SegmentRoute::class) ? V2SegmentRoute::class : SegmentRoute::class;
        return new $class(...$params);
    }
}
