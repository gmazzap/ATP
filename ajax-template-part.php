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

if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

register_activation_hook( __FILE__, 'GM\ATP\activate' );
register_deactivation_hook( __FILE__, 'GM\ATP\deactivate' );

add_action( 'ajaxtemplatepart_cache_purge', 'GM\ATP\cache_purge' );
add_action( "wp_ajax_ajaxtemplatepart", 'GM\ATP\ajax_callback' );
add_action( "wp_ajax_nopriv_ajaxtemplatepart", ' GM\ATP\ajax_callback' );

if ( ! function_exists( 'ajax_template_part' ) ) {

    function ajax_template_part( $name, $slug = '' ) {
        GM\ATP\Templater::tag( $name, $slug );
    }

}

if ( ! function_exists( 'ajax_template_part_content' ) ) {

    function ajax_template_part( $name, $slug = '' ) {
        GM\ATP\Templater::tag( $name, $slug, $content );
    }

}