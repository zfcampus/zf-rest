<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

return array(
    'zf-rest' => array(
        'controllers'      => array(),
        'controller_class' => 'ZF\Rest\RestController',
    ),

    'service_manager' => array(
        'invokables' => array(
            'ZF\Rest\RestParametersListener' => 'ZF\Rest\Listener\RestParametersListener',
        ),
    ),

    'controllers' => array(
        'abstract_factories' => array(
            'ZF\Rest\Factory\RestControllerFactory'
        )
    ),

    'view_manager' => array(
        // Enable this in your application configuration in order to get full
        // exception stack traces in your API-Problem responses.
        'display_exceptions' => false,
    ),
);
