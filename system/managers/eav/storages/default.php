<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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

/**
 * @internal
 * 
 * @package EAV
 * @subpackage Storages
 */
class CApiEavStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('eav', $sStorageName, $oManager);
	}

	/**
	 */
	public function getAttributes($iEntityId, $sValue)
	{
		return true;
	}	
	
	/**
	 */
	public function getAttribute(CAttribute $oAttribute)
	{
		return true;
	}	
	
	public function getTypes()
	{
		return true;
	}	
	
	/**
	 */
	public function getEntities($sType)
	{
		return true;
	}	
	
}
