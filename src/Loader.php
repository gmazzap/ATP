<?php namespace GM\ATP;

class Loader {

    private $cache;
    private $output;
    private $query_data;
    private $templates_data;

    public function getData() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            return;
        }
        $this->parseRequest();
        if ( empty( $this->query_data ) || empty( $this->templates_data ) ) {
            wp_send_json_error();
        }
        $this->getCache();
        $this->loadTemplates();
        $this->setCache();
        $result = ! empty( $this->output ) ? 'success' : 'error';
        call_user_func( "wp_send_json_{$result}", $this->output );
    }

    public function setCacheProvider( CacheProvider $cache ) {
        $this->cache = $cache;
    }

    public function getCacheProvider() {
        return $this->cache;
    }

    private function parseRequest() {
        $request = filter_input_array( INPUT_POST, [
            'files_data' => [ 'filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_REQUIRE_ARRAY ],
            'query_data' => [ 'filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_REQUIRE_ARRAY ]
            ] );
        if ( ! empty( $request[ 'files_data' ] ) && ! empty( $request[ 'query_data' ] ) ) {
            $this->query_data = $request[ 'query_data' ];
            $this->templates_data = $request[ 'files_data' ];
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
                $this->output[ $id ] = $this->loadTemplate( $data );
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
        if ( ! empty( $data ) && is_string( $data[ 0 ] ) ) {
            $args[] = filter_var( $data[ 0 ], FILTER_SANITIZE_STRING );
        }
        return array_filter( $args );
    }

    private function loadTemplate( $args ) {
        ob_start();
        @call_user_func_array( 'get_template_part', $args );
        return ob_get_clean();
    }

    private function getCache() {
        if ( ! $this->shouldCache() ) {
            return;
        }
        $provider = $this->getCacheProvider();
        $key = $provider->getKey( $this->templates_data, $this->query_data );
        $item = $provider->getPool()->getItem( $key );
        $data = $item->get();
        if ( ! $item->isMiss() && ! empty( $data ) ) {
            wp_send_json_success( $data );
        }
    }

    private function setCache() {
        if ( ! $this->shouldCache() || empty( $this->output ) ) {
            return;
        }
        add_action( 'shutdown', function() {
            $provider = $this->getCacheProvider();
            $key = $provider->getKey( $this->templates_data, $this->query_data );
            $item = $provider->getPool()->getItem( $key );
            $item->clear();
            $item->lock();
            $item->set( $this->output, $provider->getTTL() );
        } );
    }

    private function shouldCache() {
        $provider = $this->getCacheProvider();
        return ! is_null( $provider ) && $provider->getPool() instanceof \Stash\PoolInterface;
    }

}