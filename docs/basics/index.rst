.. _basics.index:

ZFRest Basics
=============

ZFRest allows you to create RESTful JSON APIs that adhere to
:ref:`Hypermedia Application Language <zfrest.hal-primer>`. For error
handling, it uses :ref:`API-Problem <zfrest.error-reporting>`.

The pieces you need to implement, work with, or understand are:

- Writing event listeners for the various ``ZF\Rest\Resource`` events,
  which will be used to either persist resources or fetch resources from
  persistence.

- Writing routes for your resources, and associating them with resources and/or
  ``ZF\Rest\ResourceController``.

- Writing metadata describing your resources, including what routes to associate
  with them.

All API calls are handled by ``ZF\Rest\ResourceController``, which in
turn composes a ``ZF\Rest\Resource`` object and calls methods on it. The
various methods of the controller will return either
``ZF\Rest\ApiProblem`` results on error conditions, or, on success, a
``ZF\Rest\HalResource`` or ``ZF\Rest\HalCollection`` instance; these
are then composed into a ``ZF\Rest\View\RestfulJsonModel``.

If the MVC detects a ``ZF\Rest\View\RestfulJsonModel`` during rendering,
it will select ``ZF\Rest\View\RestfulJsonRenderer``. This, with the help
of the ``ZF\Rest\Plugin\HalLinks`` plugin, will generate an appropriate
payload based on the object composed, and ensure the appropriate Content-Type
header is used.

If a ``ZF\Rest\HalCollection`` is detected, and the renderer determines
that it composes a ``Zend\Paginator\Paginator`` instance, the ``HalLinks``
plugin will also generate pagination relational links to render in the payload.
