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

namespace Aurora\System;

/**
 * @package Api
 */
abstract class AbstractManagerWithStorage extends AbstractManager
{
	/**
	 * @var string
	 */
	protected $sStorageName;

	/**
	 * @var \Aurora\System\AbstractManagerStorage
	 */
	protected $oStorage;

	/**
	 * @param string $sManagerName
	 * @param \Aurora\System\GlobalManager &$oManager
	 * @param string $sForcedStorage
	 * @return \Aurora\System\AbstractManager
	 */
	public function __construct($sManagerName, \Aurora\System\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($sManagerName, $oManager, $oModule);

		$this->oStorage = null;
		$this->sStorageName = !empty($sForcedStorage)
			? strtolower(trim($sForcedStorage)) : strtolower($oManager->GetStorageByType($sManagerName));

		if (isset($this->oModule))
		{
			$this->incDefaultStorage();

			if ($this->incStorage($this->GetStorageName().'.storage', false))
			{
				$sClassName = 'CApi'.ucfirst($oModule->GetName()).ucfirst($this->GetManagerName()).ucfirst($this->GetStorageName()).'Storage';
				$this->oStorage = new $sClassName($this);
			}
			else
			{
				$sClassName = 'CApi'.ucfirst($oModule->GetName()).ucfirst($this->GetManagerName()).'Storage';
				$this->oStorage = new $sClassName($this->sStorageName, $this);
			}
		}
		else
		{
			\Aurora\System\Api::Inc('managers.'.ucfirst($this->GetManagerName()).'.storages.default');

			if (\Aurora\System\Api::Inc('managers.'.ucfirst($this->GetManagerName()).'.storages.'.$this->GetStorageName().'.storage', false))
			{
				$sClassName = 'CApi'.ucfirst($this->GetManagerName()).ucfirst($this->GetStorageName()).'Storage';
				$this->oStorage = new $sClassName($this);
			}
			else
			{
				$sClassName = 'CApi'.ucfirst($this->GetManagerName()).'Storage';
				if (class_exists($sClassName))
				{
					$this->oStorage = new $sClassName($this->sStorageName, $this);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function GetStorageName()
	{
		return $this->sStorageName;
	}

	/**
	 * @return \Aurora\System\AbstractManagerStorage
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
