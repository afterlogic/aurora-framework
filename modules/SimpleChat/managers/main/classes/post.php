<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdUser
 * @property string $Message
 *
 * @package SimpleChat
 * @subpackage Classes
 */
class CSimpleChatPost extends api_APropertyBag
{
	public function __construct($sModule)
	{
		parent::__construct(get_class($this), $sModule);

		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		$this->aStaticMap = array(
			'IdUser'	=> array('int', 0),
			'Message'	=> array('text', '')
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
