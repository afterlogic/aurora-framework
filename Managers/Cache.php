<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * \Aurora\System\Managers\Cache class summary
 *
 * @package Cache
 */
class Cache
{
	protected static $cacheManager = null;
    
    public static function getPath()
    {
        return rtrim(trim(\Aurora\System\Api::DataPath()), '\\/') . '/temp/.cache/';
    }

    public static function getManager($sStorage = '')
    {
        if (self::$cacheManager === null)
        {
            $slice = new \PHPixie\Slice();
            $config = $slice->arrayData([
                'default' => [
                     'driver' => 'phpfile',
                     'path' => !empty(trim($sStorage)) ? $sStorage : ''
                ]
            ]);
    
            $filesystem = new \PHPixie\Filesystem();		
            $root = $filesystem->root(
                self::getPath()
            );
    
            self::$cacheManager = new \PHPixie\Cache($config, $root);
        }

        return self::$cacheManager;
    }
}
