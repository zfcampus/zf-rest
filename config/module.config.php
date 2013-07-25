<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

return array(
    'zf-rest' => array(
        'renderer'         => array(),
        'resources'        => array(),
        'controller_class' => 'ZF\Rest\ResourceController',
    ),

    'service_manager' => array(
        'invokables' => array(
            'ZF\Rest\ResourceParametersListener' => 'ZF\Rest\Listener\ResourceParametersListener',
        ),
    ),

    'controllers' => array(
        'abstract_factories' => array(
            'ZF\Rest\Factory\ResourceControllerFactory'
        )
    ),

    'view_manager' => array(
        // Enable this in your application configuration in order to get full
        // exception stack traces in your API-Problem responses.
        'display_exceptions' => false,
    ),
);
