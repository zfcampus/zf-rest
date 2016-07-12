<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest;

use Zend\Mvc\Router\SimpleRouteStack as V2SimpleRouteStack;
use Zend\Router\SimpleRouteStack;

trait SimpleRouteStackFactoryTrait
{
    /**
     * Create and return a version-specific SimpleRouteStack instance.
     *
     * @return SimpleRouteStack|V2SimpleRouteStack
     */
    public function createSimpleRouteStack()
    {
        $class = class_exists(V2SimpleRouteStack::class) ? V2SimpleRouteStack::class : SimpleRouteStack::class;
        return new $class();
    }
}
