'use strict';

var
	ko = require('knockout'),
	
	AddressUtils = require('modules/CoreClient/js/utils/Address.js'),
	Types = require('modules/CoreClient/js/utils/Types.js'),
	Utils = require('modules/CoreClient/js/utils/Common.js')
;

/**
 * @constructor
 */
function CFetcherModel()
{
	this.FETCHER = true; // constant
	
	this.id = ko.observable(0);
	this.accountId = ko.observable(0);
	this.hash = ko.computed(function () {
		return Utils.getHash(this.accountId() + 'fetcher' + this.id());
	}, this);
	this.isEnabled = ko.observable(false);
	this.isLocked = ko.observable(false).extend({'autoResetToFalse': 1000});
	this.email = ko.observable('');
	this.userName = ko.observable('');
	this.folder = ko.observable('');
	this.useSignature = ko.observable(false);
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
	this.id(Types.pInt(oData.IdFetcher));
	this.accountId(Types.pInt(oData.IdAccount));
	this.isEnabled(!!oData.IsEnabled);
	this.isLocked(!!oData.IsLocked);
	this.email(Types.pString(oData.Email));
	this.userName(Types.pString(oData.Name));
	this.folder(Types.pString(oData.Folder));
	this.useSignature(!!oData.SignatureOptions);
	this.signature(Types.pString(oData.Signature));
	this.incomingMailServer(Types.pString(oData.IncomingMailServer));
	this.incomingMailPort(Types.pInt(oData.IncomingMailPort));
	this.incomingMailSsl(!!oData.IncomingMailSsl);
	this.incomingMailLogin(Types.pString(oData.IncomingMailLogin));
	this.leaveMessagesOnServer(!!oData.LeaveMessagesOnServer);
	this.isOutgoingEnabled(!!oData.IsOutgoingEnabled);
	this.outgoingMailServer(Types.pString(oData.OutgoingMailServer));
	this.outgoingMailPort(Types.pInt(oData.OutgoingMailPort));
	this.outgoingMailSsl(!!oData.OutgoingMailSsl);
	this.outgoingMailAuth(!!oData.OutgoingMailAuth);
};

module.exports = CFetcherModel;
