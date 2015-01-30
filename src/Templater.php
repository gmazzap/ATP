<?php namespace GM\ATP;

class Templater
{

    private $wp;
    private $query;
    private $path;
    private $debug;

    public function __construct(\WP $wp, \WP_Query $query, $path)
    {
        $this->wp = $wp;
        $this->query = $query;
        $this->path = $path;
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
    }

    public function addHtml($raw_name, $raw_slug, $content = '')
    {
        static $n = 0;
        $n ++;
        $name = filter_var($raw_name, FILTER_SANITIZE_URL);
        $slug = filter_var($raw_slug, FILTER_SANITIZE_URL);
        $ajaxtemplate = urlencode(
            json_encode(['name' => $name, 'slug' => $slug])
        );
        global $post;
        $post_attr = '';
        if ($post instanceof \WP_Post) {
            $post_attr = sprintf(' data-post="%d"', $post->ID);
        }
        if (empty($content)) {
            $content = $this->getHtmlContent($name, $slug);
        }
        $class = $this->getHtmlClass($name, $slug);
        $attr = empty($content) && empty($class) ? ' style="display:none!important;"' : '';
        $id = md5($name.$slug.$n);
        $attr .= " id=\"ajaxtemplate-{$id}\"";
        $format = '<span%s%s data-ajaxtemplate="%s"%s>%s</span>';
        printf($format, $attr, $post_attr, $ajaxtemplate, $class, $content);
    }

    public function addJs()
    {
        if (wp_script_is('ajax-template-part', 'queue')) {
            return;
        }
        $args = [ 'ajax-template-part', $this->getJsUrl(), [ 'jquery'], $this->getJsVer(), TRUE];
        call_user_func_array('wp_enqueue_script', $args);
        $ajax_url = apply_filters('ajax_template_ajax_url', admin_url('admin-ajax.php'));
        $data = [
            'info' => [ 'ajax_url' => $ajax_url, 'query_data' => $this->wp->query_vars]
        ];
        wp_localize_script('ajax-template-part', 'AjaxTemplatePartData', $data);
    }

    private function getHtmlClass($name, $slug)
    {
        $class = apply_filters('ajax_template_loading_class', '', $name, $slug, $this->query);
        if ( ! is_string($class) || empty($class)) {
            return '';
        }
        return sprintf(' class="%s"', esc_attr(trim($class)));
    }

    private function getHtmlContent($name, $slug)
    {
        $content = apply_filters('ajax_template_loading_content', '', $name, $slug, $this->query);
        if ( ! is_string($content) || empty($content)) {
            return '';
        }
        return esc_html($content);
    }

    private function getJsUrl()
    {
        $min = $this->debug ? '' : '.min';
        return plugins_url("/js/atp{$min}.js", $this->path);
    }

    private function getJsVer()
    {
        $ver = NULL;
        if ( ! $this->debug) {
            $ver = @filemtime(dirname($this->path).'/js/atp.min.js');
        }
        return $ver;
    }

}