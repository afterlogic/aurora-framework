<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdTenant
 * @property int $IdUser
 * @property int $Created
 * @property string $Text
 *
 * @package SimpleChat
 * @subpackage Classes
 */
class CSimpleChatPost extends api_AContainer
{
	public function __construct($sModule)
	{
		parent::__construct(get_class($this), $sModule);

		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		$this->aStaticMap = array(
			'IdTenant'			=> array('int', 0),
			'IdUser'		=> array('int', 0),
			'Created'	=> array('string', ''), //time()
			'Text'	=> array('string', '')
		);
		
		$this->SetDefaults();
	}
	
	public static function createInstance($sModule = 'SimpleChat')
	{
		return new CSimpleChatPost($sModule);
	}

	/**
	 * @throws CApiValidationException 1106 Errs::Validation_ObjectNotComplete
	 *
	 * @return bool
	 */
	public function validate()
	{
		switch (true)
		{
			case 0 >= $this->IdUser:
				throw new CApiValidationException(Errs::Validation_ObjectNotComplete, null, array(
					'{{ClassName}}' => 'CSimpleChatPost', '{{ClassField}}' => 'IdIser'));
		}

		return true;
	}
}
