<?php namespace GM\ATP\Cache;

class TransientHandler implements HandlerInterface {

    public function clear( $key ) {
        return delete_transient( $key );
    }

    public function get( $key ) {
        return get_transient( $key ) ? : FALSE;
    }

    public function set( $key, $value, $expiration ) {
        return set_transient( $key, $value, $expiration );
    }

    public function isAvailable() {
        return wp_using_ext_object_cache();
    }

}