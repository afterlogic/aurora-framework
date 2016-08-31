<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace System\Exceptions;

/**
 * @category Core
 * @package Exceptions
 */
class AuroraApiException extends Exception
{
	/**
	 * @var array
	 */
	protected $aObjectParams;

	
	/**
	 * @var \AApiModule
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
		parent::__construct('' === $sMessage ? 'AuroraApiException' : $sMessage, $iCode, $oPrevious);
	}
	
	/**
	 * @return array
	 */
	public function GetObjectParams()
	{
		return $this->aObjectParams;
	}	
	
	/**
	 * @return \AApiModule
	 */
	public function GetModule()
	{
		return $this->oModule;
	}	
}
