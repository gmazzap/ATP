<?php
/*
 * This file is part of the ATP package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GM\ATP\Cache;

use GM\ATP\FileSystem;
use Stash\Interfaces\DriverInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package ATP
 */
class StashDriverProvider
{
    /**
     * @var \GM\ATP\FileSystem
     */
    private $fs;

    /**
     * @var array
     */
    private $drivers;

    /**
     * @param \GM\ATP\FileSystem $fs
     * @param array              $drivers
     */
    public function __construct(FileSystem $fs, array $drivers = [])
    {
        $this->fs = $fs;
        $this->drivers = $drivers;
    }

    /**
     * @return string
     */
    public function getDriverClass()
    {
        $driver = apply_filters('ajax_template_cache_driver', '\Stash\Driver\FileSystem');

        if (
            is_string($driver)
            && method_exists($driver, 'isAvailable')
            && call_user_func([$driver, 'isAvailable'])
        ) {
            return $driver;
        }

        return '\Stash\Driver\FileSystem';
    }

    /**
     * @param $driver_class
     * @return string
     */
    public function getDriverName($driver_class)
    {
        return array_search($driver_class, $this->drivers, true);
    }

    /**
     * @param $driver_class
     * @return array
     */
    public function getDriverOptions($driver_class)
    {
        $driver_name = $this->getDriverName($driver_class);
        if (empty($driver_name) || $driver_name === 'FileSystem') {
            $path = $this->fs->getFolder();

            return $path ? ['path' => $path] : [];
        }

        $name = strtolower($driver_name);

        return apply_filters("ajax_template_{$name}_driver_conf", []);
    }

    /**
     * @param  null $driver
     * @param  null $options
     * @return bool
     */
    public function checkDriver($driver = null, $options = null)
    {
        if (! is_array($options) || ! $driver instanceof DriverInterface) {
            return false;
        }

        if (! call_user_func([get_class($driver), 'isAvailable'])) {
            return false;
        }

        try {
            $driver->setOptions($options);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
