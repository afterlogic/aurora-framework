
/**
 * @param {?=} oParent
 *
 * @constructor
 */ 
function CAccountAutoresponderViewModel(oParent)
{
	this.account = ko.observable(0);
	this.available = ko.computed(function () {
		var oAccount = this.account();
		return oAccount && oAccount.autoresponder();
	}, this);
	this.loading = ko.observable(false);

	this.enable = ko.observable(false);
	this.subject = ko.observable('');
	this.message = ko.observable('');

	this.account.subscribe(function () {
		this.getAutoresponder();
	}, this);
	
	this.firstState = null;
}

/**
 * @param {Object} oAccount
 */
CAccountAutoresponderViewModel.prototype.onShow = function (oAccount)
{
	this.account(oAccount);
};

CAccountAutoresponderViewModel.prototype.getState = function ()
{
	var aState = [
		this.enable(),
		this.subject(),
		this.message()	
	];
	
	return aState.join(':');
};

CAccountAutoresponderViewModel.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountAutoresponderViewModel.prototype.isChanged = function()
{
	if (this.firstState && this.getState() !== this.firstState)
	{
		return true;
	}
	else
	{
		return false;
	}
};

CAccountAutoresponderViewModel.prototype.prepareParameters = function ()
{
	var
		oParameters = {
			'Action': 'AccountAutoresponderUpdate',
			'AccountID': this.account().id(),
			'Enable': this.enable() ? '1' : '0',
			'Subject': this.subject(),
			'Message': this.message()
		}
	;
	
	return oParameters;
};

/**
 * @param {Object} oParameters
 */
CAccountAutoresponderViewModel.prototype.saveData = function (oParameters)
{
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onAccountAutoresponderUpdateResponse, this);
};

CAccountAutoresponderViewModel.prototype.onSaveClick = function ()
{
	if (this.account())
	{
		var
			oAutoresponder = this.account().autoresponder()
		;

		if (oAutoresponder)
		{
			oAutoresponder.enable = this.enable();
			oAutoresponder.subject = this.subject();
			oAutoresponder.message = this.message();
		}

		this.loading(true);
		
		this.saveData(this.prepareParameters());
	}
};

CAccountAutoresponderViewModel.prototype.getAutoresponder = function()
{
	if (this.account())
	{
		if (this.account().autoresponder() !== null)
		{
			this.enable(this.account().autoresponder().enable);
			this.subject(this.account().autoresponder().subject);
			this.message(this.account().autoresponder().message);
			
			this.updateFirstState();
		}
		else
		{
			var
				oParameters = {
					'Action': 'AccountAutoresponderGet',
					'AccountID': this.account().id()
				}
			;

			this.loading(true);
			App.Ajax.send(oParameters, this.onAccountAutoresponderGetResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderViewModel.prototype.onAccountAutoresponderGetResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result && oResponse.AccountID && this.account())
		{
			var
				oAccount = null,
				oAutoresponder = new CAutoresponderModel(),
				iAccountId = Utils.pInt(oResponse.AccountID)
				;

			if (iAccountId)
			{
				oAccount = AppData.Accounts.getAccount(iAccountId);
				if (oAccount)
				{
					oAutoresponder.parse(iAccountId, oResponse.Result);
					oAccount.autoresponder(oAutoresponder);

					if (iAccountId === this.account().id())
					{
						this.getAutoresponder();
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
CAccountAutoresponderViewModel.prototype.onAccountAutoresponderUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result)
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_AUTORESPONDER_SUCCESS_REPORT'));
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
