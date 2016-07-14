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

use Stash\Interfaces\ItemInterface;
use Stash\Interfaces\PoolInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package ATP
 */
class StashHandler implements HandlerInterface
{
    /**
     * @var \Stash\Interfaces\PoolInterface
     */
    private $stash;

    /**
     * @param \Stash\Interfaces\PoolInterface $pool
     */
    public function __construct(PoolInterface $pool)
    {
        return $this->stash = $pool;
    }

    /**
     * @param  string          $key
     * @return bool|\Exception
     */
    public function clear($key)
    {
        try {
            $item = $this->getItem($key);
            if (! $item instanceof ItemInterface) {
                return false;
            }

            $item->clear();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        try {
            $item = $this->getItem($key);
            if (! $item instanceof ItemInterface) {
                return false;
            }
            $cached = $item->get();

            return $item->isMiss() ? false : $cached;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param  string $key
     * @param  mixed  $value
     * @param  int    $expiration
     * @return bool
     */
    public function set($key, $value, $expiration)
    {
        try {
            $item = $this->getItem($key);
            if (! $item instanceof ItemInterface) {
                return false;
            }
            $item->clear();
            $item->lock();

            return $item->set($value, $expiration);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return call_user_func([get_class($this->getStash()->getDriver()), 'isAvailable']);
    }

    /**
     * @return \Stash\Interfaces\PoolInterface
     */
    public function getStash()
    {
        return $this->stash;
    }

    /**
     * @param $key
     * @return \Stash\Interfaces\ItemInterface|null
     */
    private function getItem($key)
    {
        try {
            return $this->getStash()->getItem($key);
        } catch (\Exception $e) {
            return;
        }
    }
}
