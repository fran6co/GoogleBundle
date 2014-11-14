<?php

namespace AntiMattr\GoogleBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GoogleExtension extends Extension
{
    /**
     * @see Symfony\Component\DependencyInjection\Extension.ExtensionInterface::load()
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['adwords'])) {
            $this->registerAdwordsConfiguration($config['adwords'], $container, $loader);
        }

        if (isset($config['analytics'])) {
            $this->registerAnalyticsConfiguration($config['analytics'], $container, $loader);
        }

        if (isset($config['maps'])) {
            $this->registerMapsConfiguration($config['maps'], $container, $loader);
        }
    }

    /**
     * Loads the Adwords configuration.
     *
     * @param array            $config    An adwords configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    An XmlFileLoader instance
     */
    private function registerAdwordsConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('adwords.xml');

        $container->setParameter('google.adwords.conversions', $config['conversions']);
    }

    /**
     * Loads the Analytics configuration.
     *
     * @param array            $config    An analytics configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    An XmlFileLoader instance
     */
    private function registerAnalyticsConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('analytics.xml');

        if (isset($config['enhanced_ecommerce'])) {
            $container->setParameter('google.analytics.enhanced_ecommerce', $config['enhanced_ecommerce']);
        }
        if (isset($config['session_auto_started'])) {
            $container->setParameter('google.analytics.session_auto_started', $config['session_auto_started']);
        }
        if (isset($config['trackers'])) {
            $container->setParameter('google.analytics.trackers', $config['trackers']);
        }
        if (isset($config['dashboard'])) {
            $container->setParameter('google.analytics.dashboard', $config['dashboard']);
        }
        if (isset($config['whitelist'])) {
            $container->setParameter('google.analytics.whitelist', $config['whitelist']);
        }
        if (isset($config['js_source_https'])) {
            $container->setParameter('google.analytics.js_source_https', $config['js_source_https']);
        }
        if (isset($config['js_source_http'])) {
            $container->setParameter('google.analytics.js_source_http', $config['js_source_http']);
        }
        if (isset($config['js_source_endpoint'])) {
            $container->setParameter('google.analytics.js_source_endpoint', $config['js_source_endpoint']);
        }
    }

    /**
     * Loads the Maps configuration.
     *
     * @param array            $config    A maps configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    An XmlFileLoader instance
     */
    private function registerMapsConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('maps.xml');

        $container->setParameter('google.maps.config', $config['config']);
    }

    /**
     * @see Symfony\Component\DependencyInjection\Extension.ExtensionInterface::getAlias()
     * @codeCoverageIgnore
     */
    public function getAlias()
    {
        return 'google';
    }
}
