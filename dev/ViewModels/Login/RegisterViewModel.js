
/**
 * @constructor
 */
function CRegisterViewModel()
{
	this.name = ko.observable('');
	this.login = ko.observable('');
	this.password = ko.observable('');
	this.confirmPassword = ko.observable('');
	this.question = ko.observable('');
	this.yourQuestion = ko.observable('');
	this.answer = ko.observable('');
	this.allowQuestionPart = Utils.isNonEmptyArray(AppData.App.RegistrationQuestions);
	this.visibleYourQuestion = ko.computed(function () {
		return (this.question() === Utils.i18n('LOGIN/OPTION_YOUR_QUESTION'));
	}, this);
	
	this.nameFocus = ko.observable(false);
	this.loginFocus = ko.observable(false);
	this.passwordFocus = ko.observable(false);
	this.confirmPasswordFocus = ko.observable(false);
	this.questionFocus = ko.observable(false);
	this.answerFocus = ko.observable(false);
	this.yourQuestionFocus = ko.observable(false);
	
	this.domains = ko.observable(Utils.isNonEmptyArray(AppData.App.RegistrationDomains) ? AppData.App.RegistrationDomains : []);
	this.domain = ko.computed(function () {
		return (this.domains().length === 1) ? this.domains()[0] : '';
	}, this);
	this.selectedDomain = ko.observable((this.domains().length > 0) ? this.domains()[0] : '');
	
	this.registrationQuestions = [];
	if (this.allowQuestionPart)
	{
		this.registrationQuestions = _.map(_.union('', _.without(AppData.App.RegistrationQuestions, '*')), function (sQuestion) {
			return {text: (sQuestion !== '') ? sQuestion : Utils.i18n('LOGIN/LABEL_SELECT_QUESTION'), value: sQuestion};
		});
		if (_.indexOf(AppData.App.RegistrationQuestions, '*') !== -1)
		{
			this.registrationQuestions.push({text: Utils.i18n('LOGIN/OPTION_YOUR_QUESTION'), value: Utils.i18n('LOGIN/OPTION_YOUR_QUESTION')});
		}
	}
	
	this.loading = ko.observable(false);
	
	this.canBeRegister = ko.computed(function () {
		var
			sLogin = Utils.trim(this.login()),
			sPassword = Utils.trim(this.password()),
			sConfirmPassword = Utils.trim(this.confirmPassword()),
			sQuestion = Utils.trim(this.visibleYourQuestion() ? this.yourQuestion() : this.question()),
			sAnswer = Utils.trim(this.answer()),
			bEmptyFields = (sLogin === '' || sPassword === '' || sConfirmPassword === '' || 
					this.allowQuestionPart && (sQuestion === '' || sAnswer === ''))
		;

		return !this.loading() && !bEmptyFields;
	}, this);

	this.registerButtonText = ko.computed(function () {
		return this.loading() ? Utils.i18n('LOGIN/BUTTON_REGISTERING') : Utils.i18n('LOGIN/BUTTON_REGISTER');
	}, this);
	
	this.registerCommand = Utils.createCommand(this, this.registerAccount, this.canBeRegister);
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CRegisterViewModel.prototype.__name = 'CRegisterViewModel';

CRegisterViewModel.prototype.registerAccount = function ()
{
	if (this.password() !== this.confirmPassword())
	{
		App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
	}
	else
	{
		var
			oParameters = {
				'Action': 'AccountRegister',
				'Name': this.name(),
				'Email': this.login() + '@' + this.selectedDomain(),
				'Password': this.password(),
				'Question': this.allowQuestionPart ? (this.visibleYourQuestion() ? this.yourQuestion() : this.question()) : '',
				'Answer': this.allowQuestionPart ? this.answer() : ''
			}
		;

		this.loading(true);
		
		App.Ajax.send(oParameters, this.onAccountRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CRegisterViewModel.prototype.onAccountRegisterResponse = function (oResponse, oRequest)
{
	if (false === oResponse.Result)
	{
		this.loading(false);
		
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/LOGIN_PASS_INCORRECT'));
	}
	else
	{
		window.location.reload();
	}
};
