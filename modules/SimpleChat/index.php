<?php

class SimpleChatModule extends AApiModule
{
	public $oApiChatManager = null;
	
	public function init() 
	{
		$this->oApiChatManager = $this->GetManager('main', 'db');
		
		$this->setObjectMap('CUser', array(
				'EnableModule' => array('bool', true)
			)
		);
	}
	
	/**
	 * Obtains settings of the Simple Chat Module.
	 * 
	 * @param \CUser $oUser Object of the loggined user.
	 * @return array
	 */
	public function GetAppData($oUser = null)
	{
		return array(
			'EnableModule' => $oUser->{$this->GetName().'::EnableModule'}
		);
	}
	
	/**
	 * Updates settings of the Simple Chat Module.
	 * 
	 * @param boolean $EnableModule indicates if user turned on Simple Chat Module.
	 * @return boolean
	 */
	public function UpdateSettings($EnableModule)
	{
		$iUserId = \CApi::getLogginedUserId();
		if (0 < $iUserId)
		{
			$oCoreDecorator = \CApi::GetModuleDecorator('Core');
			$oUser = $oCoreDecorator->GetUser($iUserId);
			$oUser->{$this->GetName().'::EnableModule'} = $EnableModule;
			$oCoreDecorator->UpdateUserObject($oUser);
		}
		return true;
	}
	
	/**
	 * Obtains posts of Simple Chat Module.
	 * 
	 * @param int $Offset uses for obtaining a partial list.
	 * @param int $Limit uses for obtaining a partial list.
	 * @return array
	 */
	public function GetPosts($Offset, $Limit)
	{
		$iPostsCount = $this->oApiChatManager->GetPostsCount();
		$aPosts = $this->oApiChatManager->GetPosts($Offset, $Limit);
		return array(
			'Count' => $iPostsCount,
			'Offset' => $Offset,
			'Limit' => $Limit,
			'Collection' => $aPosts
		);
	}

	/**
	 * Creates a new post for loggined user.
	 * 
	 * @param string $Text text of the new post.
	 * @return boolean
	 */
	public function CreatePost($Text)
	{
		$iUserId = \CApi::getLogginedUserId();
		$this->oApiChatManager->CreatePost($iUserId, $Text);
		return true;
	}	
}

return new SimpleChatModule('1.0');