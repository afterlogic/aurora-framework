<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Classes;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class Model extends Eloquent
{
    /**
     * The module name of the model.
     *
     * @var string
     */
    protected $moduleName = null;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'Id';

    /**
     * The parent type for the model.
     *
     * @var string
     */
    protected $parentType = null;

    /**
     * The parent key for the model.
     *
     * @var string
     */
    protected $parentKey = null;

    /**
     * Inherited attributes.
     *
     * @var array
     */
    protected $parentInheritedAttributes = [];

    public function getInheritedAttributes()
    {
        $aArgs = [];
        $aResult = [];

        \Aurora\System\EventEmitter::getInstance()->emit($this->moduleName, 'getInheritedAttributes', $aArgs, $aResult);
        if (is_array($aResult)) {
            return array_merge($this->parentInheritedAttributes, $aResult);
        }
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function isInheritedAttribute($key)
    {
        return (in_array($key, $this->getInheritedAttributes()));
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function getInheritedValue($key)
    {
        $value = null;
        $parent = $this->parent();
        if ($parent instanceof BelongsTo && isset($this->parent)) {
            $value = $this->parent->$key;
        }
        if ($value === null && is_subclass_of($this->parentType, \Aurora\System\AbstractSettings::class)) {
            if($this->parentType === \Aurora\System\Settings::class) {
                $value = \Aurora\System\Api::GetSettings()->GetValue($key);
            }
            if($this->parentType === \Aurora\System\Module\Settings::class) {
                if (strpos($key, '::') !== false) {
                    list($moduleName, $key) = \explode('::', $key);
                }
                else {
                    $moduleName = $this->moduleName;
                }
                $oModule = \Aurora\System\Api::GetModule($moduleName);
                if ($oModule instanceof \Aurora\System\Module\AbstractModule) {
                    $value = $oModule->getConfig($key, $value);
                }
            }
        }

        return $value;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);
        if (isset($this->Properties[$key])) {
            $value = $this->Properties[$key];
        }
        if ($value === null && $this->isInheritedAttribute($key)) {
            $value = $this->getInheritedValue($key);
        }

        return $value;
    }

    public function getExtendedProp($key)
    {
        $mResult = null;
        if (isset($this->Properties[$key])) {
            $mResult = $this->Properties[$key];
        }

        return $mResult;
    }

    public function setExtendedProp($key, $value)
    {
        $properties = $this->Properties;
        $properties[$key] = $value;
        $this->Properties = $properties;
    }

    public function setExtendedProps($props)
    {
        $properties = $this->Properties;
        $this->Properties = array_merge($properties, $props);
    }

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        $result = null;
        if (isset($this->parentType) && is_subclass_of($this->parentType, \Aurora\System\Classes\Model::class)) {
            $result = $this->belongsTo($this->parentType, $this->parentKey, $this->primaryKey);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toResponseArray()
    {
        $array = $this->toArray();

        $parentInheritedAttributes = $this->getInheritedAttributes();
        if (count($parentInheritedAttributes) > 0) {
            foreach ($parentInheritedAttributes as $attribute) {
                $value = null;
                if (isset($array[$attribute])) {
                    $value = $array[$attribute];
                }
                if ($value === null) {
                    $array[$attribute] = $this->getInheritedValue($attribute);
                }
            }
        }
        if (isset($array['Properties'])) {
            foreach ($array['Properties'] as $key => $value) {
                if ($value !== null) {
                    $array[$key] = $value;
                }
            }
            unset($array['Properties']);
        }

        return $array;
    }

    public function validate()
    {
        return true;
    }
}