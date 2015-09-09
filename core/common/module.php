<?php

/**
 * @package Api
 */
class CApiModuleManager
{
	public function __construct()
	{
		$sModulesPath = $this->GetModulesPath();
		if (@is_dir($sModulesPath))
		{
			if (false !== ($rDirHandle = @opendir($sModulesPath)))
			{
				while (false !== ($sFileItem = @readdir($rDirHandle)))
				{
					if (0 < strlen($sFileItem) && '.' !== $sFileItem{0} && preg_match('/^[a-zA-Z0-9\-]+$/', $sFileItem) &&
						@file_exists($sModulesPath.$sFileItem.'/index.php'))
					{
						$oModule = include_once $sModulesPath.$sFileItem.'/index.php';
						if ($oModule instanceof AApiModule)
						{
							$oModule->SetName($sFileItem);
							$oModule->SetPath($sModulesPath.$sFileItem);
							$oModule->init();
							$this->_aModules[$sFileItem] = $oModule;
						}
					}
				}

				@closedir($rDirHandle);
			}
		}
	}

	/**
	 * @return string
	 */
	public function GetModulesPath()
	{
		return PSEVEN_APP_ROOT_PATH.'modules/';
	}

	/**
	 * @return array
	 */
	public function GetModules()
	{
		return $this->_aModules;
	}
	
	/**
	 * @param string $sModuleName
	 * @return AApiModule
	 */
	public function GetModule($sModuleName)
	{
		return (isset($this->_aModules[$sModuleName]) &&  $this->_aModules[$sModuleName] instanceof AApiModule) ? $this->_aModules[$sModuleName] : false;
	}
	
	/**
	 * @param string $sModuleName
	 * @return bool
	 */
	public function ModuleExists($sModuleName)
	{
		return ($this->GetModule($sModuleName)) ? true  : false;
	}
	
	public function ExecuteMethod($sModuleName, $sMethodName, $aArguments)
	{
		$mResult = false;
		$oModule = $this->GetModule($sModuleName);
		if ($oModule)
		{
			$mResult = $oModule->ExecuteMethod($sMethodName, $aArguments);
		}
		
		return $mResult;
	}

	/**
	 * @return string
	 */
	public function Hash()
	{
		$sResult = md5(CApi::Version());
		foreach ($this->_aModules as $oModule)
		{
			$sResult = md5($sResult.$oModule->GetPath().$oModule->GetName().$oModule->GetHash());
		}

		return $sResult;
	}
}

/**
 * @package Api
 */
abstract class AApiModule
{
	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sPath;

	/**
	 * @var string
	 */
	protected $sVersion;

	/**
	 * @param string $sVersion
	 */
	public function __construct($sVersion)
	{
		$this->sVersion = (string) $sVersion;

		$this->sName = '';
		$this->sPath = '';
	}

	public function init()
	{
	}

	/**
	 * @param string $sName
	 */
	final public function SetName($sName)
	{
		$this->sName = $sName;
	}

	/**
	 * @param string $sPath
	 */
	final public function SetPath($sPath)
	{
		$this->sPath = $sPath;
	}

	/**
	 * @return string
	 */
	public function GetHash()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function GetName()
	{
		return $this->sName;
	}

	/**
	 * @return string
	 */
	public function GetPath()
	{
		return $this->sPath;
	}

	/**
	 * @return string
	 */
	public function GetVersion()
	{
		return $this->sVersion;
	}

	/**
	 * @return string
	 */
	public function GetFullName()
	{
		return $this->sName.'-'.$this->sVersion;
	}
	
	public function GetManager($sManagerName)
	{
		static $aManagersCache = array();

		$sFileFullPath = '';
		if (isset($aManagersCache[$sManagerName]))
		{
			return true;
		}
		else
		{
			$sFileFullPath = $this->GetPath().'/managers/'.$sManagerName.'/manager.php';
			if (@file_exists($sFileFullPath))
			{
				$aManagersCache[$sManagerName] = include_once $sFileFullPath;
				return $aManagersCache[$sManagerName];
			}
		}
		
		return false;
	}

	public function MethodExists($sMethod)
	{
		return method_exists($this, $sMethod);
	}

	public function ExecuteMethod($sMethod, $aArguments)
	{
		$mResult = false;
		if ($this->MethodExists($sMethod))
		{
			$mResult = call_user_func_array(array($this, $sMethod), $aArguments);
		}
		
		return $mResult;
	}
}