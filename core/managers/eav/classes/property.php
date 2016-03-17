<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $Id
 * @property string $ObjectId
 * @property string $Name
 * @property string $Value
 *
 * @package EAV
 * @subpackage Classes
 */
class CProperty extends api_AContainer
{
	public function __construct($sName, $sValue)
	{
		parent::__construct(get_class($this), 'Id');

		$this->__USE_TRIM_IN_STRINGS__ = true;

		$this->SetDefaults(array(
			'Id'			=> 0,
			'ObjectId'		=> '',
			'Name'			=> $sName,
			'Value'			=> $sValue
		));
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
			case api_Validate::IsEmpty($this->ObjectId):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CProperty', '{{ClassField}}' => 'ObjectId'));
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
			'Id'		=> array('int', 'id', false, false),
			'ObjectId'	=> array('string(255)', 'id_object'),
			'Name'		=> array('string(255)', 'key'),
			'Value'		=> array('string(255)', 'value'),
		);
	}
}
