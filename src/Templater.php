<?php
/*
 * This file is part of the ATP package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GM\ATP;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package ATP
 */
class Templater
{
    /**
     * @var \WP
     */
    private $wp;

    /**
     * @var \WP_Query
     */
    private $query;

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param \WP       $wp
     * @param \WP_Query $query
     * @param string    $path
     */
    public function __construct(\WP $wp, \WP_Query $query, $path)
    {
        $this->wp = $wp;
        $this->query = $query;
        $this->path = $path;
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * @param  string $raw_name
     * @param  string $raw_slug
     * @param  string $content
     * @return static
     */
    public function addHtml($raw_name, $raw_slug, $content = '')
    {
        static $n = 0;
        $n++;
        $name = filter_var($raw_name, FILTER_SANITIZE_URL);
        $slug = filter_var($raw_slug, FILTER_SANITIZE_URL);
        $ajaxTemplate = urlencode(json_encode(['name' => $name, 'slug' => $slug]));

        global $post;
        $post_attr = '';
        $post instanceof \WP_Post and $post_attr = sprintf(' data-post="%d"', $post->ID);
        empty($content) and $content = $this->htmlContent($name, $slug);

        $class = $this->htmlClass($name, $slug);
        $attr = empty($content) && empty($class) ? ' style="display:none!important;"' : '';
        $id = md5($name.$slug.$n);
        $attr .= " id=\"ajaxtemplate-{$id}\"";
        $format = '<span%s%s data-ajaxtemplate="%s"%s>%s</span>';
        printf($format, $attr, $post_attr, $ajaxTemplate, $class, $content);

        return $this;
    }

    /**
     * @return static
     */
    public function addJs()
    {
        if (wp_script_is('ajax-template-part', 'queue')) {
            return $this;
        }
        $args = ['ajax-template-part', $this->jsUrl(), ['jquery'], $this->jsVer(), true];
        call_user_func_array('wp_enqueue_script', $args);
        $ajax_url = apply_filters('ajax_template_ajax_url', admin_url('admin-ajax.php'));
        $data = [
            'info' => [
                'ajax_url'   => $ajax_url,
                'query_data' => $this->wp->query_vars,
            ],
        ];
        wp_localize_script('ajax-template-part', 'AjaxTemplatePartData', $data);

        return $this;
    }

    /**
     * @param  string $name
     * @param  string $slug
     * @return string
     */
    private function htmlClass($name, $slug)
    {
        $class = apply_filters('ajax_template_loading_class', '', $name, $slug, $this->query);
        if (! is_string($class) || empty($class)) {
            return '';
        }

        return sprintf(' class="%s"', esc_attr(trim($class)));
    }

    /**
     * @param  string $name
     * @param  string $slug
     * @return string
     */
    private function htmlContent($name, $slug)
    {
        $content = apply_filters('ajax_template_loading_content', '', $name, $slug, $this->query);
        if (! is_string($content) || empty($content)) {
            return '';
        }

        return esc_html($content);
    }

    /**
     * @return string
     */
    private function jsUrl()
    {
        $min = $this->debug ? '' : '.min';

        return plugins_url("/js/atp{$min}.js", $this->path);
    }

    /**
     * @return string
     */
    private function jsVer()
    {
        if ($this->debug) {
            return (string)time();
        }

        return @filemtime(dirname($this->path).'/js/atp.min.js') ? : null;
    }
}
