'use strict';

var
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	AddressUtils = require('core/js/utils/Address.js')
;

/**
 * @constructor
 */
function CFetcherModel()
{
	this.FETCHER = true; // constant
	
	this.id = ko.observable(0);
	this.accountId = ko.observable(0);
	this.isEnabled = ko.observable(false);
	this.isLocked = ko.observable(false).extend({'autoResetToFalse': 1000});
	this.email = ko.observable('');
	this.userName = ko.observable('');
	this.folder = ko.observable('');
	this.signatureOptions = ko.observable(false);
	this.signature = ko.observable('');
	this.incomingMailServer = ko.observable('');
	this.incomingMailPort = ko.observable(0);
	this.incomingMailSsl = ko.observable(false);
	this.incomingMailLogin = ko.observable('');
	this.leaveMessagesOnServer = ko.observable('');
	this.isOutgoingEnabled = ko.observable(false);
	this.outgoingMailServer = ko.observable('');
	this.outgoingMailPort = ko.observable(0);
	this.outgoingMailSsl = ko.observable(false);
	this.outgoingMailAuth = ko.observable(false);
	
	this.fullEmail = ko.computed(function () {
		return AddressUtils.getFullEmail(this.userName(), this.email());
	}, this);
}

/**
 * @param {Object} oData
 */
CFetcherModel.prototype.parse = function (oData)
{
	this.id(Utils.pInt(oData.IdFetcher));
	this.accountId(Utils.pInt(oData.IdAccount));
	this.isEnabled(!!oData.IsEnabled);
	this.isLocked(!!oData.IsLocked);
	this.email(Utils.pString(oData.Email));
	this.userName(Utils.pString(oData.Name));
	this.folder(Utils.pString(oData.Folder));
	this.signatureOptions(!!oData.SignatureOptions);
	this.signature(Utils.pString(oData.Signature));
	this.incomingMailServer(Utils.pString(oData.IncomingMailServer));
	this.incomingMailPort(Utils.pInt(oData.IncomingMailPort));
	this.incomingMailSsl(!!oData.IncomingMailSsl);
	this.incomingMailLogin(Utils.pString(oData.IncomingMailLogin));
	this.leaveMessagesOnServer(!!oData.LeaveMessagesOnServer);
	this.isOutgoingEnabled(!!oData.IsOutgoingEnabled);
	this.outgoingMailServer(Utils.pString(oData.OutgoingMailServer));
	this.outgoingMailPort(Utils.pInt(oData.OutgoingMailPort));
	this.outgoingMailSsl(!!oData.OutgoingMailSsl);
	this.outgoingMailAuth(!!oData.OutgoingMailAuth);
};

module.exports = CFetcherModel;