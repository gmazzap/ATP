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

use Stash\DriverList;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

/**
 * Plugin activation callback.
 */
function activate()
{
    wp_schedule_event(time(), 'daily', 'ajaxtemplatepart_cache_purge');
}

/**
 * Plugin deactivation callback.
 */
function deactivate()
{
    $stash = get_stash();
    $stash instanceof Pool and $stash->flush();
    $timestamp = wp_next_scheduled('ajaxtemplatepart_cache_purge');
    $timestamp and wp_unschedule_event($timestamp, 'ajaxtemplatepart_cache_purge');
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

    $path = dirname(__DIR__).'/ajax-template-part.php';
    $templater = new Templater($GLOBALS['wp'], $GLOBALS['wp_query'], $path);
    $templater->addJs()->addHtml($name, $slug, $content);
}

/**
 * AJAX callback.
 */
function ajax_callback()
{
    if (! defined('DOING_AJAX') || ! DOING_AJAX) {
        return;
    }

    $loader = new Loader();

    /**
     * No caching when WP_DEBUG is true, but can be filtered via "ajax_template_cache" hook.
     * If external object cache is active use that (via Transients API) otherwise
     * use Stash, by default with FileSystem driver, but driver and its options can be customized
     * via "ajax_template_cache_driver" and "ajax_template_{$driver}_driver_conf" filter hooks.
     */
    $provider = new Cache\Provider();
    $handler = $provider->isEnabled() ? get_cache_handler() : null;

    if ($handler) {
        $provider->setHandler($handler);
        $loader->setCacheProvider($provider);
    }

    $loader->getData();

    exit();
}

/**
 * @return \Stash\Pool|void
 */
function get_stash()
{
    $drivers = DriverList::getAvailableDrivers();
    $provider = new Cache\StashDriverProvider(new FileSystem(), $drivers);
    $class = $provider->getDriverClass();
    if (! class_exists($class)) {
        return;
    }

    $driver = new $class();
    if ($driver instanceof DriverInterface) {
        $options = $provider->getDriverOptions($class);
        $driver->setOptions($options);

        return new Pool($driver);
    }
}

/**
 * @return \GM\ATP\Cache\HandlerInterface
 */
function get_cache_handler()
{
    static $handler;
    if (! is_null($handler)) {
        return $handler;
    }

    $transient = new Cache\TransientHandler();
    if ($transient->isAvailable()) {
        $handler = $transient;

        return $transient;
    }

    $handler = new Cache\StashHandler(get_stash());

    return $handler;
}

/**
 * Purge cache.
 */
function cache_purge()
{
    $provider = new Cache\Provider();
    $handler = $provider->isEnabled() ? get_cache_handler() : null;
    $handler instanceof Cache\StashHandler and $handler->getStash()->purge();
}
