<?php namespace GM\ATP;

use Pimple\Container as Pimple;
use Stash\Interfaces\DriverInterface;
use Stash\Pool as Stash;
use Stash\DriverList;

class Container extends Pimple {

    function __construct( Array $values = [ ] ) {

        $defaults = [
            'stash.drivers' => DriverList::getAvailableDrivers(),
            'loader'        => function() {
                return new Loader;
            },
            'templater' => function() {
                $path = dirname( dirname( __FILE__ ) ) . '/ajax-template-part.php';
                return new Templater( $GLOBALS[ 'wp' ], $GLOBALS[ 'wp_query' ], $path );
            },
            'filesystem' => function() {
                return new FileSystem;
            },
            'cache.provider' => function() {
                return new Cache\Provider;
            },
            'cache.driverpicker' => function($c) {
                return new Cache\StashDriverPicker( $c[ 'filesystem' ], $c[ 'stash.drivers' ] );
            },
            'cache.stash' => function($c) {
                $class = '\\' . ltrim( $c[ 'cache.driverpicker' ]->getDriverClass(), '\\' );
                if ( ! class_exists( $class ) ) {
                    return;
                }
                $driver = new $class;
                if ( $driver instanceof DriverInterface ) {
                    $options = $c[ 'cache.driverpicker' ]->getDriverOptions( $class );
                    $driver->setOptions( $options );
                    return new Stash( $driver );
                }
            },
            'cache.handlers.transient' => function() {
                return new Cache\TransientHandler;
            },
            'cache.handlers.stash' => function($c) {
                if ( ! empty( $c[ 'cache.stash' ] ) ) {
                    return new Cache\StashHandler( $c[ 'cache.stash' ] );
                }
            },
            'cache.handler' => function($c) {
                return $c[ 'cache.handlers.transient' ]->isAvailable() ?
                    $c[ 'cache.handlers.transient' ] :
                    $c[ 'cache.handlers.stash' ];
            }
        ];

        parent::__construct( array_merge( $defaults, $values ) );
    }

}