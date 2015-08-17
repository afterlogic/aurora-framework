
/**
 * @constructor
 */
function CWrapLoginViewModel()
{
	this.bSocialInviteMode = typeof Utils.Common.getRequestParam('invite-auth') === 'string';
	this.socialInviteTitle = Utils.i18n('LOGIN/SOCIAL_INVITE_TITLE', {'SITENAME': AppData.App.SiteName});
	this.socialInviteText = Utils.i18n('LOGIN/SOCIAL_INVITE_TEXT');
	
	this.rtl = ko.observable(Utils.isRTL());
	
	this.allowRegistration = AppData.App.AllowRegistration;
	this.allowPasswordReset = AppData.App.AllowPasswordReset;
	
	this.oLoginViewModel = new CLoginViewModel();
	if (this.allowRegistration)
	{
		this.oRegisterViewModel = new CRegisterViewModel();
	}
	if (this.allowPasswordReset)
	{
		this.oForgotViewModel = new CForgotViewModel();
	}
	this.gotoForgot = this.allowPasswordReset ? this.oForgotViewModel.gotoForgot : ko.observable(false);
	this.gotoRegister = ko.observable(false);

	this.emailVisible = this.oLoginViewModel.emailVisible;
	this.loginVisible = this.oLoginViewModel.loginVisible;
	this.loginDescription = ko.observable(AppData.App.LoginDescription || '');

	this.aLanguages = AppData.App.Languages;
	this.currentLanguage = ko.observable(AppData.App.DefaultLanguage);
	
	this.allowLanguages = ko.observable(AppData.App.AllowLanguageOnLogin);
	this.viewLanguagesAsDropdown = ko.observable(!AppData.App.FlagsLangSelect);

	this.loginCustomLogo = ko.observable(AppData['LoginStyleImage'] || '');
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CWrapLoginViewModel.prototype.__name = 'CWrapLoginViewModel';

CWrapLoginViewModel.prototype.onShow = function ()
{
	if (this.oLoginViewModel.onShow)
	{
		this.oLoginViewModel.onShow();
	}
};

CWrapLoginViewModel.prototype.onApplyBindings = function ()
{
	if (this.oLoginViewModel.onApplyBindings)
	{
		this.oLoginViewModel.onApplyBindings();
	}
};

/**
 * @param {string} sLanguage
 */
CWrapLoginViewModel.prototype.changeLanguage = function (sLanguage)
{
	if (sLanguage && this.allowLanguages())
	{
		this.currentLanguage(sLanguage);
		this.oLoginViewModel.changingLanguage(true);

		App.Ajax.send({
			'Action': 'SystemUpdateLanguageOnLogin',
			'Language': sLanguage
		}, function () {
			window.location.reload();
		}, this);
	}
};
