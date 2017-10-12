<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

/**
 * @package Api
 */
class Decorator
{
    /**
	 *
	 * @var \Aurora\System\Module\AbstractModule
	 */
	protected $oModule;

    /**
	 * 
	 * @param string $sModuleName
	 */
	public function __construct($sModuleName) 
	{
		$this->oModule = \Aurora\System\Api::GetModule($sModuleName);
    }	
	
	/**
	 * 
	 * @param string $sMethodName
	 * @param array $aArguments
	 * @return mixed
	 */
	public function __call($sMethodName, $aArguments) 
	{
		return ($this->oModule instanceof AbstractModule) ? $this->oModule->CallMethod($sMethodName, $aArguments) : false;
	}
}
