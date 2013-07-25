<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZF\Rest\View;

use ZF\Rest\ApiProblem;
use ZF\Rest\HalCollection;
use ZF\Rest\HalResource;
use Zend\View\Model\JsonModel;

/**
 * Simple extension to facilitate the specialized JsonStrategy and JsonRenderer
 * in this Module.
 */
class RestfulJsonModel extends JsonModel
{
    /**
     * Does the payload represent an API-Problem?
     *
     * @return bool
     */
    public function isApiProblem()
    {
        $payload = $this->getPayload();
        return ($payload instanceof ApiProblem);
    }

    /**
     * Does the payload represent a HAL collection?
     *
     * @return bool
     */
    public function isHalCollection()
    {
        $payload = $this->getPayload();
        return ($payload instanceof HalCollection);
    }

    /**
     * Does the payload represent a HAL item?
     *
     * @return bool
     */
    public function isHalResource()
    {
        $payload = $this->getPayload();
        return ($payload instanceof HalResource);
    }

    /**
     * Set the payload for the response
     *
     * This is the value to represent in the response.
     *
     * @param  mixed $payload
     * @return RestfulJsonModel
     */
    public function setPayload($payload)
    {
        $this->setVariable('payload', $payload);
        return $this;
    }

    /**
     * Retrieve the payload for the response
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->getVariable('payload');
    }
}
