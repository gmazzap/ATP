<?php namespace GM\ATP\Cache;

class Provider implements ProviderInterface
{

    private $handler;
    private $key;
    private $ttl;

    public function getHandler()
    {
        return $this->handler;
    }

    public function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function get(Array $id1, Array $id2)
    {
        $key = $this->getKey($id1, $id2);
        return $this->getHandler()->get($key);
    }

    public function set(Array $value, Array $id1, Array $id2)
    {
        $key = $this->getKey($id1, $id2);
        return $this->getHandler()->set($key, $value, $this->getTTL());
    }

    public function isEnabled()
    {
        return apply_filters('ajax_template_cache', ( defined('WP_DEBUG') && ! WP_DEBUG));
    }

    public function shouldCache()
    {
        if ($this->isEnabled()) {
            $handler = $this->getHandler();
            return $handler instanceof HandlerInterface && $handler->isAvailable();
        }
        return FALSE;
    }

    private function getKey(Array $id1, Array $id2)
    {
        if ( ! is_null($this->key)) {
            return $this->key;
        }
        $a = array_filter($id1);
        $b = array_filter($id2);
        ksort($a);
        ksort($b);
        $this->key = 'GM_ATP_'.md5(serialize($a).serialize($b));
        return $this->key;
    }

    private function getTTL()
    {
        if (is_null($this->ttl)) {
            $ttl = apply_filters('ajax_template_cache_ttl', HOUR_IN_SECONDS);
            $this->ttl = is_scalar($ttl) && (int) $ttl > 30 ? (int) $ttl : HOUR_IN_SECONDS;
        }
        return $this->ttl;
    }

}