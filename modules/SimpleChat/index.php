<?php

class SimpleChatModule extends AApiModule
{
	public function init() 
	{
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
		return array(
			array('name' => 'test', 'text' => 'Hi there!'),
			array('name' => 'denis', 'text' => 'I miss you!')
		);
	}

	/**
	 * @param string $Message
	 * @return boolean
	 */
	public function PostMessage($Message)
	{
		return true;
	}	
}

return new SimpleChatModule('1.0');
