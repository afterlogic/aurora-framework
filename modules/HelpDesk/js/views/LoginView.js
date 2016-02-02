'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	Ajax = require('modules/HelpDesk/js/Ajax.js'),
	Storage = require('core/js/Storage.js'),
	UserSettings = require('core/js/Settings.js'),
	
	Settings = require('modules/HelpDesk/js/Settings.js')
;

/**
 * @constructor
 */
function CLoginView()
{
	this.emailFocus = ko.observable(false);
	this.email = ko.observable('');
	
	this.passwordFocus = ko.observable(false);
	this.password = ko.observable('');
	
	this.signMeType = ko.observable(true);
	this.signMe = ko.observable(true);
	
	this.loginProcess = ko.observable(false);

	this.sLogoUrl = Settings.LoginLogoUrl;
	
	this.activationDescription = ko.observable('');
	
	this.registeringProcess = ko.observable(false);

	this.regNameFocus = ko.observable(false);
	this.regName = ko.observable('');
	this.regEmailFocus = ko.observable(false);
	this.regSocialEmailFocus = ko.observable(false);
	this.regEmail = ko.observable('');
	this.regSocialEmail = ko.observable('');
	this.regPasswordFocus = ko.observable(false);
	this.regPassword = ko.observable('');
	this.regConfirmPasswordFocus = ko.observable(false);
	this.regConfirmPassword = ko.observable('');
	this.helpdeskQuestion = ko.observable('');
	this.helpdeskQuestion.subscribe(function(sText) {
		if(!sText)
		{
			Storage.setData('helpdeskQuestion');
		}
	}, this);
	this.helpdeskQuestionFocus = ko.observable('');

	this.signInButtonText = ko.computed( function () {
		if(this.registeringProcess())
		{
			return TextUtils.i18n('LOGIN/BUTTON_SIGNING_IN');
		}
		else
		{
			return TextUtils.i18n('LOGIN/BUTTON_SIGN_IN');
		}
	}, this);

	this.regButtonText = ko.computed( function () {
		if(this.registeringProcess())
		{
			return TextUtils.i18n('HELPDESK/BUTTON_REGISTERING');
		}
		else
		{
			return TextUtils.i18n('HELPDESK/BUTTON_REGISTER');
		}
	}, this);

	this.sendingPasswordProcess = ko.observable(false);
	this.forgotButtonText = ko.computed(function () {
		return this.sendingPasswordProcess() ?
			TextUtils.i18n('MAIN/BUTTON_SENDING') :
			TextUtils.i18n('HELPDESK/BUTTON_SEND_PASSWORD');
	}, this);
	this.forgotEmailFocus = ko.observable(false);
	this.forgotEmail = ko.observable('');
	
	this.changingPasswordProcess = ko.observable(false);
	this.changepassButtonText = ko.computed(function () {
		return this.changingPasswordProcess() ?
			TextUtils.i18n('HELPDESK/BUTTON_CHANGING_PASS') :
			TextUtils.i18n('HELPDESK/BUTTON_CHANGE_PASS');
	}, this);
	this.changepassNewpassFocus = ko.observable(false);
	this.changepassNewpass = ko.observable('');
	this.changepassConfirmpassFocus = ko.observable(false);
	this.changepassConfirmpass = ko.observable('');
	
	this.gotoForgot = ko.observable(false);
	this.gotoRegister = ko.observable(false);
	this.gotoSignin = ko.observable(false);
	this.gotoSocialRegister = ko.observable(false);
	this.gotoChangepass = ko.observable(Settings.ForgotHash ? true : false);

	this.bAllowFacebookAuth = Settings.AllowFacebookAuth;
	this.sAllowGoogleAuth = Settings.AllowGoogleAuth;
	this.sAllowTwitterAuth = Settings.AllowTwitterAuth;

	this.shake = ko.observable(false).extend({'autoResetToFalse': 800});

	this.loginCommand = Utils.createCommand(this, this.actionLogin, function () {
		return !this.loginProcess();
	});
	this.sendCommand = Utils.createCommand(this, this.actionSend, this.helpdeskQuestion);
	this.registerCommand = Utils.createCommand(this, this.actionRegister, function () {
		return !this.registeringProcess() && $.trim(this.regPassword()) !== '' && $.trim(this.regConfirmPassword()) !== ''  && $.trim(this.regEmail()) !== '' ;
	});
	this.forgotCommand = Utils.createCommand(this, this.actionForgot, function () {
		return !this.sendingPasswordProcess() && $.trim(this.forgotEmail()) !== '';
	});

	this.socialNetworkLogin();

//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CLoginView.prototype.__name = 'CLoginView';

CLoginView.prototype.onShow = function ()
{
	var sReportText = Storage.getData('ReportText');
	
	if (sReportText)
	{
		Utils.log('CLoginView', sReportText);
		Screens.showReport(sReportText);
		
		Storage.removeData('ReportText');
	}

	if (Storage.getData('helpdeskQuestion'))
	{
		this.helpdeskQuestion(Storage.getData('helpdeskQuestion'));
	}

	$html.addClass('non-adjustable-valign');
};

CLoginView.prototype.onHide = function ()
{
	this.loginProcess(false);
	this.registeringProcess(false);
	this.sendingPasswordProcess(false);
	this.changingPasswordProcess(false);
	
	this.email('');
	this.password('');
	this.regEmail('');
	this.regSocialEmail('');
	this.regName('');
	this.regPassword('');
	this.regConfirmPassword('');
	this.forgotEmail('');
	this.changepassNewpass('');
	this.changepassConfirmpass('');
	this.helpdeskQuestion('');

	this.gotoForgot(false);
	this.gotoRegister(false);
	this.gotoSignin(false);
	this.gotoSocialRegister(false);
	this.gotoChangepass(false);

};

CLoginView.prototype.socialNetworkLogin = function ()
{
	this.regSocialEmail(Settings.SocialEmail);

	if (Settings.SocialIsLoggedIn)
	{
		this.gotoSocialRegister(true);
	}
};

CLoginView.prototype.onSocialClick = function (sSocial)
{
	this.storeQuestion();
	$.cookie('external-services-redirect', 'helpdesk');
	if (window !== window.top)
	{
		var
			x = screen.width/2 - 700/2,
			y = screen.height/2 - 600/2
		;

		window.open(Utils.getAppPath() + '?external-services=' + sSocial + '&scopes=login', sSocial, 'location=no,toolbar=no,status=no,scrollbars=yes,resizable=yes,menubar=no,width=700,height=600,left=' + x + ',top=' + y);
	}
	else
	{
		window.location.href = '?external-services=' + sSocial + '&scopes=login';
	}
};

CLoginView.prototype.storeQuestion = function ()
{
	if(this.helpdeskQuestion() !== '')
	{
		Storage.setData('helpdeskQuestion', this.helpdeskQuestion());
	}
};

CLoginView.prototype.actionSend = function ()
{
	this.storeQuestion();
	this.gotoRegister(true);
};

CLoginView.prototype.actionLogin = function ()
{
	$('.check_autocomplete_input').trigger('input').trigger('change').trigger('keydown');

	if ($.trim(this.password()) && '' !== $.trim(this.email()))
	{
		this.storeQuestion();

		this.loginProcess(true);

		Ajax.send('Login', {
			'Email': this.email(),
			'Password': this.password(),
			'SignMe': this.signMe() ? '1' : '0'
		}, this.onHelpdeskLoginResponse, this);
	}
	else
	{
		this.shake(true);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CLoginView.prototype.onHelpdeskLoginResponse = function (oResponse, oRequest)
{
	this.loginProcess(false);
	
	if (oResponse.Result)
	{
		window.location.reload();
	}
	else
	{
		if (oResponse.ErrorCode === Enums.Errors.HelpdeskThrowInWebmail)
		{
			window.location.href = '';
		}
		else
		{
			if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
			{
				oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
			}

			Api.showErrorByCode(oResponse, TextUtils.i18n('HELPDESK/ERROR_LOGIN_FAILED'));

			this.shake(true);
			this.emailFocus(true);
		}
	}
};

CLoginView.prototype.actionRegister = function ()
{
	if (this.regPassword() !== this.regConfirmPassword())
	{
		Screens.showError(TextUtils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
		this.regPasswordFocus(true);
	}
	else
	{
		this.registeringProcess(true);

		Ajax.send('Register', {
			'Email': this.regEmail(),
			'Password': this.regPassword(),
			'Name': this.regName()
		}, this.onHelpdeskRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CLoginView.prototype.onHelpdeskRegisterResponse = function (oResponse, oRequest)
{	
	this.registeringProcess(false);

	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		Api.showErrorByCode(oResponse, TextUtils.i18n('HELPDESK/ERROR_REGISTRATION_FAILED'));

		this.regEmailFocus(true);
	}
	else
	{
		Utils.log('CLoginView', TextUtils.i18n('HELPDESK/ACTIVATION_DESCRIPTION'));
		Screens.showReport(TextUtils.i18n('HELPDESK/ACTIVATION_DESCRIPTION', {
			'EMAIL': this.regEmail()
		}));

		this.gotoRegister(false);
	}
};

CLoginView.prototype.actionSocialRegister = function ()
{
	if (!this.registeringProcess())
	{
		if (this.regSocialEmail() === '')
		{
			this.regSocialEmailFocus(true);
			return;
		}

		this.registeringProcess(true);

		Ajax.send('RegisterSocial', { 'NotificationEmail': this.regSocialEmail() }, this.onHelpdeskSocialRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CLoginView.prototype.onHelpdeskSocialRegisterResponse = function (oResponse, oRequest)
{
	this.registeringProcess(false);

	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		Api.showErrorByCode(oResponse, TextUtils.i18n('HELPDESK/ERROR_REGISTRATION_FAILED'));

		this.regSocialEmailFocus(true);
	}
	else
	{
		if (UserSettings.TenantHash)
		{
			window.location.href = '?helpdesk=' + UserSettings.TenantHash;
		}
		else
		{
			window.location.href = '?helpdesk';
		}
	}
};

CLoginView.prototype.actionForgot = function ()
{
	this.sendingPasswordProcess(true);

	Ajax.send('Forgot', { 'Email': this.forgotEmail() }, this.onHelpdeskForgotResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CLoginView.prototype.onHelpdeskForgotResponse = function (oResponse, oRequest)
{
	this.sendingPasswordProcess(false);
	
	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		Api.showErrorByCode(oResponse, TextUtils.i18n('HELPDESK/ERROR_FORGOT_FAILED'));
		
		this.forgotEmailFocus(true);
	}
	else
	{
		Utils.log('CLoginView', TextUtils.i18n('HELPDESK/INFO_FORGOT_SUCCESSFULL'));
		Screens.showReport(TextUtils.i18n('HELPDESK/INFO_FORGOT_SUCCESSFULL'));

		this.email(this.forgotEmail());
		this.passwordFocus(true);
		_.delay(_.bind(function () {this.forgotEmail('');}, this), 500);

		this.gotoForgot(false);
	}
};

CLoginView.prototype.backToLogin = function ()
{
	location.replace('?helpdesk=' + UserSettings.TenantHash);
};

CLoginView.prototype.actionChangepass = function ()
{
	if (!this.changingPasswordProcess())
	{
		if (this.changepassNewpass() === '')
		{
			this.changepassNewpassFocus(true);
			return;
		}

		if (this.changepassConfirmpass() === '')
		{
			this.changepassConfirmpassFocus(true);
			return;
		}

		if (this.changepassNewpass() !== this.changepassConfirmpass())
		{
			Screens.showError(TextUtils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
			this.changepassNewpassFocus(true);
			return;
		}

		this.changingPasswordProcess(true);

		Ajax.send('ChangePassword', {
			'ActivateHash': Settings.ForgotHash,
			'NewPassword': this.changepassNewpass()
		}, this.onHelpdeskForgotChangePasswordResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CLoginView.prototype.onHelpdeskForgotChangePasswordResponse = function (oResponse, oRequest)
{
	this.changingPasswordProcess(false);
	
	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		Api.showErrorByCode(oResponse, TextUtils.i18n('HELPDESK/ERROR_CHANGEPASS_FAILED'));

		this.changepassNewpassFocus(true);
	}
	else
	{
		Storage.setData('ReportText', TextUtils.i18n('HELPDESK/INFO_CHANGEPASS_SUCCESSFULL'));
		
		this.backToLogin();
	}
};

module.exports = new CLoginView();
