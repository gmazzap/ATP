<?php
/*
 * This file is part of the ATP package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GM\ATP\Cache;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package ATP
 */
class TransientHandler implements HandlerInterface
{
    /**
     * @param  string $key
     * @return bool
     */
    public function clear($key)
    {
        return delete_transient($key);
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function get($key)
    {
        return get_transient($key) ? : false;
    }

    /**
     * @param  string $key
     * @param  mixed  $value
     * @param  int    $expiration
     * @return bool
     */
    public function set($key, $value, $expiration)
    {
        return set_transient($key, $value, $expiration);
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return apply_filters('ajax_template_force_transient', wp_using_ext_object_cache());
    }
}
