<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZFTest\Rest\Plugin\TestAsset;

class EmbeddedResourceWithCustomIdentifier
{
    public $custom_id;
    public $name;

    public function __construct($id, $name)
    {
        $this->custom_id = $id;
        $this->name      = $name;
    }
}
