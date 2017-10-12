<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Exceptions;

/**
 * @category Core
 * @package Exceptions
 */
class ApiException extends Exception
{
	/**
	 * @var array
	 */
	protected $aObjectParams;

	
	/**
	 * @var \Aurora\System\Module\AbstractModule
	 */
	protected $oModule;
	
	/**
	 * @param type $iCode
	 * @param type $oPrevious
	 * @param type $sMessage
	 */
	public function __construct($iCode, $oPrevious = null, $sMessage = '', $aObjectParams = array(), $oModule = null)
	{
		$this->aObjectParams = $aObjectParams;
		$this->oModule = $oModule;
		$mCode = is_int($iCode) ? $iCode : 0;
		parent::__construct('' === $sMessage ? 'ApiException' : $sMessage, $mCode, $oPrevious);
	}
	
	/**
	 * @return array
	 */
	public function GetObjectParams()
	{
		return $this->aObjectParams;
	}	
	
	/**
	 * @return \Aurora\System\Module\AbstractModule
	 */
	public function GetModule()
	{
		return $this->oModule;
	}	
}
