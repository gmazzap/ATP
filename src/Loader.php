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
class Loader
{
    /**
     * @var \GM\ATP\Cache\Provider
     */
    private $cache;

    /**
     * @var string[]
     */
    private $output = [];

    /**
     * @var array
     */
    private $query_data = [];

    /**
     * @var array
     */
    private $templates_data = [];

    /**
     * @var array
     */
    private $posts_data = [];

    /**
     * Return data from AJAX request.
     */
    public function getData()
    {
        if (! defined('DOING_AJAX') || ! DOING_AJAX) {
            return;
        }

        $this->parseRequest();

        if (empty($this->templates_data)) {
            wp_send_json_error();
        }

        $this->getCache();
        $this->loadTemplates();
        $this->setCache();
        $this->output ? wp_send_json_success($this->output) : wp_send_json_error($this->output);
    }

    /**
     * @param \GM\ATP\Cache\Provider $cache
     */
    public function setCacheProvider(Cache\Provider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return mixed
     */
    public function getCacheProvider()
    {
        return $this->cache;
    }

    /**
     * Parse HTTP request and collect request data.
     */
    private function parseRequest()
    {
        $request = filter_input_array(INPUT_POST, [
            'files_data' => [
                'filter' => FILTER_UNSAFE_RAW,
                'flags'  => FILTER_REQUIRE_ARRAY,
            ],
            'query_data' => [
                'filter' => FILTER_UNSAFE_RAW,
                'flags'  => FILTER_REQUIRE_ARRAY,
            ],
            'posts_data' => [
                'filter' => FILTER_SANITIZE_NUMBER_INT,
                'flags'  => FILTER_REQUIRE_ARRAY,
            ]
        ]);

        $this->query_data = $request['query_data'] ? : [];
        $this->templates_data = $request['files_data'];
        $posts_data = array_filter(array_unique($request['posts_data']));
        $this->posts_data = (count($posts_data) > 1) ? $request['posts_data'] : [];
    }

    /**
     * Load template parts, setting up context.
     */
    private function loadTemplates()
    {
        $this->buildGlobals();
        $this->output = [];
        foreach ($this->templates_data as $id => $template) {
            if (strpos($id, 'ajaxtemplate-') !== 0) {
                continue;
            }
            $data = $this->checkTemplateData($template);
            if (! $data) {
                $this->output[$id] = $this->loadTemplate($data, $id);
            }
        }
    }

    /**
     * Build global context.
     */
    private function buildGlobals()
    {
        global $wp, $wp_query, $wp_the_query, $wp_did_header;
        $wp_did_header = 1;
        $wp->init();
        $wp->query_vars = $this->query_data;
        $wp->query_posts();
        $wp->register_globals();
        isset($wp_the_query) or $wp_the_query = $wp_query;
    }

    /**
     * Sanitize template parts data from request.
     *
     * @param $template
     * @return array
     */
    private function checkTemplateData($template)
    {
        $data = array_values((array)$template);
        if (empty($data) || ! is_string($data[0])) {
            return [];
        }
        $args = [filter_var($data[0], FILTER_SANITIZE_STRING)];
        if (! empty($data) && is_string($data[1])) {
            $args[] = filter_var($data[1], FILTER_SANITIZE_STRING);
        }

        return array_filter($args);
    }

    /**
     * Load a single template part, by calling get_template_part().
     *
     * @param $args
     * @param $id
     * @return string
     */
    private function loadTemplate($args, $id)
    {
        if (isset($this->posts_data[$id]) && (int)$this->posts_data[$id] > 0) {
            $GLOBALS['post'] = get_post($this->posts_data[$id]);
            setup_postdata($GLOBALS['post']);
        }
        ob_start();
        @call_user_func_array('get_template_part', $args);
        $result = ob_get_clean();

        return $result;
    }

    /**
     * Get cached data if available.
     */
    private function getCache()
    {
        if (! $this->shouldCache()) {
            return;
        }
        if (! empty($this->posts_data)) {
            $this->query_data['posts_data'] = $this->posts_data;
        }
        $cached = $this->getCacheProvider()->get($this->templates_data, $this->query_data);
        if (! empty($cached) && is_array($cached)) {
            wp_send_json_success($cached);
        }
    }

    /**
     * Stores data in cache on shutdown.
     */
    private function setCache()
    {
        if (! $this->shouldCache() || empty($this->output)) {
            return;
        }

        add_action('shutdown', function () {
            $this->getCacheProvider()->set($this->output, $this->templates_data, $this->query_data);
        });
    }

    /**
     * Is cache available and enabled?
     *
     * @return bool
     */
    private function shouldCache()
    {
        $provider = $this->getCacheProvider();

        return $provider instanceof Cache\Provider && $provider->shouldCache();
    }
}
