'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
	_ = require('underscore'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Api = require('core/js/Api.js'),
	Ajax = require('core/js/Ajax.js'),
	Browser = require('core/js/Browser.js'),
	
	Settings = require('modules/Auth/js/Settings.js'),
	
	$html = $('html')
;

/**
 * @constructor
 */
function CLoginView()
{
	this.allowRegistration = Settings.AllowRegistration;
	this.allowPasswordReset = Settings.AllowPasswordReset;

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

	this.loginFormType = ko.observable(Settings.LoginFormType);
	this.loginAtDomainValue = ko.observable(Settings.LoginAtDomainValue);
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

	this.signMeType = ko.observable(Settings.LoginSignMeType);
	
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
		return this.loading() ? TextUtils.i18n('LOGIN/BUTTON_SIGNING_IN') : TextUtils.i18n('LOGIN/BUTTON_SIGN_IN');
	}, this);

	this.loginCommand = Utils.createCommand(this, this.signIn, this.canBeLogin);

	this.email(Settings.DemoWebMailLogin || '');
	this.password(Settings.DemoWebMailPassword || '');
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}

	this.shake = ko.observable(false).extend({'autoResetToFalse': 800});
}

CLoginView.prototype.ViewTemplate = 'Auth_LoginView';
CLoginView.prototype.__name = 'CLoginView';

CLoginView.prototype.onApplyBindings = function ()
{
	$html.addClass('non-adjustable-valign');
};

CLoginView.prototype.onShow = function ()
{
	this.fillFields();
};

CLoginView.prototype.fillFields = function ()
{
	_.delay(_.bind(function(){
		this.focusFields();
	},this), 1);
};

CLoginView.prototype.focusFields = function ()
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

CLoginView.prototype.signIn = function ()
{
	$('.check_autocomplete_input').trigger('input').trigger('change').trigger('keydown');

	var
		iLoginType = this.loginFormType(),
		sEmail = this.email(),
		sLogin = this.login(),
		sPassword = this.password()
	;

	if (!this.loading() && !this.changingLanguage() && '' !== $.trim(sPassword) && (
		(Enums.LoginFormType.Login === iLoginType && '' !== $.trim(sLogin)) ||
		(Enums.LoginFormType.Email === iLoginType && '' !== $.trim(sEmail)) ||
		(Enums.LoginFormType.Both === iLoginType && '' !== $.trim(sEmail))
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
CLoginView.prototype.onSystemLoginResponse = function (oResponse, oRequest)
{
	if (false === oResponse.Result)
	{
		this.loading(false);
		this.shake(true);
		
		Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/LOGIN_PASS_INCORRECT'));
	}
	else
	{
		if (window.location.search !== '' &&
			Utils.getRequestParam('reset-pass') === null &&
			Utils.getRequestParam('invite-auth') === null &&
			Utils.getRequestParam('external-services') === null)
		{
			Utils.clearAndReloadLocation(Browser.ie8AndBelow, true);
		}
		else
		{
			Utils.clearAndReloadLocation(Browser.ie8AndBelow, false);
		}
	}
};

CLoginView.prototype.sendRequest = function ()
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
	Ajax.send(oParameters, this.onSystemLoginResponse, this);
};

module.exports = CLoginView;