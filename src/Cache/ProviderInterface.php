<?php namespace GM\ATP\Cache;

interface ProviderInterface {

    public function get( Array $id1, Array $id2 );

    public function set( Array $value, Array $id1, Array $id2 );

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @return bool
     */
    public function shouldCache();

    /**
     * @return HandlerInterface
     */
    public function getHandler();

    /**
     * @param \GM\ATP\Cache\HandlerInterface $handler
     */
    public function setHandler( HandlerInterface $handler );
}