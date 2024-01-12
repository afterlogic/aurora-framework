<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

use Illuminate\Container\Container;
use Illuminate\Cache\CacheManager;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Cache
 */
class Cache
{
    protected $cacheManager = null;

    public function __construct($sStorage)
    {
        if (!file_exists(self::getPath())) {
            @mkdir(self::getPath(), 0777, true);
        }
        $sStoragePath = self::getPath() . '/' . $sStorage;
        if (!file_exists($sStoragePath)) {
            @mkdir($sStoragePath, 0777, true);
        }

        if ($this->cacheManager === null) {
            $app = new Container();
            Container::setInstance($app);
            $app->singleton('files', function () {
                return new \Illuminate\Filesystem\Filesystem();
            });

            $app->singleton('config', function () use ($sStoragePath) {
                return [
                    'cache.default' => 'file',
                    'cache.stores.file' => [
                        'driver' => 'file',
                        'path' => $sStoragePath
                    ]
                ];
            });

            $cacheManager = new CacheManager($app);
            $this->cacheManager = $cacheManager->driver();
        }
    }

    public function getInstance($sStorage)
    {
        return new self($sStorage);
    }

    public static function getPath()
    {
        return rtrim(trim(\Aurora\System\Api::DataPath()), '\\/') . '/temp/.cache/';
    }

    public function set($key, $value)
    {
        $this->cacheManager->put($key, $value);
    }

    public function get($key)
    {
        return $this->cacheManager->get($key);
    }

    public function has($key)
    {
        $result = $this->cacheManager->get($key);
        return $result !== null;
    }

    public function delete($key)
    {
        return $this->cacheManager->forget($key);
    }
}
