<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

return array(
    'zf-rest' => array(
        // 'Name of virtual controller' => array(
        //     'collection_http_methods'    => array(
        //         /* array of HTTP methods that are allowed on collections */
        //         'get'
        //     ),
        //     'collection_name'            => 'Name of property denoting collection in response',
        //     'collection_query_whitelist' => arrray(
        //         /* array of query string parameters to whitelist and return
        //          * when generating links to the collection. E.g., "sort",
        //          * "filter", etc.
        //          */
        //     ),
        //     'content_types'              => array(
        //         /* "content type"/array of media type pairs. These can be used
        //          * to determine how to parse incoming data by a listener.
        //          * See zf-content-negotiation to get an idea how this may be
        //          * used.
        //          */
        //     ),
        //     'controller_class'           => 'Name of ZF\Rest\RestController derivative, if not using that class',
        //     'identifier_name'            => 'Name of parameter in route that acts as a resource identifier',
        //     'listener'                   => 'Name of service/class that acts as a listener on the composed Resource',
        //     'page_size'                  => 'Integer specifying the number of results to return per page, if collections are paginated',
        //     'page_size_param'            => 'Name of query string parameter that specifies the number of results to return per page',
        //     'resource_http_methods'      => array(
        //         /* array of HTTP methods that are allowed on individual resources */
        //         'get', 'post', 'delete'
        //     ),
        //     'route_name'                 => 'Name of the route that will map to this controller',
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
