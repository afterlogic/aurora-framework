<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/saas/Exception.php';

/**
 * @brief 
 */
abstract class NativeService
{
	private $nativeId = 0;	 ///< Native Id
	private $oNative = false;	///< Native object
	protected $aServiceManagers = array(); ///< Subservice managers
	protected $aCachedFields = array();

	abstract protected function findNativeById($id);

	abstract protected function createNativeService();

	abstract protected function idField($obj);

	abstract protected function nativeFieldMap();

	protected function __construct($nativeId = 0)
	{
		$this->nativeId = $nativeId;
	}

	protected function nativeId()
	{
		return $this->nativeId;
	}

	protected function nativeField($fieldName)
	{
		if (!$this->nativeId && !$this->oNative)
			return (isset($this->aCachedFields[$fieldName]) ? $this->aCachedFields[$fieldName] : false);
		return $this->nativeService()->$fieldName;
	}

	protected function setNativeField($fieldName, $value)
	{
		if (!$this->nativeId && !$this->oNative)
			$this->aCachedFields[$fieldName] = $value;
		else
			$this->nativeService()->$fieldName = $value;
	}

	protected function nativeCachedField($fieldName)
	{
		return isset($this->aCachedFields[$fieldName]) ? $this->aCachedFields[$fieldName] : false;
	}

	protected function flushCache()
	{
		foreach ($this->aCachedFields as $key => $value)
			$this->setNativeField($key, $value);

		$this->aCachedFields = array();
	}

	function postAddInstance()
	{
		$this->nativeId = $this->idField($this->oNative);
	}

	function nativeService()
	{
		if ($this->oNative)
		{
			return $this->oNative;
		}

		if (!$this->nativeId && !$this->oNative)
		{
			$this->oNative = $this->createNativeService();
			$this->nativeId = $this->idField($this->oNative);
			$this->flushCache();
		}

		if (!$this->oNative)
		{
			$this->oNative = $this->findNativeById($this->nativeId);
		}

		// TODO: throw exception here is case of $this->oNativeTenant == null ??
		if (!$this->oNative)
		{
			Exception::throwException(new \Exception('No native object'));
		}

		return $this->oNative;
	}

	function cleanup()
	{
		$this->oNative = null;
		$this->nativeId = 0;
		$this->aCachedFields = array();
	}

	/**
	 * @param string $name expected values: 'user', 'domain'
	 * @see saas\api\ITenant::serviceManager()
	 */
	function serviceManager($name)
	{
		// TODO: смотреть существование функции, а не по массиву
		$aServiceManagers = &$this->aServiceManagers;
		if (strlen($name) <= 0 || !isset($aServiceManagers[$name]))
		{
			Exception::throwException(new \Exception("Service manager $name not found!"));
			return false;
		}

		$service = &$aServiceManagers[$name];
		if (!$service)
		{
			$fname = 'create'.strtoupper($name[0]).substr($name, 1).'ServiceManager';
			$service = $this->$fname();
		}

		return $service;
	}

	function id()
	{
		return $this->nativeId();
	}

	function cachedFields()
	{
		$fieldMap = $this->nativeFieldMap();

		$res = array();
		foreach ($this->aCachedFields as $key => $value)
		{
			if (isset($fieldMap[$key]))
				$key = $fieldMap[$key];
			$res[$key] = $value;
		}

		return $res;
	}
}
