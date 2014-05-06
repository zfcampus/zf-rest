<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Rest\Listener\OptionsListener;

class OptionsListenerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $services
     * @return OptionsListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = array();
        if ($services->has('Config')) {
            $allConfig = $services->get('Config');
            if (array_key_exists('zf-rest', $allConfig)
                && is_array($allConfig['zf-rest'])
            ) {
                $config = $allConfig['zf-rest'];
            }
        }
        return new OptionsListener($config);
    }
}
