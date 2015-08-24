<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Core\Exceptions;

/**
 * @category Core
 * @package Exceptions
 */
class ClientException extends Exception
{
	/**
	 * @var array
	 */
	protected $aObjectParams;

	/**
	 * @param type $iCode
	 * @param type $oPrevious
	 * @param type $sMessage
	 */
	public function __construct($iCode, $oPrevious = null, $sMessage = '', $aObjectParams = array())
	{
		$this->aObjectParams = $aObjectParams;
		parent::__construct('' === $sMessage ? 'ClientException' : $sMessage, $iCode, $oPrevious);
	}
	
	/**
	 * @return array
	 */
	public function GetObjectParams()
	{
		return $this->aObjectParams;
	}	
}
