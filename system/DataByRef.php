<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace System;

/**
 * @category Core
 * @package Base
 */
class DataByRef
{
	protected $aData;
	
	public static function createInstance($mData = null)
	{
		$oResult = new DataByRef();
		$oResult->aData = $mData;
		
		return $oResult;
	}
	
	public function getData()
	{
		return $this->aData;
	}

	public function setData($mData)
	{
		$this->aData = $mData;
	}
}
