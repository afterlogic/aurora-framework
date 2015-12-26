'use strict';

var
	ko = require('knockout'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	
	Popups = require('core/js/Popups.js'),
	AlertPopup = require('core/js/popups/AlertPopup.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 */
function CAccountForwardPageView()
{
	this.account = ko.observable(0);
	this.loading = ko.observable(false);

	this.enable = ko.observable(false);
	this.email = ko.observable('');
	this.emailFocus = ko.observable(false);

	this.account.subscribe(function () {
		this.getForward();
	}, this);
	
	this.firstState = null;
}

CAccountForwardPageView.prototype.ViewTemplate = 'Mail_Settings_AccountForwardPageView';

/**
 * @param {Object} oAccount
 */
CAccountForwardPageView.prototype.onShow = function (oAccount)
{
	this.account(oAccount);
};

CAccountForwardPageView.prototype.getState = function ()
{
	return [this.enable(), this.email()].join(':');
};

CAccountForwardPageView.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountForwardPageView.prototype.isChanged = function()
{
	return this.firstState && this.getState() !== this.firstState;
};

CAccountForwardPageView.prototype.prepareParameters = function ()
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
CAccountForwardPageView.prototype.saveData = function (oParameters)
{
	this.updateFirstState();
	Ajax.send(oParameters, this.onAccountForwardUpdateResponse, this);
};

CAccountForwardPageView.prototype.onSaveClick = function ()
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
			if (!AddressUtils.isCorrectEmail(this.email()))
			{
				Popups.showPopup(AlertPopup, [TextUtils.i18n('COMPOSE/WARNING_INPUT_CORRECT_EMAILS') + ' ' + this.email()]);
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

CAccountForwardPageView.prototype.getForward = function()
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
			Ajax.send(oParameters, this.onAccountForwardGetResponse, this);
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardPageView.prototype.onAccountForwardGetResponse = function (oResponse, oRequest)
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
				oAccount = Accounts.getAccount(iAccountId);
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
		Screens.showError(TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountForwardPageView.prototype.onAccountForwardUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result)
		{
			Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNT_FORWARD_SUCCESS_REPORT'));
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

module.exports = new CAccountForwardPageView();
