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
    protected $moduleName = null;

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

    public function isInheritedAttribute($key)
    {
        return (in_array($key, $this->parentInheritedAttributes));
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
        }

        return $value;
    }

    public function getExtendedProp($sName)
    {
        $mResult = null;
        if (isset($this->Properties[$sName])) {
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

    public function parent()
    {
        $result = null;
        if (isset($this->parentType) && is_subclass_of($this->parentType, \Aurora\System\Classes\Model::class)) {
            $result = $this->belongsTo($this->parentType, $this->parentKey, $this->primaryKey);
        }

        return $result;
    }
}