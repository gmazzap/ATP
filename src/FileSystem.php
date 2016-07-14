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
class FileSystem
{
    /**
     * @return string
     */
    public function getFolder()
    {
        $upload = wp_upload_dir();
        $path = trailingslashit($upload['basedir']).'ajax_query_template/cache';

        return wp_mkdir_p($path) ? $path : '';
    }
}
