<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use Zend\Mvc\Router\Http\TreeRouteStack as V2TreeRouteStack;
use Zend\Router\Http\TreeRouteStack;

trait TreeRouteStackFactoryTrait
{
    /**
     * Create and return a version-specific TreeRouteStack instance.
     *
     * @return TreeRouteStack|V2TreeRouteStack
     */
    public function createTreeRouteStack()
    {
        $class = class_exists(V2TreeRouteStack::class) ? V2TreeRouteStack::class : TreeRouteStack::class;
        return new $class();
    }
}
