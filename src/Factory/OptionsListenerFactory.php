<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Rest\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Rest\Listener\OptionsListener;

class OptionsListenerFactory implements FactoryInterface
{
    /**
     * Create and return an OptionsListener instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return OptionsListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new OptionsListener($this->getConfig($container));
    }

    /**
     * Create and return an OptionsListener instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return OptionsListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, OptionsListener::class);
    }

    /**
     * Retrieve zf-rest config from the container, if available.
     *
     * @param ContainerInterface $container
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');

        if (! array_key_exists('zf-rest', $config)
            || ! is_array($config['zf-rest'])
        ) {
            return [];
        }

        return $config['zf-rest'];
    }
}
