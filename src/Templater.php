<?php namespace GM\ATP;

class Templater {

    private static $templater = NULL;
    private $wp;
    private $query;
    private $path;
    private $debug;

    public static function tag( $name, $slug ) {
        if ( is_admin() || ! is_string( $name ) || ! is_string( $slug ) ) {
            return;
        }
        if ( is_null( self::$templater ) ) {
            $class = get_called_class();
            $path = dirname( dirname( __FILE__ ) ) . '/ajax-template-part.php';
            self::$templater = new $class( $GLOBALS[ 'wp' ], $GLOBALS[ 'wp_query' ], $path );
            self::$templater->addJs();
        }
        self::$templater->addHtml( sanitize_title( $name ), sanitize_title( $slug ) );
    }

    public function __construct( \WP $wp, \WP_Query $query, $path ) {
        $this->wp = $wp;
        $this->query = $query;
        $this->path = $path;
        $this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
    }

    public function addHtml( $raw_name, $raw_slug ) {
        static $n = 0;
        $n ++;
        $html_class = $content = '';
        $name = esc_attr( filter_var( $raw_name, FILTER_SANITIZE_URL ) );
        $slug = esc_attr( filter_var( $raw_slug, FILTER_SANITIZE_URL ) );
        $attr = ' style="display:none!important;"';
        if ( apply_filters( 'ajax_template_show_loading', FALSE, $name, $slug, $this->query ) ) {
            $html_class = $this->getHtmlClass( $name, $slug );
            $content = $this->getHtmlContent( $name, $slug );
            $attr = '';
        }
        $attr .= " id=\"ajaxtemplate-{$name}-{$slug}-{$n}\"";
        $format = '<span%s data-ajaxtemplatename="%s" data-ajaxtemplateslug="%s"%s>%s</span>';
        printf( $format, $attr, $name, $slug, $html_class, $content );
    }

    public function addJs() {
        if ( wp_script_is( 'ajax-template-part', 'queue' ) ) {
            return;
        }
        $args = [ 'ajax-template-part', $this->getJsUrl(), [ 'jquery' ], $this->getJsVer(), TRUE ];
        call_user_func_array( 'wp_enqueue_script', $args );
        $ajax_url = apply_filters( 'ajax_template_ajax_url', admin_url( 'admin-ajax.php' ) );
        $data = [
            'info' => [ 'ajax_url' => $ajax_url, 'query_data' => $this->wp->query_vars ]
        ];
        wp_localize_script( 'ajax-template-part', 'AjaxTemplatePartData', $data );
    }

    private function getHtmlClass( $name, $slug ) {
        $class = apply_filters( 'ajax_template_loading_class', '', $name, $slug, $this->query );
        if ( ! is_string( $class ) || empty( $class ) ) {
            return '';
        }
        return sprintf( ' class="%s"', esc_attr( trim( $class ) ) );
    }

    private function getHtmlContent( $name, $slug ) {
        $content = apply_filters( 'ajax_template_loading_content', '', $name, $slug, $this->query );
        if ( ! is_string( $content ) || empty( $content ) ) {
            return '';
        }
        return esc_html( $content );
    }

    private function getJsUrl() {
        $min = $this->debug ? '' : '.min';
        return plugins_url( "/js/atp{$min}.js", $this->path );
    }

    private function getJsVer() {
        $ver = NULL;
        if ( ! $this->debug ) {
            $ver = @filemtime( dirname( $this->path ) . '/js/atp.min.js' );
        }
        return $ver;
    }

}