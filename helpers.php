<?php
/*
 * This file is part of the ATP package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GM\ATP;

use Pimple\Container;

/**
 * Instantiate a container instance and add the service provider.
 *
 * @return \GM\ATP\Container
 */
function get_container()
{
    static $container = null;

    if (is_null($container)) {
        $container = new Container();
        $container->register(new ServiceProvider());
    }

    return $container;
}

/**
 * Plugin activation callback.
 */
function activate()
{
    $container = get_container();
    $container['filesystem']->getFolder();
    wp_schedule_event(time(), 'daily', 'ajaxtemplatepart_cache_purge');
}

/**
 * Plugin deactivation callback.
 */
function deactivate()
{
    $container = get_container();
    $stash = $container['cache.stash'];
    if ($stash instanceof \Stash\Pool) {
        $stash->flush();
    }
}

/**
 * @param string $name
 * @param string $slug
 * @param string $content
 */
function template_part($name, $slug = '', $content = '')
{
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return \get_template_part($name, $slug);
    }
    $container = get_container();
    /** @var Templater $templater */
    $templater = $container['templater'];
    $templater->addJs()->addHtml($name, $slug, $content);
}

/**
 * AJAX callback.
 */
function ajax_callback()
{
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX) {
        return;
    }

    $cont = get_container();
    $loader = $cont['loader'];
    $provider = $cont['cache.provider'];

    /**
     * No caching when WP_DEBUG is true, but can be filtered via "ajax_template_cache" hook.
     * If external object cache is active use that (via Transients API) otherwise
     * use Stash, by default with FileSystem driver, but driver and its options can be customized
     * via "ajax_template_cache_driver" and "ajax_template_{$driver}_driver_conf" filter hooks.
     */
    if ($provider->isEnabled() && $cont['cache.handler'] instanceof Cache\HandlerInterface) {
        $provider->setHandler($cont['cache.handler']);
        $loader->setCacheProvider($provider);
    }

    $loader->getData();

    exit();
}

/**
 * Purge cache.
 */
function cache_purge()
{
    $container = get_container();
    $handler = $container['cache.handler'];
    if ($handler instanceof Cache\StashHandler) {
        $handler->getStash()->purge();
    }
}
