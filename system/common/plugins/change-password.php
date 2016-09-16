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
 * @package Api
 */
abstract class AApiChangePasswordPlugin extends AApiPlugin
{
	/**
	 * @param string $sVersion
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct($sVersion, CApiPluginManager $oPluginManager)
	{
		parent::__construct($sVersion, $oPluginManager);

		$this->AddHook('api-change-account-by-id', 'PluginChangeAccountById');
		$this->AddHook('api-update-account', 'PluginUpdateAccount');
	}

	abstract protected function validateIfAccountCanChangePassword($oAccount);

	abstract public function ChangePasswordProcess($oAccount);

	/**
	 * @param CAccount $oAccount
	 * @param bool $bUseOnlyHookUpdate
	 */
	public function PluginUpdateAccount(&$oAccount, &$bUseOnlyHookUpdate)
	{
		if ($this->validateIfAccountCanChangePassword($oAccount) && $oAccount->isExtensionEnabled(CAccount::ChangePasswordExtension))
		{
			$this->ChangePasswordProcess($oAccount);
		}
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function PluginChangeAccountById(&$oAccount)
	{
		if ($this->validateIfAccountCanChangePassword($oAccount))
		{
			if ($oAccount)
			{
				$oAccount->enableExtension(CAccount::ChangePasswordExtension);
			}
		}
	}
}
