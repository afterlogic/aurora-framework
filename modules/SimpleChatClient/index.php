<?php

class SimpleChatModuleClient extends AApiModule
{
//	public $oApiHelpDeskManager = null;
	
//	public $oCoreDecorator = null;
	
	public function init()
	{
//		$this->oApiHelpDeskManager = $this->GetManager('main', 'db');

//		$this->AddEntry('chat', 'EntryPoint');
		
//		$this->oCoreDecorator = \CApi::GetModuleDecorator('Core');
//		$this->oCommonChatDecorator = \CApi::GetModuleDecorator('CommonChat');
	}
	
//	public function GetAppData($oUser = null)
//	{
//		return array(
//			'AllowEmailNotifications' => '', //AppData.User ? !!AppData.User.AllowHelpdeskNotifications : false,
//			'IsAgent' => '', //AppData.User ? !!AppData.User.IsHelpdeskAgent : false,
//			'UserEmail' => '', //AppData.User ? Types.pString(AppData.User.Email) : '',
//			'signature' => '', //ko.observable(AppData.User ? Types.pString(AppData.User.HelpdeskSignature) : ''),
//			'useSignature' => '', //ko.observable(AppData.User ? !!AppData.User.HelpdeskSignatureEnable : false),
//			'ActivatedEmail' => '',
//			'AfterThreadsReceivingAction' => '', //Types.pString(AppData.HelpdeskThreadAction), // add, close
//			'ClientDetailsUrl' => '', //Types.pString(AppData.HelpdeskIframeUrl),
//			'ClientSiteName' => '', //Types.pString(AppData.HelpdeskSiteName), // todo
//			'ForgotHash' => '', //Types.pString(AppData.HelpdeskForgotHash),
//			'LoginLogoUrl' => '', //Types.pString(AppData.HelpdeskStyleImage),
//			'SelectedThreadId' => 0, //Types.pInt(AppData.HelpdeskThreadId),
//			'SocialEmail' => '', //Types.pString(AppData.SocialEmail),
//			'SocialIsLoggedIn' => '', //!!AppData.SocialIsLoggedIn, // ???
//			'ThreadsPerPage' => 10 // add to settings
//		);
//	}
}

return new SimpleChatModuleClient('1.0');
