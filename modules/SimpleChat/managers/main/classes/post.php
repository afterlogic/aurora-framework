<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $UserId
 * @property string $Text
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
			'UserId'	=> array('int', 0),
			'Text'		=> array('text', ''),
			'Date'		=> array('datetime', '')
		);
		
		$this->SetDefaults();
	}
	
	public static function createInstance($sModule = 'SimpleChat')
	{
		return new CSimpleChatPost($sModule);
	}
}
