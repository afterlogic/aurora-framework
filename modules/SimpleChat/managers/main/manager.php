<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiSimpleChatMainManager class summary
 *
 * @package SimpleChat
 */

class CApiSimpleChatMainManager extends AApiManager
{
	/**
	 * @var CApiEavManager
	 */
	public $oEavManager = null;
	
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
	{
		parent::__construct('main', $oManager, $oModule);
		
		$this->oEavManager = \CApi::GetCoreManager('eav', 'db');

		$this->incClass('post');
	}
	
	public function GetMessages()
	{
		$aResult = array();
		try
		{
			$aResults = $this->oEavManager->getObjects(
				'CSimpleChatPost', 
				array(
					'IdUser', 'Message'
				),
				0,
				0,
				array()
			);

			if (is_array($aResults))
			{
				$oCoreDecorator = \CApi::GetModuleDecorator('Core');
				foreach($aResults as $oItem)
				{
					$oUser = $oCoreDecorator->GetUser($oItem->IdUser);
					$aResult[] = array(
						'name' => $oUser->Name,
						'text' => $oItem->Message
					);
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$aResult = false;
			$this->setLastException($oException);
		}
		return $aResult;
	}
	
	public function PostMessage($iUserId, $sMessage)
	{
		$bResult = true;
		try
		{
			$oNewPost = new \CSimpleChatPost($this->GetModule()->GetName());
			$oNewPost->IdUser = $iUserId;
			$oNewPost->Message = $sMessage;
			if (!$this->oEavManager->saveObject($oNewPost))
			{
				throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
			}
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		return $bResult;
	}
}
