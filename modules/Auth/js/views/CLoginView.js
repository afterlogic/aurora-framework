'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	UrlUtils = require('modules/Core/js/utils/Url.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Api = require('modules/Core/js/Api.js'),
	Browser = require('modules/Core/js/Browser.js'),
	
	Ajax = require('modules/%ModuleName%/js/Ajax.js'),
	Settings = require('modules/%ModuleName%/js/Settings.js'),
	
	$html = $('html')
;

/**
 * @constructor
 */
function CLoginView()
{
	this.bAllowRegistration = Settings.AllowRegistration;
	this.bAllowResetPassword = Settings.AllowResetPassword;

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

	this.sLoginAtDomain = Settings.LoginAtDomain !== '' ? '@' + Settings.LoginAtDomain : '';

	this.emailVisible = ko.computed(function () {
		return Enums.LoginFormType.Login !== Settings.LoginFormType;
	}, this);
	
	this.loginVisible = ko.computed(function () {
		return Enums.LoginFormType.Email !== Settings.LoginFormType;
	}, this);

	this.bUseSignMe = (Settings.LoginSignMeType === Enums.LoginSignMeType.Unuse);
	this.signMe = ko.observable(Enums.LoginSignMeType.DefaultOn === Settings.LoginSignMeType);
	this.signMeFocused = ko.observable(false);

	this.emailDom = ko.observable(null);
	this.loginDom = ko.observable(null);
	this.passwordDom = ko.observable(null);

	this.focusedField = '';

	this.canBeLogin = ko.computed(function () {
		return !this.loading() && !this.changingLanguage();
	}, this);

	this.signInButtonText = ko.computed(function () {
		return this.loading() ? TextUtils.i18n('CORE/ACTION_SIGN_IN_IN_PROGRESS') : TextUtils.i18n('CORE/ACTION_SIGN_IN');
	}, this);

	this.loginCommand = Utils.createCommand(this, this.signIn, this.canBeLogin);

	this.email(Settings.DemoLogin || '');
	this.password(Settings.DemoPassword || '');
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}

	this.shake = ko.observable(false).extend({'autoResetToFalse': 800});
}

CLoginView.prototype.ViewTemplate = 'Auth_LoginView';
CLoginView.prototype.__name = 'CLoginView';

CLoginView.prototype.onBind = function ()
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
		sEmail = this.email(),
		sLogin = this.login(),
		sPassword = this.password()
	;

	if (!this.loading() && !this.changingLanguage() && '' !== $.trim(sPassword) && (
		(Enums.LoginFormType.Login === Settings.LoginFormType && '' !== $.trim(sLogin)) ||
		(Enums.LoginFormType.Email === Settings.LoginFormType && '' !== $.trim(sEmail)) ||
		(Enums.LoginFormType.Both === Settings.LoginFormType && '' !== $.trim(sEmail))
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
		
		Api.showErrorByCode(oResponse, TextUtils.i18n('CORE/ERROR_PASS_INCORRECT'));
	}
	else
	{
		$.cookie('AuthToken', oResponse.Result.AuthToken, { expires: 30 });
		
		if (window.location.search !== '' &&
			UrlUtils.getRequestParam('reset-pass') === null &&
			UrlUtils.getRequestParam('invite-auth') === null &&
			UrlUtils.getRequestParam('external-services') === null)
		{
			UrlUtils.clearAndReloadLocation(Browser.ie8AndBelow, true);
		}
		else
		{
			UrlUtils.clearAndReloadLocation(Browser.ie8AndBelow, false);
		}
	}
};

CLoginView.prototype.sendRequest = function ()
{
	var oParameters = {
		'Login': this.emailVisible() ? this.email() : this.loginVisible() ? this.login() : '',
		'Password': this.password(),
		'SignMe': this.signMe() ? '1' : '0'
	};

	this.loading(true);
	
	Ajax.send('Login', oParameters, this.onSystemLoginResponse, this);
};

module.exports = CLoginView;
