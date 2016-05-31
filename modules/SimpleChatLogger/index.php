<?php

class SimpleChatLoggerModule extends AApiModule
{
	/**
	 * Subscribes on the event that is broadcasted after executing of the CreatePost method in the SimpleChat module.
	 */
	public function init()
	{
		$this->subscribeEvent('SimpleChat::CreatePost::after', array($this, 'afterCreatePost'));
	}
	
	/**
	 * Logs data of the user post in the simple chat.
	 * 
	 * @param array $aArgs Array with data of the user post in the simple chat.
	 *  ['Date'] - string Date and time when user put down the post in UTC.
	 *  ['Text'] - string Text of the user post.
	 */
	public function afterCreatePost($aArgs)
	{
		$iUserId = \CApi::getLogginedUserId();
		\CApi::Log($iUserId.' ['.$aArgs['Date'].'] '.$aArgs['Text'], ELogLevel::Full, 'simple-chat');
	}
}