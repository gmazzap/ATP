<?php
/*
  Plugin Name: Ajax Template Part
  Plugin URI: https://github.com/Giuseppe-Mazzapica/ajax-template-part
  Description: Introduce ajax_template_part() function: like get_template_part(), but AJAX powered.
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


if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
    return;
}


if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}


if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

    if ( ! function_exists( 'ajax_template_part' ) ) {

        function ajax_template_part( $name, $slug = '' ) {
            GM\ATP\Templater::tag( $name, $slug );
        }

    }

    return;
}

function atp_ajax_callback() {
    $loader = new GM\ATP\Loader();
    $cache_provider = new GM\ATP\Cache\Provider();

    // no caching
    if ( ! $cache_provider->isEnabled() ) {
        $loader->getData();
        exit();
    }

    // custom object cache already installed: use that
    $transient = new GM\ATP\Cache\TransientHandler();
    if ( $transient->isAvailable() ) {
        $cache_provider->setHandler( $transient );
        $loader->setCacheProvider( $cache_provider );
        $loader->getData();
        exit();
    }
    unset( $transient );

    // let's use Stash and let users choose a driver via
    // `"ajax_template_cache_driver"` and `"ajax_template_{$driver}_driver_conf"` filter hooks.
    // FileSystem driver by default
    $picker = new GM\ATP\Cache\StashDriverPicker( new GM\ATP\FileSystem );
    $driver_class = $picker->getDriverClass();
    if ( class_exists( $driver_class ) ) {
        $driver = new $driver_class;
    }
    $options = $picker->getDriverOptions( $driver_class );
    if ( $picker->checkDriver( $driver, $options ) ) {
        $handler = new GM\ATP\Cache\StashHandler( new Stash\Pool( $driver ) );
        $cache_provider->setHandler( $handler );
        $loader->setCacheProvider( $cache_provider );
    }
    $loader->getData();

    exit();
}

add_action( "wp_ajax_ajaxtemplatepart", 'atp_ajax_callback' );
add_action( "wp_ajax_nopriv_ajaxtemplatepart", 'atp_ajax_callback' );


