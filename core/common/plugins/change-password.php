<?php

/* -AFTERLOGIC LICENSE HEADER- */

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
