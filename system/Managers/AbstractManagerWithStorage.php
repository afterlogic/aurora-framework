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

namespace Aurora\System\Managers;

/**
 * @package Api
 */
abstract class AbstractManagerWithStorage extends AbstractManager
{

	/**
	 * @var \Aurora\System\Managers\AbstractManagerStorage
	 */
	public $oStorage;

	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 * @param \Aurora\System\Managers\AbstractManagerStorage $oStorage
	 * @return \Aurora\System\Managers\AbstractManager
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null, \Aurora\System\Managers\AbstractManagerStorage $oStorage = null)
	{
		parent::__construct($oModule);
		$this->oStorage = $oStorage;
	}

	/**
	 * @return \Aurora\System\Managers\AbstractManagerStorage
	 */
	public function &GetStorage()
	{
		return $this->oStorage;
	}

	public function moveStorageExceptionToManager()
	{
		if ($this->oStorage)
		{
			$oException = $this->oStorage->GetStorageException();
			if ($oException)
			{
				$this->oLastException = $oException;
			}
		}
	}
}
