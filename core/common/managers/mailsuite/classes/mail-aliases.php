<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdAccount
 * @property string $Email
 * @property array $Aliases
 *
 * @package Mailsuite
 * @subpackage Classes
 */
class CMailAliases extends api_AContainer
{

	/**
	 * @param CAccount $oAccount
	 */
	public function __construct($oAccount)
	{
		parent::__construct(get_class($this), 'IdAccount');

		$this->SetDefaults(array(
			'IdAccount'	=> $oAccount->IdAccount,
			'Email'		=> $oAccount->Email,
			'Aliases'	=> array()
		));
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
			'IdAccount'	=> array('int', 'id_acct', false),
			'Email'		=> array('string(255)', 'email'),
			'Aliases'	=> array('array')
		);
	}
}
