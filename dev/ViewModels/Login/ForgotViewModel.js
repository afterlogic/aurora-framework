
/**
 * @constructor
 */
function CForgotViewModel()
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
		return this.gettingQuestion() ? Utils.i18n('LOGIN/BUTTON_GETTING_QUESTION') : Utils.i18n('LOGIN/BUTTON_GET_QUESTION');
	}, this);
	this.allowGetQuestion = ko.computed(function () {
		return !this.gettingQuestion() && Utils.trim(this.email()) !== '';
	}, this);
	this.getQuestionCommand = Utils.createCommand(this, this.executeGetQuestion, this.allowGetQuestion);
	
	this.visibleQuestionForm = ko.observable(false);
	this.question = ko.observable('');
	this.answer = ko.observable('');
	this.answerFocus = ko.observable(false);
	this.validatingAnswer = ko.observable(false);
	this.validateAnswerButtonText = ko.computed(function () {
		return this.validatingAnswer() ? Utils.i18n('LOGIN/BUTTON_VALIDATING_ANSWER') : Utils.i18n('LOGIN/BUTTON_VALIDATE_ANSWER');
	}, this);
	this.allowValidatingAnswer = ko.computed(function () {
		return !this.validatingAnswer() && Utils.trim(this.answer()) !== '';
	}, this);
	this.validateAnswerCommand = Utils.createCommand(this, this.executeValidateAnswer, this.allowValidatingAnswer);
	
	this.visiblePasswordForm = ko.observable(false);
	this.password = ko.observable('');
	this.confirmPassword = ko.observable('');
	this.passwordFocus = ko.observable(false);
	this.confirmPasswordFocus = ko.observable(false);
	this.changingPassword = ko.observable(false);
	this.changePasswordButtonText = ko.computed(function () {
		return this.changingPassword() ? Utils.i18n('LOGIN/BUTTON_RESETTING_PASSWORD') : Utils.i18n('LOGIN/BUTTON_RESET_PASSWORD');
	}, this);
	this.allowChangePassword = ko.computed(function () {
		var
			sPassword = Utils.trim(this.password()),
			sConfirmPassword = Utils.trim(this.confirmPassword()),
			bEmptyFields = (sPassword === '' || sConfirmPassword === '')
		;

		return !this.changingPassword() && !bEmptyFields;
	}, this);
	this.changePasswordCommand = Utils.createCommand(this, this.executeChangePassword, this.allowChangePassword);
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CForgotViewModel.prototype.__name = 'CForgotViewModel';

CForgotViewModel.prototype.executeGetQuestion = function ()
{
	var
		oParameters = {
			'Action': 'AccountGetForgotQuestion',
			'Email': this.email()
		}
	;

	this.gettingQuestion(true);

	App.Ajax.send(oParameters, this.onAccountGetForgotQuestionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CForgotViewModel.prototype.onAccountGetForgotQuestionResponse = function (oResponse, oRequest)
{
	var sQuestion = '';
	
	this.gettingQuestion(false);
	
	if (false === oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('LOGIN/ERROR_GETTING_QUESTION'));
	}
	else
	{
		sQuestion = Utils.pString(oResponse.Result.Question);
		
		if (sQuestion === '')
		{
			App.Api.showError(Utils.i18n('LOGIN/ERROR_PASSWORD_RESET_NOT_AVAILABLE'));
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

CForgotViewModel.prototype.executeValidateAnswer = function ()
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

	App.Ajax.send(oParameters, this.onAccountValidateForgotQuestionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CForgotViewModel.prototype.onAccountValidateForgotQuestionResponse = function (oResponse, oRequest)
{
	this.validatingAnswer(false);
	
	if (false === oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('LOGIN/ERROR_WRONG_ANSWER'));
	}
	else
	{
		this.visibleEmailForm(false);
		this.visibleQuestionForm(false);
		this.visiblePasswordForm(true);
	}
};

CForgotViewModel.prototype.executeChangePassword = function ()
{
	if (this.password() !== this.confirmPassword())
	{
		App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
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
		
		App.Ajax.send(oParameters, this.onAccountChangeForgotPasswordResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CForgotViewModel.prototype.onAccountChangeForgotPasswordResponse = function (oResponse, oRequest)
{
	this.changingPassword(false);
	
	if (false === oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('LOGIN/ERROR_RESETTING_PASSWORD'));
	}
	else
	{
		this.gotoForgot(false);
		App.Api.showReport(Utils.i18n('LOGIN/REPORT_PASSWORD_CHANGED'));
	}
};
