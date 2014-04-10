ZF REST
=======

[![Build Status](https://travis-ci.org/zfcampus/zf-rest.png)](https://travis-ci.org/zfcampus/zf-rest)

Introduction
------------

[![Build Status](https://travis-ci.org/zfcampus/zf-rest.png)](https://travis-ci.org/zfcampus/zf-rest)
[![Coverage Status](https://coveralls.io/repos/zfcampus/zf-rest/badge.png?branch=master)](https://coveralls.io/r/zfcampus/zf-rest)

This module provides structure and code for quickly implementing RESTful APIs
that use JSON as a transport.

It allows you to create RESTful JSON APIs that use the following standards:

- [HAL](http://tools.ietf.org/html/draft-kelly-json-hal-03), used for creating
  hypermedia links
- [Problem API](http://tools.ietf.org/html/draft-nottingham-http-problem-02),
  used for reporting API problems

Installation
------------

Run the following `composer` command:

```console
$ composer require "zfcampus/zf-rest:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-rest": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'ZF\Rest',
    ),
    /* ... */
);
```


Configuration
=============

### User Configuration

```php
'zf-rest' => array(
    // 'Name of virtual controller' => array(
    //     'collection_http_methods'    => array(
    //         /* array of HTTP methods that are allowed on collections */
    //         'get'
    //     ),
    //     'collection_name'            => 'Name of property denoting collection in response',
    //     'collection_query_whitelist' => array(
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
    //     'route_identifier_name'      => 'Name of parameter in route that acts as an entity identifier',
    //     'listener'                   => 'Name of service/class that acts as a listener on the composed Resource',
    //     'page_size'                  => 'Integer specifying the number of results to return per page, if collections are paginated',
    //     'page_size_param'            => 'Name of query string parameter that specifies the number of results to return per page',
    //     'entity_http_methods'      => array(
    //         /* array of HTTP methods that are allowed on individual entities */
    //         'get', 'post', 'delete'
    //     ),
    //     'route_name'                 => 'Name of the route that will map to this controller',
    // ),
    // repeat for each controller you want to define
),
```

### System Configuration

```php
'service_manager' => array(
    'invokables' => array(
        'ZF\Rest\RestParametersListener' => 'ZF\Rest\Listener\RestParametersListener',
    ),
    'factories' => array(
        'ZF\Rest\OptionsListener' => 'ZF\Rest\Factory\OptionsListenerFactory',
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
```

ZF2 Events
==========

### Listeners

#### `ZF\Rest\Listener\OptionsListener`

#### `ZF\Rest\Listener\RestParametersListener`

ZF2 Services
============

### Models

#### `ZF\Rest\Resource`

### Controller

#### `ZF\Rest\Controller`
