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
interface HandlerInterface
{
    /**
     * @param $key
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $expiration
     */
    public function set($key, $value, $expiration);

    /**
     * @param string $key
     */
    public function clear($key);

    /**
     * @return bool
     */
    public function isAvailable();
}
