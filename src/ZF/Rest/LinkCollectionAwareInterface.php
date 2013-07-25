<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace ZF\Rest;

interface LinkCollectionAwareInterface
{
    public function setLinks(LinkCollection $links);
    public function getLinks();
}
