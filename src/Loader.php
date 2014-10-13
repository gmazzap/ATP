<?php namespace GM\ATP;

class Loader {

    private $cache;
    private $output;
    private $query_data;
    private $templates_data;
    private $posts_data;

    public function getData() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            return;
        }
        $this->parseRequest();
        if ( empty( $this->templates_data ) ) {
            wp_send_json_error();
        }
        $this->getCache();
        $this->loadTemplates();
        $this->setCache();
        $result = ! empty( $this->output ) ? 'success' : 'error';
        call_user_func( "wp_send_json_{$result}", $this->output );
    }

    public function setCacheProvider( Cache\Provider $cache ) {
        $this->cache = $cache;
    }

    public function getCacheProvider() {
        return $this->cache;
    }

    private function parseRequest() {
        $request = filter_input_array( INPUT_POST, [
            'files_data' => [ 'filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_REQUIRE_ARRAY ],
            'query_data' => [ 'filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_REQUIRE_ARRAY ],
            'posts_data' => [ 'filter' => FILTER_SANITIZE_NUMBER_INT, 'flags' => FILTER_REQUIRE_ARRAY ]
            ] );
        if ( ! empty( $request[ 'files_data' ] ) ) {
            $this->query_data = $request[ 'query_data' ] ? : [ ];
            $this->templates_data = $request[ 'files_data' ];
            $posts_data = array_filter( array_unique( $request[ 'posts_data' ] ) );
            $this->posts_data = ( count( $posts_data ) > 1 ) ? $request[ 'posts_data' ] : [ ];
        }
    }

    private function loadTemplates() {
        $this->buildGlobals();
        $this->output = [ ];
        foreach ( $this->templates_data as $id => $template ) {
            if ( strpos( $id, 'ajaxtemplate-' ) !== 0 ) {
                continue;
            }
            $data = $this->checkTemplateData( $template );
            if ( ! empty( $data ) ) {
                $this->output[ $id ] = $this->loadTemplate( $data, $id );
            }
        }
    }

    private function buildGlobals() {
        global $wp, $wp_query, $wp_the_query, $wp_did_header;
        $wp_did_header = 1;
        $wp->init();
        $wp->query_vars = $this->query_data;
        $wp->query_posts();
        $wp->register_globals();
        if ( ! isset( $wp_the_query ) ) {
            $wp_the_query = $wp_query;
        }
    }

    private function checkTemplateData( $template ) {
        $data = array_values( (array) $template );
        if ( empty( $data ) || ! is_string( $data[ 0 ] ) ) {
            return FALSE;
        }
        $args = [ filter_var( $data[ 0 ], FILTER_SANITIZE_STRING ) ];
        if ( ! empty( $data ) && is_string( $data[ 1 ] ) ) {
            $args[] = filter_var( $data[ 1 ], FILTER_SANITIZE_STRING );
        }
        return array_filter( $args );
    }

    private function loadTemplate( $args, $id ) {
        if ( isset( $this->posts_data[ $id ] ) && (int) $this->posts_data[ $id ] > 0 ) {
            $GLOBALS[ 'post' ] = get_post( $this->posts_data[ $id ] );
            setup_postdata( $GLOBALS[ 'post' ] );
        }
        ob_start();
        @call_user_func_array( 'get_template_part', $args );
        $result = ob_get_clean();
        return $result;
    }

    private function getCache() {
        if ( ! $this->shouldCache() ) {
            return;
        }
        if ( ! empty( $this->posts_data ) ) {
            $this->query_data[ 'posts_data' ] = $this->posts_data;
        }
        $cached = $this->getCacheProvider()->get( $this->templates_data, $this->query_data );
        if ( ! empty( $cached ) && is_array( $cached ) ) {
            wp_send_json_success( $cached );
        }
    }

    private function setCache() {
        if ( ! $this->shouldCache() || empty( $this->output ) ) {
            return;
        }
        add_action( 'shutdown', function() {
            $this->getCacheProvider()->set( $this->output, $this->templates_data, $this->query_data );
        } );
    }

    private function shouldCache() {
        $provider = $this->getCacheProvider();
        return $provider instanceof Cache\Provider && $provider->shouldCache();
    }

}