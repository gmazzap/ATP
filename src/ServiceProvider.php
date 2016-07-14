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
use Pimple\ServiceProviderInterface;
use Stash\Interfaces\DriverInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package ATP
 */
final class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Container $container)
    {
        $container['loader'] = function () {
            return new Loader();
        };

        $container['templater'] = function () {
            $path = dirname(__DIR__).'/ajax-template-part.php';

            return new Templater($GLOBALS['wp'], $GLOBALS['wp_query'], $path);
        };

        $container['filesystem'] = function () {
            return new FileSystem();
        };

        $container['cache.provider'] = function () {
            return new Cache\Provider();
        };

        $container['cache.driver-provider'] = function (Container $pimple) {
            $drivers = DriverList::getAvailableDrivers();

            return new Cache\StashDriverProvider($pimple['filesystem'], $drivers);
        };

        $container['cache.stash'] = function (Container $pimple) {
            $class = '\\'.ltrim($pimple['driver-provider']->getDriverClass(), '\\');
            if (! class_exists($class)) {
                return;
            }

            $driver = new $class();
            if ($driver instanceof DriverInterface) {
                $options = $pimple['driver-provider']->getDriverOptions($class);
                $driver->setOptions($options);

                return new Stash($driver);
            }
        };

        $container['cache.handlers.transient'] = function () {
            return new Cache\TransientHandler();
        };

        $container['cache.handlers.stash'] = function (Container $pimple) {
            if (! empty($pimple['cache.stash'])) {
                return new Cache\StashHandler($pimple['cache.stash']);
            }
        };

        $container['cache.handler'] = function (Container $pimple) {
            return $pimple['cache.handlers.transient']->isAvailable()
                ? $pimple['cache.handlers.transient']
                : $pimple['cache.handlers.stash'];
        };
    }
}
