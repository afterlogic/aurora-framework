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

/**
 * @package Api
 */

namespace Aurora\System;

class GlobalManager
{
	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	/**
	 * @var CDbStorage
	 */
	protected $oConnection;

	/**
	 * @var IDbHelper
	 */
	protected $oSqlHelper;

	/**
	 * @var array
	 */
	protected $aManagers;

	/**
	 * @var array
	 */
	protected $aStorageMap;

	public function __construct()
	{
		$this->oSettings = null;
		$this->oConnection = null;
		$this->oSqlHelper = null;
		$this->aManagers = array();
		$this->aStorageMap = array(
			'db' => 'db',
			'filecache' => 'file'
		);

		if (\Aurora\System\Api::GetConf('gcontacts.ldap', false))
		{
			$this->aStorageMap['gcontacts'] = 'ldap';
		}

		if (\Aurora\System\Api::GetConf('contacts.ldap', false))
		{
			$this->aStorageMap['contactsmain'] = 'ldap';
		}
	}
	
	public function GetManagers()
	{
		return $this->aManagers;
	}

	public function &GetManager($sKey)
	{
		return $this->aManagers[$sKey];
	}

	public function SetManager($sKey, $oManager)
	{
		$this->aManagers[$sKey] = $oManager;
	}	
	/**
	 * @return CApiSettings
	 */
	public function &GetSettings()
	{
		if (null === $this->oSettings)
		{
			\Aurora\System\Api::Inc('settings');
			try
			{
				$sSettingsPath = \Aurora\System\Api::DataPath() . '/settings/';
				if (!file_exists($sSettingsPath))
				{
					mkdir(dirname($sSettingsPath), 0777);
				}
				
				$this->oSettings = new SystemSettings($sSettingsPath . 'config.json');
			}
			catch (BaseException $oException)
			{
				$this->oSettings = false;
			}
		}

		return $this->oSettings;
	}

	/**
	 * @param string $sManagerName
	 * @return string
	 */
	public function GetStorageByType($sManagerName)
	{
		$sManagerName = strtolower($sManagerName);
		return isset($this->aStorageMap[$sManagerName]) ? $this->aStorageMap[$sManagerName] : '';
	}

	/**
	 * @return CDbStorage
	 */
	public function &GetConnection()
	{
		if (null === $this->oConnection)
		{
			$oSettings =& $this->GetSettings();

			if ($oSettings)
			{
				$this->oConnection = new Db\Storage($oSettings);
			}
			else
			{
				$this->oConnection = false;
			}
		}

		return $this->oConnection;
	}

	/**
	 * @return CDbStorage
	 */
	public function &GetSqlHelper()
	{
		if (null === $this->oSqlHelper)
		{
			$oSettings =& $this->GetSettings();

			if ($oSettings)
			{
				$this->oSqlHelper = Db\Creator::CreateCommandCreatorHelper($oSettings);
			}
			else
			{
				$this->oSqlHelper = false;
			}
		}

		return $this->oSqlHelper;
	}

	/**
	 * @param bool $iMailProtocol
	 * @return CApiImap4MailProtocol
	 */
	public function GetSimpleMailProtocol($sHost, $iPort, $bUseSsl = false)
	{
		\Aurora\System\Api::Inc('net.protocols.imap4');
		return new \CApiImap4MailProtocol($sHost, $iPort, $bUseSsl);
	}

	public function &GetCommandCreator(\Aurora\System\AbstractManagerStorage &$oStorage, $aCommandCreatorsNames)
	{
		$oSettings =& $oStorage->GetSettings();
		$oCommandCreatorHelper =& $this->GetSqlHelper();

		$oCommandCreator = null;

		if ($oSettings)
		{
			$sDbType = $oSettings->GetConf('DBType');
			$sDbPrefix = $oSettings->GetConf('DBPrefix');

			if (isset($aCommandCreatorsNames[$sDbType]))
			{
				\Aurora\System\Api::Inc('db.command_creator');
				\Aurora\System\Api::StorageInc($oStorage->GetManagerName(), $oStorage->GetStorageName(), 'command_creator');

				$oCommandCreator =
					new $aCommandCreatorsNames[$sDbType]($oCommandCreatorHelper, $sDbPrefix);
			}
		}

		return $oCommandCreator;
	}

	/**
	 * @param string $sManagerType
	 * @param string $sForcedStorage = ''
	 */
	public function GetByType($sManagerType, $sForcedStorage = '')
	{
		$oResult = null;
		if (\Aurora\System\Api::IsValid())
		{
			$sManagerKey = empty($sForcedStorage) ? $sManagerType : $sManagerType.'/'.$sForcedStorage;
			if (isset($this->aManagers[$sManagerKey]))
			{
				$oResult =& $this->aManagers[$sManagerKey];
			}
			else
			{
				$sManagerType = strtolower($sManagerType);
				$sClassName = 'CApi'.ucfirst($sManagerType).'Manager';
				if (!class_exists($sClassName))
				{
					\Aurora\System\Api::Inc('managers.'.$sManagerType.'.manager', false);
				}
				if (class_exists($sClassName))
				{
					$oMan = new $sClassName($this, $sForcedStorage);
					$sCurrentStorageName = $oMan->GetStorageName();

					$sManagerKey = empty($sCurrentStorageName) ? $sManagerType : $sManagerType.'/'.$sCurrentStorageName;
					$this->aManagers[$sManagerKey] = $oMan;
					$oResult =& $this->aManagers[$sManagerKey];
				}
			}
		}

		return $oResult;
	}
}
