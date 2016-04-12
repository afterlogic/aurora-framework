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
class CChannel extends api_APropertyBag
{
	public function __construct($sModule)
	{
		parent::__construct(get_class($this), $sModule);

		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		self::$aStaticMap = array(
			'Login'			=> array('string', ''),
			'Password'		=> array('string', ''),
			'Description'	=> array('string', '')
		);
		
		$this->SetDefaults();
		
		//TODO
//		$this->SetLower(array('Login'));
	}
	
	public static function createInstance($sModule = 'Core')
	{
		return new CChannel($sModule);
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
}
