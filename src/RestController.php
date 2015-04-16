<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest;

use ArrayAccess;
use Traversable;
use Zend\Http\Header\Allow;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ApiProblem\Exception\DomainException;
use ZF\ContentNegotiation\ViewModel as ContentNegotiationViewModel;
use ZF\Hal\Collection as HalCollection;
use ZF\Hal\Entity as HalEntity;

/**
 * Controller for handling resources.
 *
 * Extends the base AbstractRestfulController in order to provide very specific
 * semantics for building a RESTful JSON service. All operations return either
 *
 * - a HAL-compliant response with appropriate hypermedia links
 * - a Problem API-compliant response for reporting an error condition
 *
 * You may specify what specific HTTP method types you wish to respond to, and
 * OPTIONS will then report those; attempting any HTTP method falling outside
 * that list will result in a 405 (Method Not Allowed) response.
 *
 * I recommend using resource-specific factories when using this controller,
 * to allow injecting the specific resource you wish to use (and its listeners),
 * which will also allow you to have multiple instances of the controller when
 * desired.
 *
 * @see http://tools.ietf.org/html/draft-kelly-json-hal-03
 * @see http://tools.ietf.org/html/draft-nottingham-http-problem-02
 */
class RestController extends AbstractRestfulController
{
    /**
     * HTTP methods we allow for the collections; used by options()
     *
     * HEAD and OPTIONS are always available.
     *
     * @var array
     */
    protected $collectionHttpMethods = array(
        'GET',
        'POST',
    );

    /**
     * Name of the collections entry in a Collection
     *
     * @var string
     */
    protected $collectionName = 'items';

    /**
     * Minimum number of entities to return per page of a collection.  If
     * $pageSize parameter is out of range an ApiProblem will be returned
     *
     * @var int
     */
    protected $minPageSize;

    /**
     * Number of entities to return per page of a collection.  If
     * $pageSize parameter is specified, then it will override this when
     * provided in a request.
     *
     * @var int
     */
    protected $pageSize = 30;

    /**
     * Maximum number of entities to return per page of a collection.  If
     * $pageSize parameter is out of range an ApiProblem will be returned
     *
     * @var int
     */
    protected $maxPageSize;

    /**
     * A query parameter to use to specify the number of records to return in
     * each collection page.  If not provided, $pageSize will be used as a
     * default value.
     *
     * Leave null to disable this functionality and always use $pageSize.
     *
     * @var string
     */
    protected $pageSizeParam;

    /**
     * @var ResourceInterface
     */
    protected $resource;

    /**
     * HTTP methods we allow for individual entities; used by options()
     *
     * HEAD and OPTIONS are always available.
     *
     * @var array
     */
    protected $entityHttpMethods = array(
        'DELETE',
        'GET',
        'PATCH',
        'PUT',
    );

    /**
     * Route name that resolves to this resource; used to generate links.
     *
     * @var string
     */
    protected $route;

    /**
     * Constructor
     *
     * Allows you to set the event identifier, which can be useful to allow multiple
     * instances of this controller to react to different sets of shared events.
     *
     * @param  null|string $eventIdentifier
     */
    public function __construct($eventIdentifier = null)
    {
        if (null !== $eventIdentifier) {
            $this->eventIdentifier = $eventIdentifier;
        }
    }

    /**
     * Set the allowed HTTP methods for collections
     *
     * @param  array $methods
     */
    public function setCollectionHttpMethods(array $methods)
    {
        $this->collectionHttpMethods = $methods;
    }

    /**
     * Set the name to which to assign a collection in a Collection
     *
     * @param  string $name
     */
    public function setCollectionName($name)
    {
        $this->collectionName = (string) $name;
    }

    /**
     * Set the minimum page size for paginated responses
     *
     * @param  int
     */
    public function setMinPageSize($count)
    {
        $this->minPageSize = (int) $count;
    }

    /**
     * Return the minimum page size
     *
     * @return int
     */
    public function getMinPageSize()
    {
        return $this->minPageSize;
    }

    /**
     * Set the default page size for paginated responses
     *
     * @param  int
     */
    public function setPageSize($count)
    {
        $this->pageSize = (int) $count;
    }

    /**
     * Return the default page size
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Set the maximum page size for paginated responses
     *
     * @param  int
     */
    public function setMaxPageSize($count)
    {
        $this->maxPageSize = (int) $count;
    }

    /**
     * Return the maximum page size
     *
     * @return int
     */
    public function getMaxPageSize()
    {
        return $this->maxPageSize;
    }

    /**
     * Set the page size parameter for paginated responses.
     *
     * @param string
     */
    public function setPageSizeParam($param)
    {
        $this->pageSizeParam = (string) $param;
    }

    /**
     * The true description of getIdentifierName is
     * a route identifier name.  This function corrects
     * this mistake for this controller.
     *
     * @return string
     */
    public function getRouteIdentifierName()
    {
        return $this->getIdentifierName();
    }

    /**
     * Inject the resource with which this controller will communicate.
     *
     * @param  ResourceInterface $resource
     */
    public function setResource(ResourceInterface $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Returns the resource
     *
     * @throws DomainException If no resource has been set
     *
     * @return ResourceInterface
     */
    public function getResource()
    {
        if ($this->resource === null) {
            throw new DomainException('No resource has been set.');
        }

        $this->injectEventIdentityIntoResource();
        $this->injectEventInputFilterIntoResource();
        $this->injectRequestIntoResourceEventParams();
        return $this->resource;
    }

    /**
     * Set the allowed HTTP OPTIONS for a resource
     *
     * @param  array $methods
     */
    public function setEntityHttpMethods(array $methods)
    {
        $this->entityHttpMethods = $methods;
    }

    /**
     * Inject the route name for this resource.
     *
     * @param  string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * Handle the dispatch event
     *
     * Does several "pre-flight" checks:
     * - Raises an exception if no resource is composed.
     * - Raises an exception if no route is composed.
     * - Returns a 405 response if the current HTTP request method is not in
     *   $options
     *
     * When the dispatch is complete, it will check to see if an array was
     * returned; if so, it will cast it to a view model using the
     * AcceptableViewModelSelector plugin, and the $acceptCriteria property.
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        if (! $this->getResource()) {
            throw new DomainException(sprintf(
                '%s requires that a %s\ResourceInterface object is composed; none provided',
                __CLASS__,
                __NAMESPACE__
            ));
        }

        if (! $this->route) {
            throw new DomainException(sprintf(
                '%s requires that a route name for the resource is composed; none provided',
                __CLASS__
            ));
        }

        // Check for an API-Problem in the event
        $return = $e->getParam('api-problem', false);

        // If no return value dispatch the parent event
        if (! $return) {
            $return = parent::onDispatch($e);
        }

        if (! $return instanceof ApiProblem
            && ! $return instanceof HalEntity
            && ! $return instanceof HalCollection
        ) {
            return $return;
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        // Set the fallback content negotiation to use HalJson.
        $e->setParam('ZFContentNegotiationFallback', 'HalJson');

        // Use content negotiation for creating the view model
        $viewModel = new ContentNegotiationViewModel(array('payload' => $return));
        $e->setResult($viewModel);

        return $viewModel;
    }

    /**
     * Create a new entity
     *
     * @todo   Remove 'resource' from the create.post event parameters for 1.0.0
     * @param  array $data
     * @return Response|ApiProblem|HalEntity
     */
    public function create($data)
    {
        $events = $this->getEventManager();
        $events->trigger('create.pre', $this, array('data' => $data));

        try {
            $entity = $this->getResource()->create($data);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        if ($entity instanceof ApiProblem
            || $entity instanceof ApiProblemResponse
            || $entity instanceof Response
        ) {
            return $entity;
        }

        $plugin   = $this->plugin('Hal');
        $entity   = $plugin->createEntity($entity, $this->route, $this->getRouteIdentifierName());

        if ($entity->id) {
            $self = $entity->getLinks()->get('self');
            $self = $plugin->fromLink($self);

            $response = $this->getResponse();
            $response->setStatusCode(201);
            $response->getHeaders()->addHeaderLine('Location', $self);
        }

        $events->trigger('create.post', $this, array(
            'data'     => $data,
            'entity'   => $entity,
            'resource' => $entity,
        ));

        return $entity;
    }

    /**
     * Delete an existing entity
     *
     * @param  int|string $id
     * @return Response|ApiProblem
     */
    public function delete($id)
    {
        $events = $this->getEventManager();
        $events->trigger('delete.pre', $this, array('id' => $id));

        try {
            $result = $this->getResource()->delete($id);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        $result = $result ?: new ApiProblem(422, 'Unable to delete entity.');

        if ($result instanceof ApiProblem
            || $result instanceof ApiProblemResponse
            || $result instanceof Response
        ) {
            return $result;
        }

        $response = $this->getResponse();
        $response->setStatusCode(204);

        $events->trigger('delete.post', $this, array('id' => $id));

        return $response;
    }

    /**
     * Delete a collection of entities as specified.
     *
     * @param mixed $data Typically an array
     * @return Response|ApiProblem
     */
    public function deleteList($data)
    {
        $events = $this->getEventManager();
        $events->trigger('deleteList.pre', $this, array());

        try {
            $result = $this->getResource()->deleteList($data);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        $result = $result ?: new ApiProblem(422, 'Unable to delete collection.');

        if ($result instanceof ApiProblem
            || $result instanceof ApiProblemResponse
            || $result instanceof Response
        ) {
            return $result;
        }

        $response = $this->getResponse();
        $response->setStatusCode(204);

        $events->trigger('deleteList.post', $this, array());

        return $response;
    }

    /**
     * Return single entity
     *
     * @todo   Remove 'resource' from get.post event for 1.0.0
     * @param  int|string $id
     * @return Response|ApiProblem|HalEntity
     */
    public function get($id)
    {
        $events = $this->getEventManager();
        $events->trigger('get.pre', $this, array('id' => $id));

        try {
            $entity = $this->getResource()->fetch($id);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        $entity = $entity ?: new ApiProblem(404, 'Entity not found.');

        if ($entity instanceof ApiProblem
            || $entity instanceof ApiProblemResponse
            || $entity instanceof Response
        ) {
            return $entity;
        }

        $plugin   = $this->plugin('Hal');
        $entity   = $plugin->createEntity($entity, $this->route, $this->getRouteIdentifierName());

        if ($entity instanceof ApiProblem) {
            return $entity;
        }

        $events->trigger('get.post', $this, array(
            'id'       => $id,
            'entity'   => $entity,
            'resource' => $entity,
        ));
        return $entity;
    }

    /**
     * Return collection of entities
     *
     * @return Response|HalCollection
     */
    public function getList()
    {
        $events = $this->getEventManager();
        $events->trigger('getList.pre', $this, array());

        try {
            $collection = $this->getResource()->fetchAll();
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        if ($collection instanceof ApiProblem
            || $collection instanceof ApiProblemResponse
            || $collection instanceof Response
        ) {
            return $collection;
        }

        $plugin = $this->plugin('Hal');

        if (! is_array($collection)
            && ! $collection instanceof Traversable
            && ! $collection instanceof HalCollection
            && is_object($collection)
        ) {
            $collection = $plugin->createEntity($collection, $this->route, $this->getRouteIdentifierName());
            $events->trigger('getList.post', $this, array('collection' => $collection));
            return $collection;
        }

        $pageSize = $this->pageSizeParam
            ? $this->getRequest()->getQuery($this->pageSizeParam, $this->pageSize)
            : $this->pageSize;

        if (isset($this->minPageSize) && $pageSize < $this->minPageSize) {
            return new ApiProblem(
                500,
                sprintf("Page size is out of range, minimum page size is %s", $this->minPageSize)
            );
        }

        if (isset($this->maxPageSize) && $pageSize > $this->maxPageSize) {
            return new ApiProblem(
                500,
                sprintf("Page size is out of range, maximum page size is %s", $this->maxPageSize)
            );
        }

        $this->setPageSize($pageSize);

        $collection = $plugin->createCollection($collection, $this->route);
        $collection->setCollectionRoute($this->route);
        $collection->setRouteIdentifierName($this->getRouteIdentifierName());
        $collection->setEntityRoute($this->route);
        $collection->setPage($this->getRequest()->getQuery('page', 1));
        $collection->setCollectionName($this->collectionName);
        $collection->setPageSize($this->getPageSize());

        $events->trigger('getList.post', $this, array('collection' => $collection));
        return $collection;
    }

    /**
     * Retrieve HEAD metadata for the entity and/or collection
     *
     * @param  null|mixed $id
     * @return Response|ApiProblem|HalEntity|HalCollection
     */
    public function head($id = null)
    {
        if ($id) {
            return $this->get($id);
        }
        return $this->getList();
    }

    /**
     * Respond to OPTIONS request
     *
     * Uses $options to set the Allow header line and return an empty response.
     *
     * @return Response
     */
    public function options()
    {
        $e  = $this->getEvent();
        $id = $this->getIdentifier($e->getRouteMatch(), $e->getRequest());

        if ($id) {
            $options = $this->entityHttpMethods;
        } else {
            $options = $this->collectionHttpMethods;
        }

        $events = $this->getEventManager();
        $events->trigger('options.pre', $this, array('options' => $options));

        $response = $this->getResponse();
        $response->setStatusCode(204);
        $headers  = $response->getHeaders();
        $headers->addHeader($this->createAllowHeaderWithAllowedMethods($options));

        $events->trigger('options.post', $this, array('options' => $options));

        return $response;
    }

    /**
     * Respond to the PATCH method (partial update of existing entity)
     *
     * @todo   Remove 'resource' from patch.post event for 1.0.0
     * @param  int|string $id
     * @param  array $data
     * @return Response|ApiProblem|HalEntity
     */
    public function patch($id, $data)
    {
        $events = $this->getEventManager();
        $events->trigger('patch.pre', $this, array('id' => $id, 'data' => $data));

        try {
            $entity = $this->getResource()->patch($id, $data);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        if ($entity instanceof ApiProblem
            || $entity instanceof ApiProblemResponse
            || $entity instanceof Response
        ) {
            return $entity;
        }

        $plugin   = $this->plugin('Hal');
        $entity   = $plugin->createEntity($entity, $this->route, $this->getRouteIdentifierName());

        if ($entity instanceof ApiProblem) {
            return $entity;
        }

        $events->trigger('patch.post', $this, array(
            'id'       => $id,
            'data'     => $data,
            'entity'   => $entity,
            'resource' => $entity,
        ));
        return $entity;
    }

    /**
     * Update an existing entity
     *
     * @todo   Remove 'resource' from update.post event for 1.0.0
     * @param  int|string $id
     * @param  array $data
     * @return Response|ApiProblem|HalEntity
     */
    public function update($id, $data)
    {
        $events = $this->getEventManager();
        $events->trigger('update.pre', $this, array('id' => $id, 'data' => $data));

        try {
            $entity = $this->getResource()->update($id, $data);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        if ($entity instanceof ApiProblem
            || $entity instanceof ApiProblemResponse
            || $entity instanceof Response
        ) {
            return $entity;
        }

        $plugin   = $this->plugin('Hal');
        $entity   = $plugin->createEntity($entity, $this->route, $this->getRouteIdentifierName());

        $events->trigger('update.post', $this, array(
            'id'       => $id,
            'data'     => $data,
            'entity'   => $entity,
            'resource' => $entity,
        ));
        return $entity;
    }

    /**
     * Respond to the PATCH method (partial update of existing entity) on
     * a collection, i.e. create and/or update multiple entities in a collection.
     *
     * @param array $data
     * @return array
     */
    public function patchList($data)
    {
        $events = $this->getEventManager();
        $events->trigger('patchList.pre', $this, array('data' => $data));

        try {
            $collection = $this->getResource()->patchList($data);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        if ($collection instanceof ApiProblem
            || $collection instanceof ApiProblemResponse
            || $collection instanceof Response
        ) {
            return $collection;
        }

        $plugin = $this->plugin('Hal');
        $collection = $plugin->createCollection($collection, $this->route);
        $collection->setCollectionRoute($this->route);
        $collection->setRouteIdentifierName($this->getRouteIdentifierName());
        $collection->setEntityRoute($this->route);
        $collection->setPage($this->getRequest()->getQuery('page', 1));
        $collection->setPageSize($this->getPageSize());
        $collection->setCollectionName($this->collectionName);

        $events->trigger('patchList.post', $this, array('data' => $data, 'collection' => $collection));
        return $collection;
    }

    /**
     * Update an existing collection of entities
     *
     * @param array $data
     * @return array
     */
    public function replaceList($data)
    {
        $events = $this->getEventManager();
        $events->trigger('replaceList.pre', $this, array('data' => $data));

        try {
            $collection = $this->getResource()->replaceList($data);
        } catch (\Exception $e) {
            return new ApiProblem($this->getHttpStatusCodeFromException($e), $e);
        }

        if ($collection instanceof ApiProblem
            || $collection instanceof ApiProblemResponse
            || $collection instanceof Response
        ) {
            return $collection;
        }

        $plugin = $this->plugin('Hal');
        $collection = $plugin->createCollection($collection, $this->route);
        $collection->setCollectionRoute($this->route);
        $collection->setRouteIdentifierName($this->getRouteIdentifierName());
        $collection->setEntityRoute($this->route);
        $collection->setPage($this->getRequest()->getQuery('page', 1));
        $collection->setPageSize($this->getPageSize());
        $collection->setCollectionName($this->collectionName);

        $events->trigger('replaceList.post', $this, array('data' => $data, 'collection' => $collection));
        return $collection;
    }

    /**
     * Retrieve the identifier, if any
     *
     * Attempts to see if an identifier was passed in the URI,
     * returning it if found. Otherwise, returns a boolean false.
     *
     * @param  \Zend\Mvc\Router\RouteMatch $routeMatch
     * @param  \Zend\Http\Request $request
     * @return false|mixed
     */
    protected function getIdentifier($routeMatch, $request)
    {
        $identifier = $this->getIdentifierName();
        $id = $routeMatch->getParam($identifier, false);
        if ($id !== null) {
            return $id;
        }

        return false;
    }

    /**
     * Creates an ALLOW header with the provided HTTP methods
     *
     * @param  array $methods
     * @return Allow
     */
    protected function createAllowHeaderWithAllowedMethods(array $methods)
    {
        // Need to create an Allow header. It has several defaults, and the only
        // way to start with a clean slate is to retrieve all methods, disallow
        // them all, and then set the ones we want to allow.
        $allow      = new Allow();
        $allMethods = $allow->getAllMethods();
        $allow->disallowMethods(array_keys($allMethods));
        $allow->allowMethods($methods);
        return $allow;
    }

    /**
     * Ensure we have a valid HTTP status code for an ApiProblem
     *
     * @param \Exception $e
     * @return int
     */
    protected function getHttpStatusCodeFromException(\Exception $e)
    {
        $code = $e->getCode();
        if (!is_int($code)
            || $code < 100
            || $code >= 600
        ) {
            return 500;
        }
        return $code;
    }

    /**
     * Injects the resource with the identity composed in the event, if present
     */
    protected function injectEventIdentityIntoResource()
    {
        if ($this->resource->getIdentity()) {
            return;
        }

        $event = $this->getEvent();
        if (! $event) {
            return;
        }

        $identity = $event->getParam('ZF\MvcAuth\Identity');
        if (! $identity) {
            return;
        }

        $this->resource->setIdentity($identity);
    }

    /**
     * Injects the resource with the input filter composed in the event, if present
     */
    protected function injectEventInputFilterIntoResource()
    {
        if ($this->resource->getInputFilter()) {
            return;
        }

        $event = $this->getEvent();
        if (! $event) {
            return;
        }

        $inputFilter = $event->getParam('ZF\ContentValidation\InputFilter');
        if (! $inputFilter) {
            return;
        }

        $this->resource->setInputFilter($inputFilter);
    }

    protected function injectRequestIntoResourceEventParams()
    {
        $request = $this->getRequest();
        if (! $request) {
            return;
        }

        $params = $this->resource->getEventParams();

        if (! is_array($params) && ! $params instanceof ArrayAccess) {
            // If not array-like, no clear path for setting event parameters
            return;
        }

        $params['request'] = $request;
        $this->resource->setEventParams($params);
    }

    /**
     * Override parent - pull from content negotiation helpers
     *
     * @param RequestInterface $request
     * @return null|array|\Traversable
     */
    public function processPostData(RequestInterface $request)
    {
        return $this->create($this->bodyParams());
    }

    /**
     * Override parent - pull from content negotiation helpers
     *
     * @param Request $request
     * @return null|array|\Traversable
     */
    protected function processBodyContent($request)
    {
        return $this->bodyParams();
    }
}
