<?php

namespace Aurora\System\Facades;

use Aurora\System\Router;

class Route
{
    protected static ?Router $instance = null;

    protected static function getRouter(): Router
    {
        return Router::getInstance();
    }

    /**
     * Add route
     * 
     * @param object|string $sModule module object
     * @param array|string $mRoute oute name or array of route name => callback pairs
     * @param callable|string $mCallbak callback or function name if $mRoute is string
     * @return void
     */
    public static function add($mModule, $mRoute, $mCallbak = null): void
    {
        if (is_string($mModule)) {
            $mModule = \Aurora\System\Api::GetModuleManager()->GetModule($mModule);
        }
        if ($mModule instanceof \Aurora\System\Module\AbstractModule || $mModule instanceof \Aurora\System\Module\AbstractEntries) {
            if (!is_array($mRoute)) {
                $mRoute = [
                    $mRoute => $mCallbak
                ];
            }
            foreach ($mRoute as $sName => $mCallbak) {
                if (!is_callable($mCallbak)) {
                    $mCallbak = [$mModule, $mCallbak];
                }
                self::getRouter()->register($mModule->GetName(), $sName, $mCallbak);
            }
        }
    }

    public static function has($sRoute): bool
    {
        return self::getRouter()->hasRoute($sRoute);
    }

    public static function remove($sRoute): void
    {
        self::getRouter()->removeRoute($sRoute);
    }

    public static function route($sRoute)
    {
        return self::getRouter()->route($sRoute);
    }

    public static function getItems(): array
    {
        return Router::getItems();
    }

    public static function getItemByIndex(int $iIndex, $mDefaultValue = null)
    {
        return Router::getItemByIndex($iIndex, $mDefaultValue);
    }
}
