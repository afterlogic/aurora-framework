<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Classes;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    protected $parent = null;

    protected $parentInheritedAttributes = [];

    public function __get($key)
    {
        if (in_array($key, $this->parentInheritedAttributes)) {
            $value = parent::__get($key);

            if ($value === null && isset($this->parent)) {
                $value = $this->parent->$key;
            }

            return $value;
        }

        return parent::__get($key);
    }

    public function getExtendedProp($sName)
    {
        $mResult = null;
        if (isset($this->Properties[$sName]))
        {
            $mResult = $this->Properties[$sName];
        }

        return $mResult;
    }

    public function setExtendedProp($sName, $sValue)
    {
        $properties = $this->Properties;
        $properties[$sName] = $sValue;
        $this->Properties = $properties;
    }
}