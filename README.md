ZF REST
=======

[![Build Status](https://travis-ci.org/zfcampus/zf-rest.png)](https://travis-ci.org/zfcampus/zf-rest)

Introduction
------------

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

The top-level key used to configure this module is `zf-rest`

#### Key: Controller Service Name

##### Sub-key: `collection_http_methods`

An array of HTTP methods that are allowed for the collection.

##### Sub-key: `entity_http_methods`

An arra of HTTP methods that are allowed for the entity.

##### Sub-key: `collection_name`

The name of property denoting collection in response.

##### Sub-key: `collection_query_whitelist`

An array of query string parameters to whitelist and return when generating links to the
collection. E.g., "sort", "filter", etc.

##### Sub-key: `content_types`

"content type"/array of media type pairs. These can be used to determine how to parse incoming
data by a listener.  See zf-content-negotiation to get an idea how this may be used.

##### Sub-key: `controller_class` (optional)

The `ZF\Rest\RestController` based class.  This is generally useful when overriding the default,
which is to use `ZF\Rest\RestController`.

##### Sub-key: `entity_class`

The class to be used as the entity.  Primarily useful for introspection (for example in the admin
UI for Apigility).

##### Sub-key: `route_name`

The back reference to the route name for this REST service.  This is utilized when links need
to be generated in the response.

##### Sub-key: `route_identifier_name`

The parameter name for the identifier in the route specification.

##### Sub-key: `listener`

The resource class that will be dispatched to handle any collection or entity requests.

##### Sub-key: `page_size`

The maximum size of the collection.

##### Sub-key: `page_size_param`

The name of the parameter that will determine page size, if provided.

Example:

```php
'AddressBook\\V1\\Rest\\Contact\\Controller' => array(
    'listener' => 'AddressBook\\V1\\Rest\\Contact\\ContactResource',
    'route_name' => 'address-book.rest.contact',
    'route_identifier_name' => 'contact_id',
    'collection_name' => 'contact',
    'entity_http_methods' => array(
        0 => 'GET',
        1 => 'PATCH',
        2 => 'PUT',
        3 => 'DELETE',
    ),
    'collection_http_methods' => array(
        0 => 'GET',
        1 => 'POST',
    ),
    'collection_query_whitelist' => array(),
    'page_size' => 25,
    'page_size_param' => null,
    'entity_class' => 'AddressBook\\V1\\Rest\\Contact\\ContactEntity',
    'collection_class' => 'AddressBook\\V1\\Rest\\Contact\\ContactCollection',
    'service_name' => 'Contact',
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

#### `ZF\Rest\Listener\OptionsListener

This listeners is registered to the `MvcEvent::EVENT_ROUTE` with a priority of -100.  It is
primarily responsible for ensuring the HTTP response to this REST request includes the properly
configured and allowed HTTP methods in the `Allow` header.  This uses the configuration from
the `http_methods` key of the `zf-rest` service configuration for the matching service.

#### `ZF\Rest\Listener\RestParametersListener`

This listener is attached to the shared `dispatch` event at priority `100`.  The primary
responsibility of this listener is to map query parameters from the Request and the
RouteMatch into the Resource listener to be dispatched at dispatch time.

ZF2 Services
============

### Models

#### `ZF\Rest\AbstractResourceListener`

This abstract class is the base implementation of a resource listener.  Since dispatching
of `zf-rest` based REST services is event driven, a listener must be constructed to listen
for the proper event triggered from `ZF\Rest\Resource` (which is dispached by the
`RestController`).  This particular listener, in addition to the event wiring, also does
some introspection to determine if the current HTTP method pertains to the resource
*collection* (all resources) or a particular *entity* (a single resource, with a particular
identity).  This is evident in that the route used to get to a particular
`AbstractResourceListener` will have used a particular `route_identifier_name` present as
an HTTP query parameter.  The following methods are called during dispatch(), depending
on the HTTP method and query parameter context:

- `create()` - Generally the code for a POST request to a resource *collection*
- `delete()` - Generally the code for a DELETE request to a resource *entity*
- `deleteList()` - Generally the code for a DELETE request to a resource *entity*
- `fetch()` - Generally the code for a GET request to a resource *entity*
- `fetchAll()` - Generally the code for a GET request to a resource *collection*
- `patch()` - Generally the code for a PATCH request to resource *entity*
- `patchList()` - Generally the code for a PATCH request to a resource *collection*
- `update()` - Generally the code for a PUT request to a resource *entity*
- `replaceList()` - Generally the code for a PUT request to a resource *collection*

#### `ZF\Rest\Resource`

This is the instance responsible for handling the dispatching of REST based business
logic.  `Resource` composes an instance of the `EventManager` in order to make and
event based dispatcher for the delegation to the users instance of the
`AbstractResourceListener`.  The methods in `Resource` each, when dispatched, will in
turn trigger an event that instances of `AbstractResourceListener::dispatch()` will
be listening for.

### Controller

#### `ZF\Rest\RestController`

This is the base controller implementation which is used when a controller service name
matches a `zf-rest` configured REST service.  This instance is produced by the
`ZF\Rest\Factory\RestControllerFactory` abstract factory for controllers.  This controller
then delegates to the proper method in `ZF\Rest\Resource` based on the HTTP method
that was utilized for the request.  This implementation builds on the ZF2
`Zend\Mvc\Controller\AbstractRestfulController`

