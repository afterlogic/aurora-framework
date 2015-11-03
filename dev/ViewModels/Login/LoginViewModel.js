
/**
 * @constructor
 */
function CLoginViewModel()
{
	this.allowRegistration = AppData.App.AllowRegistration;
	this.allowPasswordReset = AppData.App.AllowPasswordReset;

	this.email = ko.observable('');
	this.login = ko.observable('');
	this.password = ko.observable('');
	
	this.emailFocus = ko.observable(false);
	this.loginFocus = ko.observable(false);
	this.passwordFocus = ko.observable(false);

	this.loading = ko.observable(false);
	this.changingLanguage = ko.observable(false);

	this.loginFocus.subscribe(function (bFocus) {
		if (bFocus && '' === this.login()) {
			this.login(this.email());
		}
	}, this);

	this.loginFormType = ko.observable(AppData.App.LoginFormType);
	this.loginAtDomainValue = ko.observable(AppData.App.LoginAtDomainValue);
	this.loginAtDomainValueWithAt = ko.computed(function () {
		var sV = this.loginAtDomainValue();
		return '' === sV ? '' : '@' + sV;
	}, this);

	this.emailVisible = ko.computed(function () {
		return Enums.LoginFormType.Login !== this.loginFormType();
	}, this);
	
	this.loginVisible = ko.computed(function () {
		return Enums.LoginFormType.Email !== this.loginFormType();
	}, this);

	this.signMeType = ko.observable(AppData.App.LoginSignMeType);
	
	this.signMe = ko.observable(Enums.LoginSignMeType.DefaultOn === this.signMeType());
	this.signMeType.subscribe(function () {
		this.signMe(Enums.LoginSignMeType.DefaultOn === this.signMeType());
	}, this);
	this.signMeFocused = ko.observable(false);

	this.emailDom = ko.observable(null);
	this.loginDom = ko.observable(null);
	this.passwordDom = ko.observable(null);

	this.focusedField = '';

	this.canBeLogin = ko.computed(function () {
		return !this.loading() && !this.changingLanguage();
	}, this);

	this.signInButtonText = ko.computed(function () {
		return this.loading() ? Utils.i18n('LOGIN/BUTTON_SIGNING_IN') : Utils.i18n('LOGIN/BUTTON_SIGN_IN');
	}, this);

	this.loginCommand = Utils.createCommand(this, this.signIn, this.canBeLogin);

	this.email(AppData.App.DemoWebMailLogin || '');
	this.password(AppData.App.DemoWebMailPassword || '');
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}

	this.shake = ko.observable(false).extend({'autoResetToFalse': 800});
}

CLoginViewModel.prototype.__name = 'CLoginViewModel';

CLoginViewModel.prototype.onApplyBindings = function ()
{
	$html.addClass('non-adjustable-valign');
};

CLoginViewModel.prototype.onShow = function ()
{
	this.fillFields();
};

CLoginViewModel.prototype.fillFields = function ()
{
	_.delay(_.bind(function(){
		this.focusFields();
	},this), 1);
};

CLoginViewModel.prototype.focusFields = function ()
{
	if (this.emailVisible() && this.email() === '')
	{
		this.emailFocus(true);
	}
	else if (this.loginVisible() && this.login() === '')
	{
		this.loginFocus(true);
	}
};

CLoginViewModel.prototype.signIn = function ()
{
	$('.check_autocomplete_input').trigger('input').trigger('change').trigger('keydown');

	var
		iLoginType = this.loginFormType(),
		sEmail = this.email(),
		sLogin = this.login(),
		sPassword = this.password()
	;

	if (!this.loading() && !this.changingLanguage() && '' !== Utils.trim(sPassword) && (
		(Enums.LoginFormType.Login === iLoginType && '' !== Utils.trim(sLogin)) ||
		(Enums.LoginFormType.Email === iLoginType && '' !== Utils.trim(sEmail)) ||
		(Enums.LoginFormType.Both === iLoginType && '' !== Utils.trim(sEmail))
	))
	{
		this.sendRequest();
	}
	else
	{
		this.shake(true);
	}
};

/**
 * Receives data from the server. Shows error and shakes form if server has returned false-result.
 * Otherwise clears search-string if it don't contain "reset-pass", "invite-auth" and "external-services" parameters and reloads page.
 * 
 * @param {Object} oResponse Data obtained from the server.
 * @param {Object} oRequest Data has been transferred to the server.
 */
CLoginViewModel.prototype.onSystemLoginResponse = function (oResponse, oRequest)
{
	if (false === oResponse.Result)
	{
		this.loading(false);
		this.shake(true);
		
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/LOGIN_PASS_INCORRECT'));
	}
	else
	{
		App.Storage.setData('AuthToken', oResponse.Result.AuthToken);
		
		if (window.location.search !== '' &&
			Utils.Common.getRequestParam('reset-pass') === null &&
			Utils.Common.getRequestParam('invite-auth') === null &&
			Utils.Common.getRequestParam('external-services') === null)
		{
			Utils.Common.clearAndReloadLocation(App.browser.ie8AndBelow, true);
		}
		else
		{
			Utils.Common.clearAndReloadLocation(App.browser.ie8AndBelow, false);
		}
	}
};

CLoginViewModel.prototype.sendRequest = function ()
{
	var
		oParameters = {
			'Action': 'SystemLogin',
			'Email': this.emailVisible() ? this.email() : '',
			'IncLogin': this.loginVisible() ? this.login() : '',
			'IncPassword': this.password(),
			'SignMe': this.signMe() ? '1' : '0'
		}
	;

	this.loading(true);
	App.Ajax.send(oParameters, this.onSystemLoginResponse, this);
};