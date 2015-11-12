<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdChannel
 * @property string $Login
 * @property string $Password
 * @property string $Description
 *
 * @package Channels
 * @subpackage Classes
 */
class CChannel extends api_AContainer
{
	public function __construct()
	{
		parent::__construct(get_class($this), 'IdChannel');

		$this->__USE_TRIM_IN_STRINGS__ = true;

		$this->SetDefaults(array(
			'IdChannel'		=> 0,
			'Login'			=> '',
			'Password'		=> '',
			'Description'	=> ''
		));

		$this->SetLower(array('Login'));
	}

	/**
	 * @throws CApiValidationException
	 *
	 * @return bool
	 */
	public function validate()
	{
		switch (true)
		{
			case !api_Validate::IsValidChannelLogin($this->Login):
				throw new CApiValidationException(Errs::Validation_InvalidTenantName);
			case api_Validate::IsEmpty($this->Login):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CChannel', '{{ClassField}}' => 'Login'));
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
			'IdChannel'		=> array('int', 'id_channel', false, false),
			'Login'			=> array('string(255)', 'login', true, false),
			'Password'		=> array('string(100)', 'password', true, false),
			'Description'	=> array('string(255)', 'description')
		);
	}
}
