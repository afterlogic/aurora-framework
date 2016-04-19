<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 *
 * @package Users
 * @subpackage Classes
 */
class CMailAccount extends api_APropertyBag
{
	/**
	 * Creates a new instance of the object.
	 * 
	 * @return void
	 */
	public function __construct($sModule, $oParams)
	{
		var_dump($sModule);
		var_dump(get_class($this));
		parent::__construct(get_class($this), $sModule);
		
		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		$this->SetDefaults();

		CApi::Plugin()->RunHook('api-account-construct', array(&$this));

		self::$aStaticMap = array(
			'IsDisabled'			=> array('bool', false),
			'IdUser'				=> array('int', 0),
			'IsInternal'			=> array('bool', false),
			'IsDefaultAccount'		=> array('bool', false),//'def_acct'),
			'IsMailingList'			=> array('bool', false),//'mailing_list'),
			'StorageQuota'			=> array('int', 0),//'quota'),
			'StorageUsedSpace'		=> array('int', 0),
			'Email'					=> array('string(255)', ''),//'email', true, false),
			'FriendlyName'			=> array('string(255)', ''),//'friendly_nm'),
			'DetectSpecialFoldersWithXList' => array('bool', false),
			'IncomingMailProtocol'	=> array('int',  0),//'mail_protocol'),
			'IncomingMailServer'	=> array('string(255)', ''),//'mail_inc_host'),
			'IncomingMailPort'		=> array('int',  0),//'mail_inc_port'),
			'IncomingMailLogin'		=> array('string(255)', ''),//'mail_inc_login'),
			'IncomingMailPassword'	=> array('string(255)', ''),//'password', 'mail_inc_pass'),
			'IncomingMailUseSSL'	=> array('bool', false),//'mail_inc_ssl'),
			'PreviousMailPassword'	=> array('string', ''),
			'OutgoingMailServer'	=> array('string(255)', ''),//'mail_out_host'),
			'OutgoingMailPort'		=> array('int',  0),//'mail_out_port'),
			'OutgoingMailLogin'		=> array('string(255)', ''),//'mail_out_login'),
			'OutgoingMailPassword'	=> array('string(255)', ''),//'password', 'mail_out_pass'),
			'OutgoingMailAuth'		=> array('int',  0),//'mail_out_auth'),
			'OutgoingMailUseSSL'	=> array('bool', false),//'mail_out_ssl'),
			'OutgoingSendingMethod'	=> array('int', 0)
		);
		
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
}
