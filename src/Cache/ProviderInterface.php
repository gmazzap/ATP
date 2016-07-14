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
interface ProviderInterface
{
    /**
     * @param array $id1
     * @param array $id2
     */
    public function get(Array $id1, Array $id2);

    /**
     * @param array $value
     * @param array $id1
     * @param array $id2
     */
    public function set(Array $value, Array $id1, Array $id2);

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @return bool
     */
    public function shouldCache();

    /**
     * @return HandlerInterface
     */
    public function getHandler();

    /**
     * @param \GM\ATP\Cache\HandlerInterface $handler
     */
    public function setHandler(HandlerInterface $handler);
}
