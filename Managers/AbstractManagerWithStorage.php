<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

/**
 * @package Api
 */
abstract class AbstractManagerWithStorage extends AbstractManager
{
	/**
	 * @var \Aurora\System\Managers\AbstractStorage
	 */
	public $oStorage;
	
	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 * @param \Aurora\System\Managers\AbstractStorage $oStorage
	 * @return \Aurora\System\Managers\AbstractManager
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule, AbstractStorage $oStorage)
	{
		parent::__construct($oModule);
		$this->oStorage = $oStorage;
	}

	/**
	 * @return \Aurora\System\Managers\AbstractStorage
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
