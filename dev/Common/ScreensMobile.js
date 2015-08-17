CScreens.prototype.initScreens = function ()
{
	this.oScreens[Enums.Screens.Information] = {
		'Model': CInformationViewModel,
		'TemplateName': 'Common_InformationViewModel'
	};
	this.oScreens[Enums.Screens.Login] = {
		'Model': CWrapLoginViewModel,
		'TemplateName': 'Login_WrapLoginViewModel'
	};
	this.oScreens[Enums.Screens.Header] = {
		'Model': CHeaderMobileViewModel,
		'TemplateName': 'Common_HeaderMobileViewModel'
	};
	this.oScreens[Enums.Screens.Mailbox] = {
		'Model': CMailViewModel,
		'TemplateName': 'Mail_LayoutSidePane_MailViewModel'
	};
	this.oScreens[Enums.Screens.SingleMessageView] = {
		'Model': CMessagePaneViewModel,
		'TemplateName': 'Mail_LayoutSidePane_MessagePaneViewModel'
	};
	this.oScreens[Enums.Screens.Compose] = {
		'Model': CComposeViewModel,
		'TemplateName': 'Mail_ComposeViewModel'
	};
//	this.oScreens[Enums.Screens.SingleCompose] = {
//		'Model': CComposeViewModel,
//		'TemplateName': 'Mail_ComposeViewModel'
//	};
//	this.oScreens[Enums.Screens.Settings] = {
//		'Model': CSettingsViewModel,
//		'TemplateName': 'Settings_SettingsViewModel'
//	};
//	this.oScreens[Enums.Screens.SingleHelpdesk] = {
//		'Model': CHelpdeskViewModel,
//		'TemplateName': 'Helpdesk_ViewThreadInNewWindow'
//	};
};

CScreens.prototype.initLayout = function ()
{
	$('#pSevenContent').append($('#Layout').html());
};