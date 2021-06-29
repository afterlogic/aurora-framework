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
    use DisabledModulesTrait;

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
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        if (strpos($key, '::') !== false) {
            $this->setExtendedProp($key, $value);
        } else {
            parent::__set($key, $value);
        }
    }

    public function getEntityIdAttribute()
    {
        return $this->Id;
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

        /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        $value = parent::__isset($key);
        if (!$value) {
            $value = isset($this->Properties[$key]);
        }

        return $value;
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        parent::__unset($key);
        if (isset($this->Properties[$key])) {
            unset($this->Properties[$key]);
        }
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
        $properties = is_array($this->Properties) ? $this->Properties : [];
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

    	/**
     * Returns a pseudo-random v4 UUID
     *
     * This function is based on a comment by Andrew Moore on php.net
     *
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     * @return string
     */
    public function generateUUID()
	{
        return sprintf(

            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * Checks if a string is a valid UUID.
     *
     * @param string $uuid
     * @return bool
     */
    public function validateUUID($uuid)
	{
        return preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            $uuid
        ) == true;
    }

    	/**
	 *
	 * @param type $aProperties
	 */
	public function populate($aProperties)
	{
        foreach ($aProperties as $key => $value) {
            if (in_array($key, $this->fillable) || strpos($key, '::') !== false) {
                $this->$key = $value;
            }
        }
	}
}