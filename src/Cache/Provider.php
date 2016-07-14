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
class Provider implements ProviderInterface
{
    /**
     * @var \GM\ATP\Cache\HandlerInterface
     */
    private $handler;

    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @return \GM\ATP\Cache\HandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param \GM\ATP\Cache\HandlerInterface $handler
     */
    public function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param  array $id1
     * @param  array $id2
     * @return mixed
     */
    public function get(array $id1, array $id2)
    {
        $key = $this->id($id1, $id2);

        return $this->getHandler()->get($key);
    }

    /**
     * @param  array $value
     * @param  array $id1
     * @param  array $id2
     * @return mixed
     */
    public function set(array $value, array $id1, array $id2)
    {
        $key = $this->id($id1, $id2);

        return $this->getHandler()->set($key, $value, $this->ttl());
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return apply_filters('ajax_template_cache', (defined('WP_DEBUG') && ! WP_DEBUG));
    }

    /**
     * @return bool
     */
    public function shouldCache()
    {
        if ($this->isEnabled()) {
            $handler = $this->getHandler();

            return $handler instanceof HandlerInterface && $handler->isAvailable();
        }

        return false;
    }

    /**
     * @param  array $id1
     * @param  array $id2
     * @return string
     */
    private function id(array $id1, array $id2)
    {
        if (! is_null($this->id)) {
            return $this->id;
        }

        $a = array_filter($id1);
        $b = array_filter($id2);
        ksort($a);
        ksort($b);
        $this->id = 'GM_ATP_'.md5(serialize($a).serialize($b));

        return $this->id;
    }

    /**
     * @return int
     */
    private function ttl()
    {
        if (is_null($this->ttl)) {
            $ttl = apply_filters('ajax_template_cache_ttl', HOUR_IN_SECONDS);
            $this->ttl = is_scalar($ttl) && (int)$ttl > 30 ? (int)$ttl : HOUR_IN_SECONDS;
        }

        return $this->ttl;
    }
}
