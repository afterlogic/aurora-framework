'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Types = require('modules/CoreClient/js/utils/Types.js'),
	Utils = require('modules/CoreClient/js/utils/Common.js'),
	
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	Api = require('modules/CoreClient/js/Api.js'),
	Screens = require('modules/CoreClient/js/Screens.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CRegisterView()
{
	this.name = ko.observable('');
	this.login = ko.observable('');
	this.password = ko.observable('');
	this.confirmPassword = ko.observable('');
	this.question = ko.observable('');
	this.yourQuestion = ko.observable('');
	this.answer = ko.observable('');
	this.bAllowQuestionPart = Types.isNonEmptyArray(Settings.RegistrationQuestions);
	this.visibleYourQuestion = ko.computed(function () {
		return (this.question() === TextUtils.i18n('%MODULENAME%/OPTION_YOUR_QUESTION'));
	}, this);
	
	this.nameFocus = ko.observable(false);
	this.loginFocus = ko.observable(false);
	this.passwordFocus = ko.observable(false);
	this.confirmPasswordFocus = ko.observable(false);
	this.questionFocus = ko.observable(false);
	this.answerFocus = ko.observable(false);
	this.yourQuestionFocus = ko.observable(false);
	
	this.aDomains = Settings.RegistrationDomains;
	this.domain = ko.computed(function () {
		return (this.aDomains.length === 1) ? this.aDomains[0] : '';
	}, this);
	this.selectedDomain = ko.observable((this.aDomains.length > 0) ? this.aDomains[0] : '');
	
	this.aRegistrationQuestions = [];
	if (this.bAllowQuestionPart)
	{
		this.aRegistrationQuestions = _.map(_.union('', _.without(Settings.RegistrationQuestions, '*')), function (sQuestion) {
			return {text: (sQuestion !== '') ? sQuestion : TextUtils.i18n('%MODULENAME%/LABEL_SELECT_QUESTION'), value: sQuestion};
		});
		if (_.indexOf(Settings.RegistrationQuestions, '*') !== -1)
		{
			this.aRegistrationQuestions.push({text: TextUtils.i18n('%MODULENAME%/OPTION_YOUR_QUESTION'), value: TextUtils.i18n('%MODULENAME%/OPTION_YOUR_QUESTION')});
		}
	}
	
	this.loading = ko.observable(false);
	
	this.canBeRegister = ko.computed(function () {
		var
			sLogin = $.trim(this.login()),
			sPassword = $.trim(this.password()),
			sConfirmPassword = $.trim(this.confirmPassword()),
			sQuestion = $.trim(this.visibleYourQuestion() ? this.yourQuestion() : this.question()),
			sAnswer = $.trim(this.answer()),
			bEmptyFields = (sLogin === '' || sPassword === '' || sConfirmPassword === '' || 
					this.bAllowQuestionPart && (sQuestion === '' || sAnswer === ''))
		;

		return !this.loading() && !bEmptyFields;
	}, this);

	this.registerButtonText = ko.computed(function () {
		return this.loading() ? TextUtils.i18n('CORE/ACTION_REGISTER_IN_PROGRESS') : TextUtils.i18n('CORE/ACTION_REGISTER');
	}, this);
	
	this.registerCommand = Utils.createCommand(this, this.registerAccount, this.canBeRegister);
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CRegisterView.prototype.ViewTemplate = '%ModuleName%_RegisterView';
CRegisterView.prototype.__name = 'CRegisterView';

CRegisterView.prototype.registerAccount = function ()
{
	if (this.password() !== this.confirmPassword())
	{
		Screens.showError(TextUtils.i18n('CORE/ERROR_PASSWORDS_DO_NOT_MATCH'));
	}
	else
	{
		var
			oParameters = {
				'Action': 'AccountRegister',
				'Name': this.name(),
				'Email': this.login() + '@' + this.selectedDomain(),
				'Password': this.password(),
				'Question': this.bAllowQuestionPart ? (this.visibleYourQuestion() ? this.yourQuestion() : this.question()) : '',
				'Answer': this.bAllowQuestionPart ? this.answer() : ''
			}
		;

		this.loading(true);
		
		Ajax.send(oParameters, this.onAccountRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CRegisterView.prototype.onAccountRegisterResponse = function (oResponse, oRequest)
{
	if (false === oResponse.Result)
	{
		this.loading(false);
		
		Api.showErrorByCode(oResponse, TextUtils.i18n('CORE/ERROR_PASS_INCORRECT'));
	}
	else
	{
		window.location.reload();
	}
};

module.exports = CRegisterView;
