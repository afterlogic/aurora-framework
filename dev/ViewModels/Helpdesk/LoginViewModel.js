/**
 * @constructor
 */
function CHelpdeskLoginViewModel()
{
	this.emailFocus = ko.observable(false);
	this.email = ko.observable('');
	
	this.passwordFocus = ko.observable(false);
	this.password = ko.observable('');
	
	this.signMeType = ko.observable(true);
	this.signMe = ko.observable(true);
	
	this.loginDescription = ko.observable('');
	this.loginProcess = ko.observable(false);

	this.loginCustomLogo = ko.observable(AppData['HelpdeskStyleImage'] || '');
	
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
			App.Storage.setData('helpdeskQuestion');
		}
	}, this);
	this.helpdeskQuestionFocus = ko.observable('');

	this.signInButtonText = ko.computed( function () {
		if(this.registeringProcess())
		{
			return Utils.i18n('LOGIN/BUTTON_SIGNING_IN');
		}
		/*else if (this.helpdeskQuestion())
		{
			return Utils.i18n('Sign in and Send');
		}
		else if (!this.helpdeskQuestion())*/
		else
		{
			return Utils.i18n('LOGIN/BUTTON_SIGN_IN');
		}
	}, this);

	this.regButtonText = ko.computed( function () {
		if(this.registeringProcess())
		{
			return Utils.i18n('HELPDESK/BUTTON_REGISTERING');
		}
		/*else if (this.helpdeskQuestion())
		{
			return Utils.i18n('Register and Send');
		}
		else if (!this.helpdeskQuestion())*/
		else
		{
			return Utils.i18n('HELPDESK/BUTTON_REGISTER');
		}
	}, this);

	this.sendingPasswordProcess = ko.observable(false);
	this.forgotButtonText = ko.computed(function () {
		return this.sendingPasswordProcess() ?
			Utils.i18n('MAIN/BUTTON_SENDING') :
			Utils.i18n('HELPDESK/BUTTON_SEND_PASSWORD');
	}, this);
	this.forgotEmailFocus = ko.observable(false);
	this.forgotEmail = ko.observable('');
	
	this.changingPasswordProcess = ko.observable(false);
	this.changepassButtonText = ko.computed(function () {
		return this.changingPasswordProcess() ?
			Utils.i18n('HELPDESK/BUTTON_CHANGING_PASS') :
			Utils.i18n('HELPDESK/BUTTON_CHANGE_PASS');
	}, this);
	this.changepassNewpassFocus = ko.observable(false);
	this.changepassNewpass = ko.observable('');
	this.changepassConfirmpassFocus = ko.observable(false);
	this.changepassConfirmpass = ko.observable('');
	
	this.gotoForgot = ko.observable(false);
	this.gotoRegister = ko.observable(false);
	this.gotoSignin = ko.observable(false);
	this.gotoSocialRegister = ko.observable(false);
//	this.gotoActivation = ko.observable(false);
	this.gotoChangepass = ko.observable(AppData['HelpdeskForgotHash'] ? true : false);

	this.socialFacebook = ko.observable(AppData['SocialFacebook']);
	this.socialGoogle = ko.observable(AppData['SocialGoogle']);
	this.socialTwitter = ko.observable(AppData['SocialTwitter']);

	this.socialEmail = ko.observable(AppData['SocialEmail']);
	this.socialIsLoggedIn = ko.observable(AppData['SocialIsLoggedIn']);

	this.shake = ko.observable(false).extend({'autoResetToFalse': 800});

	this.loginCommand = Utils.createCommand(this, this.actionLogin, function () {
		return !this.loginProcess();
	});
	this.sendCommand = Utils.createCommand(this, this.actionSend, this.helpdeskQuestion);
	this.registerCommand = Utils.createCommand(this, this.actionRegister, function () {
		return !this.registeringProcess() && Utils.trim(this.regPassword()) !== '' && Utils.trim(this.regConfirmPassword()) !== ''  && Utils.trim(this.regEmail()) !== '' ;
	});
	this.forgotCommand = Utils.createCommand(this, this.actionForgot, function () {
		return !this.sendingPasswordProcess() && Utils.trim(this.forgotEmail()) !== '';
	});

	this.socialNetworkLogin();

	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CHelpdeskLoginViewModel.prototype.__name = 'CHelpdeskLoginViewModel';

CHelpdeskLoginViewModel.prototype.onShow = function ()
{
	var sReportText = App.Storage.getData('ReportText');
	
	if (sReportText)
	{
		App.Api.showReport(sReportText);
		
		App.Storage.removeData('ReportText');
	}

	if(App.Storage.getData('helpdeskQuestion'))
	{
		this.helpdeskQuestion(App.Storage.getData('helpdeskQuestion'));
	}

	$html.addClass('non-adjustable-valign');
};

CHelpdeskLoginViewModel.prototype.onHide = function ()
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
//	this.gotoActivation(false);
	this.gotoChangepass(false);

};

CHelpdeskLoginViewModel.prototype.socialNetworkLogin = function ()
{
	this.regSocialEmail(this.socialEmail());

	if (this.socialIsLoggedIn()) {
		this.gotoSocialRegister(true);
	}
};

CHelpdeskLoginViewModel.prototype.onSocialClick = function (sSocial)
{
	this.storeQuestion();
	$.cookie('external-services-redirect', 'helpdesk');
	if (window !== window.top)
	{
		var
			x = screen.width/2 - 700/2,
			y = screen.height/2 - 600/2
		;

		window.open(Utils.Common.getAppPath() + '?external-services=' + sSocial + '&scopes=login', sSocial, 'location=no,toolbar=no,status=no,scrollbars=yes,resizable=yes,menubar=no,width=700,height=600,left=' + x + ',top=' + y);
	}
	else
	{
		window.location.href = '?external-services=' + sSocial + '&scopes=login';
	}
};

CHelpdeskLoginViewModel.prototype.storeQuestion = function ()
{
	if(this.helpdeskQuestion() !== '')
	{
		App.Storage.setData('helpdeskQuestion', this.helpdeskQuestion());
	}
};

CHelpdeskLoginViewModel.prototype.actionSend = function ()
{
	this.storeQuestion();
	this.gotoRegister(true);
};

CHelpdeskLoginViewModel.prototype.actionLogin = function ()
{
	$('.check_autocomplete_input').trigger('input').trigger('change').trigger('keydown');

	if (Utils.trim(this.password()) && '' !== Utils.trim(this.email()))
	{
		this.storeQuestion();

		this.loginProcess(true);

		App.Ajax.sendExt({
			'Action': 'HelpdeskLogin',
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
CHelpdeskLoginViewModel.prototype.onHelpdeskLoginResponse = function (oResponse, oRequest)
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

			App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_LOGIN_FAILED'));

			this.shake(true);
			this.emailFocus(true);
		}
	}
};

CHelpdeskLoginViewModel.prototype.actionRegister = function ()
{
	if (this.regPassword() !== this.regConfirmPassword())
	{
		App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
		this.regPasswordFocus(true);
	}
	else
	{
		this.registeringProcess(true);

		App.Ajax.sendExt({
			'Action': 'HelpdeskRegister',
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
CHelpdeskLoginViewModel.prototype.onHelpdeskRegisterResponse = function (oResponse, oRequest)
{	
	this.registeringProcess(false);

	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_REGISTRATION_FAILED'));

		this.regEmailFocus(true);
	}
	else
	{
		App.Api.showReport(Utils.i18n('HELPDESK/ACTIVATION_DESCRIPTION', {
			'EMAIL': this.regEmail()
		}));

		this.gotoRegister(false);
	}
};

CHelpdeskLoginViewModel.prototype.actionSocialRegister = function ()
{
	if (!this.registeringProcess())
	{
		if (this.regSocialEmail() === '')
		{
			this.regSocialEmailFocus(true);
			return;
		}

		this.registeringProcess(true);

		App.Ajax.sendExt({
			'Action': 'SocialRegister',
			'NotificationEmail': this.regSocialEmail()
		}, this.onHelpdeskSocialRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskSocialRegisterResponse = function (oResponse, oRequest)
{
	this.registeringProcess(false);

	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_REGISTRATION_FAILED'));

		this.regSocialEmailFocus(true);
	}
	else
	{
		if(AppData['TenantHash'])
		{
			window.location.href = '?helpdesk=' + AppData['TenantHash'];
		}
		else
		{
			window.location.href = '?helpdesk';
		}
	}
};

CHelpdeskLoginViewModel.prototype.actionForgot = function ()
{
	this.sendingPasswordProcess(true);

	App.Ajax.sendExt({
		'Action': 'HelpdeskForgot',
		'Email': this.forgotEmail()
	}, this.onHelpdeskForgotResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskForgotResponse = function (oResponse, oRequest)
{
	this.sendingPasswordProcess(false);
	
	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_FORGOT_FAILED'));
		
		this.forgotEmailFocus(true);
	}
	else
	{
		App.Api.showReport(Utils.i18n('HELPDESK/INFO_FORGOT_SUCCESSFULL'));

		this.email(this.forgotEmail());
		this.passwordFocus(true);
		_.delay(_.bind(function () {this.forgotEmail('');}, this), 500);

		this.gotoForgot(false);
	}
};

CHelpdeskLoginViewModel.prototype.backToLogin = function ()
{
	location.replace('?helpdesk=' + AppData['TenantHash']);
};

CHelpdeskLoginViewModel.prototype.actionChangepass = function ()
{
	if (!this.changingPasswordProcess())
	{
		var
			oParameters = {
				'Action': 'HelpdeskForgotChangePassword',
				'TenantHash': AppData['TenantHash'],
				'ActivateHash': AppData['HelpdeskForgotHash'],
				'NewPassword': this.changepassNewpass()
			}
		;

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
			App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
			this.changepassNewpassFocus(true);
			return;
		}

		this.changingPasswordProcess(true);

		App.Ajax.sendExt(oParameters, this.onHelpdeskForgotChangePasswordResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskForgotChangePasswordResponse = function (oResponse, oRequest)
{
	this.changingPasswordProcess(false);
	
	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_CHANGEPASS_FAILED'));

		this.changepassNewpassFocus(true);
	}
	else
	{
		App.Storage.setData('ReportText', Utils.i18n('HELPDESK/INFO_CHANGEPASS_SUCCESSFULL'));
		
		this.backToLogin();
	}
};
