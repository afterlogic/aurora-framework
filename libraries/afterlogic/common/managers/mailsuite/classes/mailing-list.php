<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdMailingList
 * @property int $IdDomain
 * @property string $Name
 * @property string $Email
 * @property array $Members
 *
 * @package Mailsuite
 * @subpackage Classes
 */
class CMailingList extends api_AContainer
{
	/**
	 * @var CDomain
	 */
	protected $oDomain;

	/**
	 * @param CDomain $oDomain Default value is **null**.
	 */
	public function __construct($oDomain = null)
	{
		parent::__construct(get_class($this), 'IdMailingList');

		$this->oDomain = $oDomain;

		$this->SetDefaults(array(
			'IdMailingList'		=> 0,
			'IdDomain'			=> ($oDomain) ? $oDomain->IdDomain : 0,
			'Name'				=> '',
			'Email'				=> '',
			'Members'			=> array(),

			'_Login'			=> '',
			'_IsMailingList'	=> true,
			'_IsDefaultAccount'	=> true
		));
	}

	/**
	 * @param string $sLogin
	 * @param string $sAtChar Default value is **'@'**.
	 */
	public function initLoginAndEmail($sLogin, $sAtChar = '@')
	{
		$this->Email = '';

		$sLoginPart = api_Utils::GetAccountNameFromEmail($sLogin);
		$sDomainName = ($this->oDomain) ? $this->oDomain->Name : '';
		if (!empty($sDomainName))
		{
			$this->Email = $sLoginPart.$sAtChar.$sDomainName;
		}

		$this->_Login = $this->Email;
	}

	/**
	 * @return bool
	 */
	public function initBeforeChange()
	{
		$this->_Login = $this->Email;
		$this->_IsMailingList = true;
		$this->_IsDefaultAccount = true;
		return true;
	}

	/**
	 * @return CAccount
	 */
	public function generateAccount()
	{
		$this->initBeforeChange();

		CApi::Manager('users');

		$oAccount = new CAccount($this->oDomain);

		$oAccount->Email = $this->Email;
		$oAccount->FriendlyName = $this->Name;
		$oAccount->IncomingMailLogin = $this->_Login;
		$oAccount->IsDefaultAccount = $this->_IsDefaultAccount;
		$oAccount->IsMailingList = $this->_IsMailingList;

		return $oAccount;
	}

	/**
	 * @throws CApiValidationException(Errs::MailSuiteManager_MailingListInvalid) 1404
	 * @throws CApiValidationException(Errs::Validation_FieldIsEmpty) 1102
	 *
	 * @return bool
	 */
	public function validate()
	{
		$this->initBeforeChange();

		switch (true)
		{
			case ($this->IdDomain < 1):
				throw new CApiValidationException(Errs::MailSuiteManager_MailingListInvalid);

			case (api_Validate::IsEmpty($this->Email)):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CMailingList', '{{ClassField}}' => 'Email'));

			case (api_Validate::IsEmpty($this->_Login)):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CMailingList', '{{ClassField}}' => '_Login'));
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'IdMailingList'	=> array('int', 'id_acct', false),
			'IdDomain'		=> array('int', 'id_domain'),
			'Name'			=> array('string(255)', 'friendly_nm'),
			'Email'			=> array('string(255)', 'email'),
			'Members'		=> array('array'),

			'_Login'				=> array('string(255)', 'mail_inc_login'),
			'_IsMailingList'		=> array('bool', 'mailing_list'),
			'_IsDefaultAccount'		=> array('bool', 'def_acct'),
		);
	}
}
