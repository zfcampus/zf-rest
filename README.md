ZF REST
=======

[![Build Status](https://travis-ci.org/zfcampus/zf-rest.png)](https://travis-ci.org/zfcampus/zf-rest)

Introduction
------------

This module provides structure and code for quickly implementing RESTful APIs
that use JSON as a transport.

It allows you to create RESTful JSON APIs that use the following standards:

- [Hypermedia Application Language](http://tools.ietf.org/html/draft-kelly-json-hal-06), aka HAL,
  used for creating JSON payloads with hypermedia controls.
- [Problem Details for HTTP APIs](http://tools.ietf.org/html/draft-nottingham-http-problem-06),
  aka API Problem, used for reporting API problems.

Requirements
------------
  
Please see the [composer.json](composer.json) file.

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
-------------

### User Configuration

The top-level key used to configure this module is `zf-rest`.

#### Key: Controller Service Name

Each key under `zf-rest` is a controller service name, and the value is an array with one or more of
the following keys.

##### Sub-key: `collection_http_methods`

An array of HTTP methods that are allowed when making requests to a collection.

##### Sub-key: `entity_http_methods`

An array of HTTP methods that are allowed when making requests for entities.

##### Sub-key: `collection_name`

The name of the embedded property in the representation denoting the collection.

##### Sub-key: `collection_query_whitelist` (optional)

An array of query string arguments to whitelist for collection requests and when generating links
to collections. These parameters will be passed to the resource class' `fetchAll()` method. Any of
these parameters present in the request will also be used when generating links to the collection.

Examples of query string arguments you may want to whitelist include "sort", "filter", etc.

##### Sub-key: `controller_class` (optional)

An alternate controller class to use when creating the controller service; it **must** extend
`ZF\Rest\RestController`. Only use this if you are altering the workflow present in the
`RestController`.

##### Sub-key: `identifier` (optional)

The name of event identifier for controller. It allows multiple instances of controller to react
to different sets of shared events.

##### Sub-key: `resource_identifiers` (optional)

The name or an array of names of event identifier/s for resource.

##### Sub-key: `entity_class`

The class to be used for representing an entity.  Primarily useful for introspection (for example in
the Apigility Admin UI).

##### Sub-key: `route_name`

The route name associated with this REST service.  This is utilized when links need to be generated
in the response.

##### Sub-key: `route_identifier_name`

The parameter name for the identifier in the route specification.

##### Sub-key: `listener`

The resource class that will be dispatched to handle any collection or entity requests.

##### Sub-key: `page_size`

The number of entities to return per "page" of a collection. This is only used if the collection
returned is a `Zend\Paginator\Paginator` instance or derivative.

##### Sub-key: `max_page_size` (optional)

The maximum number of entities to return per "page" of a collection.  This is tested against the
`page_size_param`. This parameter can be set to help prevent denial of service attacks against your API.

##### Sub-key: `min_page_size` (optional)

The minimum number of entities to return per "page" of a collection.  This is tested against the
`page_size_param`.

##### Sub-key: `page_size_param` (optional)

The name of a query string argument that will set a per-request page size. Not set by default; we
recommend having additional logic to ensure a ceiling for the page size as well, to prevent denial
of service attacks on your API.

#### User configuration example:

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

The `zf-rest` module provides the following configuration to ensure it operates properly in a Zend
Framework 2 application.

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

#### ZF\Rest\Listener\OptionsListener

This listener is registered to the `MvcEvent::EVENT_ROUTE` event with a priority of `-100`. 
It serves two purposes:

- If a request is made to either a REST entity or collection with a method they do not support, it
  will return a `405 Method not allowed` response, with a populated `Allow` header indicating which
  request methods may be used.
- For `OPTIONS` requests, it will respond with a `200 OK` response and a populated `Allow` header
  indicating which request methods may be used.

#### ZF\Rest\Listener\RestParametersListener

This listener is attached to the shared `dispatch` event at priority `100`.  The listener maps query
string arguments from the request to the `Resource` object composed in the `RestController`, as well
as injects the `RouteMatch`.

ZF2 Services
============

### Models

#### ZF\Rest\AbstractResourceListener

This abstract class is the base implementation of a [Resource](#zfrestresource) listener.  Since
dispatching of `zf-rest` based REST services is event driven, a listener must be constructed to
listen for events triggered from `ZF\Rest\Resource` (which is called from the `RestController`).
The following methods are called during `dispatch()`, depending on the HTTP method:

- `create($data)` - Triggered by a `POST` request to a resource *collection*.
- `delete($id)` - Triggered by a `DELETE` request to a resource *entity*.
- `deleteList($data)` - Triggered by a `DELETE` request to a resource *collection*.
- `fetch($id)` - Triggered by a `GET` request to a resource *entity*.
- `fetchAll($params = array())` - Triggered by a `GET` request to a resource *collection*.
- `patch($id, $data)` - Triggered by a `PATCH` request to resource *entity*.
- `patchList($data)` - Triggered by a `PATCH` request to a resource *collection*.
- `update($id, $data)` - Triggered by a `PUT` request to a resource *entity*.
- `replaceList($data)` - Triggered by a `PUT` request to a resource *collection*.

#### ZF\Rest\Resource

The `Resource` object handles dispatching business logic for REST requests. It composes an
`EventManager` instance in order to delegate operations to attached listeners. Additionally, it
composes request information, such as the `Request`, `RouteMatch`, and `MvcEvent` objects, in order
to seed the `ResourceEvent` it creates and passes to listeners when triggering events.

### Controller

#### ZF\Rest\RestController

This is the base controller implementation used when a controller service name matches a configured
REST service. All REST services managed by `zf-rest` will use this controller (though separate
instances of it), unless they specify a [controller_class](#subkeycontrollerclassoptional) option.
Instances are created via the `ZF\Rest\Factory\RestControllerFactory` abstract factory.

The `RestController` calls the appropriate method in `ZF\Rest\Resource` based on the requested HTTP
method. It returns [HAL](https://github.com/zfcampus/zf-hal) payloads on success, and [API
Problem](https://github.com/zfcampus/zf-api-problem) responses on error.
