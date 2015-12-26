'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 */ 
function CAccountAutoresponderPageView()
{
	this.account = ko.observable(0);
	this.loading = ko.observable(false);

	this.enable = ko.observable(false);
	this.subject = ko.observable('');
	this.message = ko.observable('');

	this.account.subscribe(function () {
		this.getAutoresponder();
	}, this);
	
	this.firstState = null;
}

CAccountAutoresponderPageView.prototype.ViewTemplate = 'Mail_Settings_AccountAutoresponderPageView';

/**
 * @param {Object} oAccount
 */
CAccountAutoresponderPageView.prototype.onShow = function (oAccount)
{
	this.account(oAccount);
};

CAccountAutoresponderPageView.prototype.getState = function ()
{
	var aState = [
		this.enable(),
		this.subject(),
		this.message()	
	];
	
	return aState.join(':');
};

CAccountAutoresponderPageView.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountAutoresponderPageView.prototype.isChanged = function()
{
	return this.firstState && this.getState() !== this.firstState;
};

CAccountAutoresponderPageView.prototype.prepareParameters = function ()
{
	return {
		'AccountID': this.account().id(),
		'Enable': this.enable() ? '1' : '0',
		'Subject': this.subject(),
		'Message': this.message()
	};
};

/**
 * @param {Object} oParameters
 */
CAccountAutoresponderPageView.prototype.saveData = function (oParameters)
{
	this.updateFirstState();
	Ajax.send('AccountAutoresponderUpdate', oParameters, this.onAccountAutoresponderUpdateResponse, this);
};

CAccountAutoresponderPageView.prototype.onSaveClick = function ()
{
	if (this.account())
	{
		var oAutoresponder = this.account().autoresponder();

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

CAccountAutoresponderPageView.prototype.getAutoresponder = function()
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
			this.loading(true);
			Ajax.send('AccountAutoresponderGet', {AccountID: this.account().id()}, this.onAccountAutoresponderGetResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderPageView.prototype.onAccountAutoresponderGetResponse = function (oResponse, oRequest)
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
				oAccount = Accounts.getAccount(iAccountId);
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
		Screens.showError(TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountAutoresponderPageView.prototype.onAccountAutoresponderUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result)
		{
			Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNT_AUTORESPONDER_SUCCESS_REPORT'));
		}
		else
		{
			Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
		}
	}
	else
	{
		Screens.showError(TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

module.exports = new CAccountAutoresponderPageView();
