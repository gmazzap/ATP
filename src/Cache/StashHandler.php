<?php namespace GM\ATP\Cache;

use Stash\Interfaces\PoolInterface;

class StashHandler implements HandlerInterface {

    private $stash;

    function __construct( PoolInterface $pool ) {
        return $this->stash = $pool;
    }

    public function clear( $key ) {
        try {
            $item = $this->getItem( $key );
            if ( $item instanceof \Exception ) {
                return FALSE;
            }
            $item->clear();
        } catch ( \Exception $e ) {
            return $e;
        }
    }

    public function get( $key ) {
        try {
            $item = $this->getItem( $key );
            if ( $item instanceof \Exception ) {
                return FALSE;
            }
            $cached = $item->get();
            return $item->isMiss() ? FALSE : $cached;
        } catch ( \Exception $e ) {
            return $e;
        }
    }

    public function set( $key, $value, $expiration ) {
        try {
            $item = $this->getItem( $key );
            if ( $item instanceof \Exception ) {
                return FALSE;
            }
            $item->clear();
            $item->lock();
            return $item->set( $value, $expiration );
        } catch ( \Exception $e ) {
            return $e;
        }
    }

    public function isAvailable() {
        return call_user_func( [ get_class( $this->getStash()->getDriver() ), 'isAvailable' ] );
    }

    private function getStash() {
        return $this->stash;
    }

    private function getItem( $key ) {
        try {
            return $this->getStash()->getItem( $key );
        } catch ( \Exception $e ) {
            return $e;
        }
    }

}