<?php namespace GM\ATP;

class CacheProvider {

    private $drivers;
    private $driver;
    private $pool;
    private $filesystem;
    private $key;

    public function __construct( FileSystem $filesystem, Array $drivers ) {
        $this->filesystem = $filesystem;
        $this->drivers = $drivers;
    }

    /**
     * @return bool
     */
    public function shouldCache() {
        $should = apply_filters( 'ajax_template_cache', ( defined( 'WP_DEBUG' ) && ! WP_DEBUG ) );
        return ! empty( $should ) && ! is_null( $this->getDriverClass() );
    }

    /**
     * @return string
     */
    public function getDriverClass() {
        if ( ! is_null( $this->driver ) ) {
            return $this->driver;
        }
        $driver = apply_filters( 'ajax_template_cache_driver', '\Stash\Driver\FileSystem' );
        if ( in_array( $driver, $this->drivers, TRUE ) ) {
            $this->driver = $driver;
        }
        return $this->driver;
    }

    /**
     * @param string $driver
     * @return mixed
     */
    public function getDriverOptions( $driver ) {
        if ( $driver === '\Stash\Driver\FileSystem' ) {
            $path = $this->filesystem->getFolder();
            return $path ? [ 'path' => $path ] : FALSE;
        }
        $name = array_search( $driver, $this->drivers );
        if ( $name !== FALSE ) {
            $name = strtolower( $name );
            return apply_filters( "ajax_template_cache_{$name}_options", FALSE );
        }
    }

    /**
     * @param \Stash\PoolInterface $pool
     */
    public function setPool( \Stash\PoolInterface $pool ) {
        $this->pool = $pool;
        $this->pool->setNamespace( __NAMESPACE__ );
    }

    /**
     * @return Stash\PoolInterface
     */
    public function getPool() {
        return $this->pool;
    }

    /**
     * @return int
     */
    public function getTTL() {
        $duration = apply_filters( 'ajax_template_cache_duration', HOUR_IN_SECONDS );
        return is_scalar( $duration ) && (int) $duration > 30 ? $duration : HOUR_IN_SECONDS;
    }

    /**
     * @param array $data_a
     * @param array $data_b
     * @return string
     */
    public function getKey( $data_a, $data_b ) {
        if ( ! is_null( $this->key ) ) {
            return $this->key;
        }
        $a = array_filter( (array) $data_a );
        $b = array_filter( (array) $data_b );
        ksort( $a );
        ksort( $b );
        $this->key = md5( serialize( $a ) . serialize( $b ) );
        return $this->key;
    }

}