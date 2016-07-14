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
use Stash\Driver\FileSystem as FileSystemDriver;
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
        $driver = apply_filters('ajax_template_cache_driver', FileSystemDriver::class);

        if (
            ! is_subclass_of($driver, DriverInterface::class, true)
            || $driver === FileSystemDriver::class
        ) {
            return FileSystemDriver::class;
        }

        /** @var callable $cb */
        $cb = [$driver, 'isAvailable'];

        return $cb() ? $driver : FileSystemDriver::class;
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
}
