<?php namespace GM\ATP\Cache;

interface HandlerInterface
{

    public function get($key);

    public function set($key, $value, $expiration);

    public function clear($key);

    public function isAvailable();
}