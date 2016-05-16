<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 *
 * @package Users
 * @subpackage Classes
 */
class CMailAccount extends api_APropertyBag
{
	const ChangePasswordExtension = 'AllowChangePasswordExtension';
	const AutoresponderExtension = 'AllowAutoresponderExtension';
	const SpamFolderExtension = 'AllowSpamFolderExtension';
	const DisableAccountDeletion = 'DisableAccountDeletion';
	const DisableManageFolders = 'DisableManageFolders';
	const SieveFiltersExtension = 'AllowSieveFiltersExtension';
	const ForwardExtension = 'AllowForwardExtension';
	const DisableManageSubscribe = 'DisableManageSubscribe';
	const DisableFoldersManualSort = 'DisableFoldersManualSort';
	const IgnoreSubscribeStatus = 'IgnoreSubscribeStatus';
	
	/**
	 * Creates a new instance of the object.
	 * 
	 * @return void
	 */
	public function __construct($sModule, $oParams)
	{
		parent::__construct(get_class($this), $sModule);
		
		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		CApi::Plugin()->RunHook('api-account-construct', array(&$this));

		$this->aStaticMap = array(
			'IsDisabled'			=> array('bool', false),
			'IdUser'				=> array('int', 0),
			'IsInternal'			=> array('bool', false),
			'IsDefaultAccount'		=> array('bool', false),//'def_acct'),
			'IsMailingList'			=> array('bool', false),//'mailing_list'),
			'StorageQuota'			=> array('int', 0),//'quota'),
			'StorageUsedSpace'		=> array('int', 0),
			'Email'					=> array('string', ''),//'email', true, false),
			'FriendlyName'			=> array('string', ''),//'friendly_nm'),
			'DetectSpecialFoldersWithXList' => array('bool', false),
			'IncomingMailProtocol'	=> array('int',  EMailProtocol::IMAP4),//'mail_protocol'),
			'IncomingMailServer'	=> array('string', ''),//'mail_inc_host'),
			'IncomingMailPort'		=> array('int',  143),//'mail_inc_port'),
			'IncomingMailLogin'		=> array('string', ''),//'mail_inc_login'),
			'IncomingMailPassword'	=> array('string', ''),//'password', 'mail_inc_pass'),
			'IncomingMailUseSSL'	=> array('bool', false),//'mail_inc_ssl'),
			'PreviousMailPassword'	=> array('string', ''),
			'OutgoingMailServer'	=> array('string', ''),//'mail_out_host'),
			'OutgoingMailPort'		=> array('int',  25),//'mail_out_port'),
			'OutgoingMailLogin'		=> array('string', ''),//'mail_out_login'),
			'OutgoingMailPassword'	=> array('string', ''),//'password', 'mail_out_pass'),
			'OutgoingMailAuth'		=> array('int',  ESMTPAuthType::NoAuth),//'mail_out_auth'),
			'OutgoingMailUseSSL'	=> array('bool', false),//'mail_out_ssl'),
			'OutgoingSendingMethod'	=> array('int', ESendingMethod::Specified)
		);
		
		$this->SetDefaults();
	}

	/**
	 * Checks if the user has only valid data.
	 * 
	 * @return bool
	 */
	public function isValid()
	{
		switch (true)
		{
			case false:
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CUser', '{{ClassField}}' => 'Error'));
		}

		return true;
	}
	
	public static function createInstance($sModule = 'Mail', $oParams = array())
	{
		return new CMailAccount($sModule, $oParams);
	}
	
	public function isExtensionEnabled($sExtention)
	{
		return $sExtention === CMailAccount::DisableFoldersManualSort;
	}
	
	public function getDefaultTimeOffset()
	{
		return 0;
	}
}
