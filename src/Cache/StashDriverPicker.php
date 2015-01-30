<?php namespace GM\ATP\Cache;

use GM\ATP\FileSystem;
use Stash\Interfaces\DriverInterface;

class StashDriverPicker
{

    private $fs;
    private $drivers;

    function __construct(FileSystem $fs, Array $drivers = [])
    {
        $this->fs = $fs;
        $this->drivers = $drivers;
    }

    public function getDriverClass()
    {
        $driver = apply_filters('ajax_template_cache_driver', '\Stash\Driver\FileSystem');
        if (
            is_string($driver)
            && method_exists($driver, 'isAvailable')
            && call_user_func([ $driver, 'isAvailable'])
        ) {
            return $driver;
        }
        return '\Stash\Driver\FileSystem';
    }

    public function getDriverName($driver_class)
    {
        return array_search($driver_class, $this->drivers, TRUE);
    }

    public function getDriverOptions($driver_class)
    {
        $driver_name = $this->getDriverName($driver_class);
        if (empty($driver_name) || $driver_name === 'FileSystem') {
            $path = $this->fs->getFolder();
            return $path ? [ 'path' => $path] : [];
        }
        $name = strtolower($driver_name);
        return apply_filters("ajax_template_{$name}_driver_conf", FALSE);
    }

    public function checkDriver($driver = NULL, $options = NULL)
    {
        if ( ! is_array($options) || ! $driver instanceof DriverInterface) {
            return FALSE;
        }
        if ( ! call_user_func([ get_class($driver), 'isAvailable'])) {
            return FALSE;
        }
        try {
            $driver->setOptions($options);
            return TRUE;
        } catch (\Exception $e) {
            return $e;
        }
    }

}