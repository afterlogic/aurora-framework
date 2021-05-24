<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

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

    public function __construct($sStorage, $sPath, $sDriver = 'phpfile')
    {
        if (!file_exists(self::getPath()))
        {
            @mkdir(self::getPath(), 0777, true);
        }
        $sStoragePath = self::getPath() . '/' . $sStorage;
        if (!file_exists($sStoragePath))
        {
            @mkdir($sStoragePath, 0777, true);
        }

        if ($this->cacheManager === null)
        {
            $slice = new \PHPixie\Slice();
            $filesystem = new \PHPixie\Filesystem();

            $this->cacheManager = new \PHPixie\Cache(
                $slice->arrayData([
                    'default' => [
                         'driver' => $sDriver,
                         'path' => !empty(trim($sPath)) ? $sPath : ''
                    ]
                ]),
                $filesystem->root(
                    $sStoragePath
                )
            );
        }
    }

    public function getInstance($sStorage, $sPath)
    {
        return new self($sStorage, $sPath);
    }

    public static function getPath()
    {
        return rtrim(trim(\Aurora\System\Api::DataPath()), '\\/') . '/temp/.cache/';
    }

    public function set($key, $value)
    {
        $this->cacheManager->set($key, $value);
    }

    public function get($key)
    {
        return $this->cacheManager->get($key);
    }

    public function has($key)
    {
        return $this->cacheManager->has($key);
    }

    public function delete($key)
    {
        return $this->cacheManager->delete($key);
    }
}
