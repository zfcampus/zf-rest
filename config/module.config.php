<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

return array(
    'zf-rest' => array(
        // 'Name of virtual controller' => array(
        //     'controller_class'        => 'Name of ZF\Rest\RestController derivative, if not using that class',
        //     'listener'                => 'Name of service/class that acts as a listener on the composed Resource',
        //     'route_name'              => 'Name of the route that will map to this controller',
        //     'identifier_name'         => 'Name of parameter in route that acts as a resource identifier',
        //     'resource_http_options'   => array(
        //         /* array of HTTP options that are allowed on individual resources */
        //         'get', 'post', 'delete'
        //     ),
        //     'collection_http_options' => array(
        //         /* array of HTTP options that are allowed on collections */
        //         'get'
        //     ),
        // ),
        // repeat for each controller you want to define
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
