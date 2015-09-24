'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	Ajax = require('core/js/Ajax.js')
;

/**
 * @constructor
 */
function CForgotView()
{
	this.gotoForgot = ko.observable(false);
	this.gotoForgot.subscribe(function () {
		this.visibleEmailForm(true);
		this.visibleQuestionForm(false);
		this.visiblePasswordForm(false);
	}, this);
	
	this.visibleEmailForm = ko.observable(true);
	this.email = ko.observable('');
	this.emailFocus = ko.observable(false);
	this.gettingQuestion = ko.observable(false);
	this.getQuestionButtonText = ko.computed(function () {
		return this.gettingQuestion() ? TextUtils.i18n('LOGIN/BUTTON_GETTING_QUESTION') : TextUtils.i18n('LOGIN/BUTTON_GET_QUESTION');
	}, this);
	this.allowGetQuestion = ko.computed(function () {
		return !this.gettingQuestion() && $.trim(this.email()) !== '';
	}, this);
	this.getQuestionCommand = Utils.createCommand(this, this.executeGetQuestion, this.allowGetQuestion);
	
	this.visibleQuestionForm = ko.observable(false);
	this.question = ko.observable('');
	this.answer = ko.observable('');
	this.answerFocus = ko.observable(false);
	this.validatingAnswer = ko.observable(false);
	this.validateAnswerButtonText = ko.computed(function () {
		return this.validatingAnswer() ? TextUtils.i18n('LOGIN/BUTTON_VALIDATING_ANSWER') : TextUtils.i18n('LOGIN/BUTTON_VALIDATE_ANSWER');
	}, this);
	this.allowValidatingAnswer = ko.computed(function () {
		return !this.validatingAnswer() && $.trim(this.answer()) !== '';
	}, this);
	this.validateAnswerCommand = Utils.createCommand(this, this.executeValidateAnswer, this.allowValidatingAnswer);
	
	this.visiblePasswordForm = ko.observable(false);
	this.password = ko.observable('');
	this.confirmPassword = ko.observable('');
	this.passwordFocus = ko.observable(false);
	this.confirmPasswordFocus = ko.observable(false);
	this.changingPassword = ko.observable(false);
	this.changePasswordButtonText = ko.computed(function () {
		return this.changingPassword() ? TextUtils.i18n('LOGIN/BUTTON_RESETTING_PASSWORD') : TextUtils.i18n('LOGIN/BUTTON_RESET_PASSWORD');
	}, this);
	this.allowChangePassword = ko.computed(function () {
		var
			sPassword = $.trim(this.password()),
			sConfirmPassword = $.trim(this.confirmPassword()),
			bEmptyFields = (sPassword === '' || sConfirmPassword === '')
		;

		return !this.changingPassword() && !bEmptyFields;
	}, this);
	this.changePasswordCommand = Utils.createCommand(this, this.executeChangePassword, this.allowChangePassword);
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CForgotView.prototype.ViewTemplate = 'Auth_ForgotView';
CForgotView.prototype.__name = 'CForgotView';

CForgotView.prototype.executeGetQuestion = function ()
{
	var
		oParameters = {
			'Action': 'AccountGetForgotQuestion',
			'Email': this.email()
		}
	;

	this.gettingQuestion(true);

	Ajax.send(oParameters, this.onAccountGetForgotQuestionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CForgotView.prototype.onAccountGetForgotQuestionResponse = function (oResponse, oRequest)
{
	var sQuestion = '';
	
	this.gettingQuestion(false);
	
	if (false === oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('LOGIN/ERROR_GETTING_QUESTION'));
	}
	else
	{
		sQuestion = Utils.pString(oResponse.Result.Question);
		
		if (sQuestion === '')
		{
			Screens.showError(TextUtils.i18n('LOGIN/ERROR_PASSWORD_RESET_NOT_AVAILABLE'));
		}
		else
		{
			this.question(sQuestion);
			this.visibleEmailForm(false);
			this.visibleQuestionForm(true);
			this.visiblePasswordForm(false);
		}
	}
};

CForgotView.prototype.executeValidateAnswer = function ()
{
	var
		oParameters = {
			'Action': 'AccountValidateForgotQuestion',
			'Email': this.email(),
			'Question': this.question(),
			'Answer': this.answer()
		}
	;

	this.validatingAnswer(true);

	Ajax.send(oParameters, this.onAccountValidateForgotQuestionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CForgotView.prototype.onAccountValidateForgotQuestionResponse = function (oResponse, oRequest)
{
	this.validatingAnswer(false);
	
	if (false === oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('LOGIN/ERROR_WRONG_ANSWER'));
	}
	else
	{
		this.visibleEmailForm(false);
		this.visibleQuestionForm(false);
		this.visiblePasswordForm(true);
	}
};

CForgotView.prototype.executeChangePassword = function ()
{
	if (this.password() !== this.confirmPassword())
	{
		Screens.showError(TextUtils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
	}
	else
	{
		var
			oParameters = {
				'Action': 'AccountChangeForgotPassword',
				'Email': this.email(),
				'Question': this.question(),
				'Answer': this.answer(),
				'Password': this.password()
			}
		;

		this.changingPassword(true);
		
		Ajax.send(oParameters, this.onAccountChangeForgotPasswordResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CForgotView.prototype.onAccountChangeForgotPasswordResponse = function (oResponse, oRequest)
{
	this.changingPassword(false);
	
	if (false === oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('LOGIN/ERROR_RESETTING_PASSWORD'));
	}
	else
	{
		this.gotoForgot(false);
		Screens.showReport(TextUtils.i18n('LOGIN/REPORT_PASSWORD_CHANGED'));
	}
};

module.exports = CForgotView;