<?php

class SimpleChatLoggerModule extends AApiModule
{
	public function init()
	{
		$this->subscribeEvent('CreatePost::after', array($this, 'afterCreatePost'));
	}
	
	public function afterCreatePost($aArgs)
	{
		$iUserId = \CApi::getLogginedUserId();
		\CApi::Log($iUserId.' ['.$aArgs['Date'].'] '.$aArgs['Text'], ELogLevel::Full, 'simple-chat');
	}
}

return new SimpleChatLoggerModule('1.0');
