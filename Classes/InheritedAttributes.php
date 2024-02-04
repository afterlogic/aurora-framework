<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Classes;

class InheritedAttributes
{
    protected static $attributes = [];

    public static function addAttributes($model, $attributes)
    {
        if (!isset(self::$attributes[$model])) {
            self::$attributes[$model] = [];
        }
        self::$attributes[$model] = array_merge(
            self::$attributes[$model],
            $attributes
        );
    }

    public static function getAttributes($model)
    {
        if (isset(self::$attributes[$model])) {
            return self::$attributes[$model];
        }

        return [];
    }

    /**
     * @param  string  $model
     * @param  string  $key
     * @return bool
     */
    public static function hasAttribute($model, $key)
    {
        if (isset(self::$attributes[$model])) {
            return in_array($key, self::$attributes[$model]);
        }

        return false;
    }
}
