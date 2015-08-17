CScreens.prototype.initScreens = function ()
{
	this.oScreens[Enums.Screens.Information] = {
		'Model': CInformationViewModel,
		'TemplateName': 'Common_InformationViewModel'
	};
	this.oScreens[Enums.Screens.Login] = {
		'Model': CHelpdeskLoginViewModel,
		'TemplateName': 'Helpdesk_Login'
	};
	this.oScreens[Enums.Screens.Header] = {
		'Model': CHelpdeskHeaderViewModel,
		'TemplateName': 'Helpdesk_Header'
	};
	this.oScreens[Enums.Screens.Helpdesk] = {
		'Model': CHelpdeskViewModel,
		'TemplateName': 'Helpdesk_HelpdeskViewModel'
	};
	this.oScreens[Enums.Screens.Settings] = {
		'Model': CHelpdeskSettingsViewModel,
		'TemplateName': 'Helpdesk_SettingsExt'
	};
};

CScreens.prototype.initLayout = function ()
{
	$('#pSevenContent').append($('#HelpdeskLayout').html());
};
