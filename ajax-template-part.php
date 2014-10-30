<?php
/*
  Plugin Name: Ajax Template Part
  Plugin URI: https://github.com/Giuseppe-Mazzapica/ajax-template-part
  Description: Introduce ajax_template_part() function: like get_template_part(), but AJAX powered.
  Author: Giuseppe Mazzapica
  Author URI: http://gm.zoomlab.it/
  License: MIT
 */

/*
  The MIT License (MIT)

  Copyright (c) 2014 Giuseppe Mazzapica

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:
  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.
  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
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
        GM\ATP\template_part( $name, $slug );
    }

}

if ( ! function_exists( 'ajax_template_part_content' ) ) {

    function ajax_template_part_content( $content, $name, $slug = '' ) {
        GM\ATP\template_part( $name, $slug, $content );
    }

}