<?php

class SimpleChatModule extends AApiModule
{
	public $oApiChatManager = null;
	
	public function init() 
	{
		$this->oApiChatManager = $this->GetManager('main', 'db');
		
		$this->setObjectMap('CUser', array(
				'AllowSimpleChat' => array('bool', true)
			)
		);
	}
	
	public function GetAppData($oUser = null)
	{
		return array(
			'AllowModule' => $oUser->{$this->GetName().'::AllowSimpleChat'}
		);
	}
	
	public function UpdateSettings($AllowModule)
	{
		$iUserId = \CApi::getLogginedUserId();
		if (0 < $iUserId)
		{
			$oCoreDecorator = \CApi::GetModuleDecorator('Core');
			$oUser = $oCoreDecorator->GetUser($iUserId);
			$oUser->{$this->GetName().'::AllowSimpleChat'} = $AllowModule;
			$oCoreDecorator->UpdateUserObject($oUser);
		}
		return true;
	}
	
	/**
	 * @return array
	 */
	public function GetMessages()
	{
		return $this->oApiChatManager->GetMessages();
	}

	/**
	 * @param string $Message
	 * @return boolean
	 */
	public function PostMessage($Message)
	{
		$iUserId = \CApi::getLogginedUserId();
		$this->oApiChatManager->PostMessage($iUserId, $Message);
		return true;
	}	
}

return new SimpleChatModule('1.0');
