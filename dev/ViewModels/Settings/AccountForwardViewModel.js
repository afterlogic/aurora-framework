
/**
 * @param {?=} oParent
 *
 * @constructor
 */
function CAccountForwardViewModel(oParent)
{
	this.account = ko.observable(0);
	this.available = ko.computed(function () {
		var oAccount = this.account();
		return oAccount && oAccount.forward();
	}, this);
	this.loading = ko.observable(false);

	this.enable = ko.observable(false);
	this.email = ko.observable('');
	this.emailFocus = ko.observable(false);

	this.account.subscribe(function () {
		this.getForward();
	}, this);
	
	this.firstState = null;
}

/**
 * @param {Object} oAccount
 */
CAccountForwardViewModel.prototype.onShow = function (oAccount)
{
	this.account(oAccount);
};

CAccountForwardViewModel.prototype.getState = function ()
{
	return [this.enable(), this.email()].join(':');
};

CAccountForwardViewModel.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountForwardViewModel.prototype.isChanged = function()
{
	return this.firstState && this.getState() !== this.firstState;
};

CAccountForwardViewModel.prototype.prepareParameters = function ()
{
	return {
		'Action': 'AccountForwardUpdate',
		'AccountID': this.account().id(),
		'Enable': this.enable() ? '1' : '0',
		'Email': this.email()
	};
};

/**
 * @param {Object} oParameters
 */
CAccountForwardViewModel.prototype.saveData = function (oParameters)
{
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onAccountForwardUpdateResponse, this);
};

CAccountForwardViewModel.prototype.onSaveClick = function ()
{
	if (this.account())
	{
		var
			self = this,
			oForward = this.account().forward(),
			fSaveData = function() {
				if (oForward)
				{
					oForward.enable = self.enable();
					oForward.email = self.email();
				}

				self.loading(true);
				self.saveData(self.prepareParameters());
			}
		;

		if (this.enable() && this.email() === '')
		{
			this.emailFocus(true);
		}
		else if (this.enable() && this.email() !== '')
		{
			if (!Utils.Address.isCorrectEmail(this.email()))
			{
				App.Screens.showPopup(AlertPopup, [Utils.i18n('COMPOSE/WARNING_INPUT_CORRECT_EMAILS') + ' ' + this.email()]);
			}
			else
			{
				fSaveData();
			}
		}
		else
		{
			fSaveData();
		}
	}
};

CAccountForwardViewModel.prototype.getForward = function()
{
	if (this.account())
	{
		if (this.account().forward() !== null)
		{
			this.enable(this.account().forward().enable);
			this.email(this.account().forward().email);
			this.firstState = this.getState();
		}
		else
		{
			var	oParameters = {
					'Action': 'AccountForwardGet',
					'AccountID': this.account().id()
				};

			this.loading(true);
			this.updateFirstState();
			App.Ajax.send(oParameters, this.onAccountForwardGetResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardViewModel.prototype.onAccountForwardGetResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result && oResponse.AccountID && this.account())
		{
			var
				oAccount = null,
				oForward = new CForwardModel(),
				iAccountId = Utils.pInt(oResponse.AccountID)
				;

			if (iAccountId)
			{
				oAccount = AppData.Accounts.getAccount(iAccountId);
				if (oAccount)
				{
					oForward.parse(iAccountId, oResponse.Result);
					oAccount.forward(oForward);

					this.enable(oAccount.forward().enable);
					this.email(oAccount.forward().email);

					this.updateFirstState();

					if (iAccountId === this.account().id())
					{
						this.getForward();
					}
				}
			}
		}
	}
	else
	{
		App.Api.showError(Utils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardViewModel.prototype.onAccountForwardUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result)
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_FORWARD_SUCCESS_REPORT'));
		}
		else
		{
			App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
		}
	}
	else
	{
		App.Api.showError(Utils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};
