<?php
/*
  Plugin Name: Ajax Template Part
  Plugin URI: https://github.com/Giuseppe-Mazzapica/ajax-template-part
  Description: Like get_template_part, but via AJAX
  Author: Giuseppe Mazzapica
  Author URI: http://gm.zoomlab.it/
  License: GPLv2+
 */

/*
  Copyright (C) 2014 Giuseppe

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

register_activation_hook( __FILE__, function() {
    if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
    }
    $filestystem = new GM\ATP\FileSystem;
    $filestystem->getFolder();
} );


if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
    return;
}


if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}


if ( ! defined( 'DOING_AJAX' ) ) {

    if ( ! function_exists( 'ajax_template_part' ) ) {

        function ajax_template_part( $name, $slug = '' ) {
            GM\ATP\Templater::tag( $name, $slug );
        }

    }

    return;
}


add_action( 'wp_ajax_' . ( is_user_logged_in() ? '' : 'nopriv_' ) . 'ajaxtemplatepart', function() {

    static $loader = NULL;

    if ( ! is_null( $loader ) ) {
        exit();
    }

    $loader = new GM\ATP\Loader();

    $drivers = Stash\DriverList::getAvailableDrivers();
    $cache = new GM\ATP\CacheProvider( new GM\ATP\FileSystem, $drivers );

    if ( ! $cache->shouldCache() ) {
        $loader->getData();
        exit();
    }

    try {
        $driver_class = $cache->getDriverClass();
        $options = $cache->getDriverOptions( $driver_class );
        $cache_driver = FALSE;
        if ( is_array( $options ) && class_exists( $driver_class ) ) {
            $cache_driver = new $driver_class;
        }
        if ( $cache_driver instanceof Stash\Interfaces\DriverInterface ) {
            $cache_driver->setOptions( $options );
            $cache->setPool( new Stash\Pool( $cache_driver ) );
            $loader->setCacheProvider( $cache );
        }
    } catch ( Exception $e ) {
        //
    }

    $loader->getData();

    exit();
} );



