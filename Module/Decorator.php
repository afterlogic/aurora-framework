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
