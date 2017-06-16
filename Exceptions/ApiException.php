<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
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
