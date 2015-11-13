'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	_ = require('underscore'),
	
	Ajax = require('modules/Contacts/js/Ajax.js'),
	CContactModel = require('modules/Contacts/js/models/CContactModel.js')
;

/**
 * @constructor
 */
function CContactsCache()
{
	this.contacts = {};
	this.responseHandlers = {};
	
	this.vcardAttachments = [];
	
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});

	this.newContactParams = null;
}

/**
 * @param {string} sEmail
 */
CContactsCache.prototype.clearInfoAboutEmail = function (sEmail)
{
	this.contacts[sEmail] = undefined;
};

/**
 * Looks for contacts in the cache and returns them by the specified handler.
 * If some of contacts are not found in the cache, requests them from the server by specified emails.
 * 
 * @param {Array} aEmails List of emails.
 * @param {Function} fResponseHandler Function to call when the server response.
 */
CContactsCache.prototype.getContactsByEmails = function (aEmails, fResponseHandler)
{
	var
		aContacts = [],
		aEmailsForRequest = [],
		sHandlerId = Math.random().toString()
	;

	_.each(aEmails, _.bind(function (sEmail) {
		var oContact = this.contacts[sEmail];

		if (oContact !== undefined)
		{
			aContacts[sEmail] = oContact;
		}
		else
		{
			this.contacts[sEmail] = null;
			aEmailsForRequest.push(sEmail);
		}
	}, this));
	
//	console.log('aContacts', aContacts);
//	console.log('aContacts.length', aContacts.length);
//	console.log('fResponseHandler', fResponseHandler);
//	console.log('$.isFunction(fResponseHandler)', $.isFunction(fResponseHandler));
	if ($.isFunction(fResponseHandler))
	{
		fResponseHandler(aContacts);
	}

	if (aEmailsForRequest.length > 0)
	{
		this.responseHandlers[sHandlerId] = fResponseHandler;

		Ajax.send('GetContactsByEmails', {
			'Emails': aEmailsForRequest.join(','),
			'HandlerId': sHandlerId
		}, this.onGetContactsByEmailsResponse, this);
	}
};

/**
 * Receives data from the server, parses them and passes on.
 * 
 * @param {Object} oResponse Data obtained from the server.
 * @param {Object} oRequest Data has been transferred to the server.
 */
CContactsCache.prototype.onGetContactsByEmailsResponse = function (oResponse, oRequest)
{
	var
		oParameters = JSON.parse(oRequest.Parameters),
		fResponseHandler = this.responseHandlers[oParameters.HandlerId],
		oResult = oResponse.Result,
		aEmails = oParameters.Emails.split(','),
		aContacts = []
	;
	
	if (oResult)
	{
		_.each(oResult, _.bind(function (oRawContact, sEmail) {
			var oContact = new CContactModel();
			
			if (oContact)
			{
				oContact.parse(oRawContact);
				this.contacts[sEmail] = oContact;
			}
		}, this));
	}
	
	_.each(aEmails, _.bind(function (sEmail) {
		aContacts[sEmail] = this.contacts[sEmail];
	}, this));
	
	if ($.isFunction(fResponseHandler))
	{
		fResponseHandler(aContacts);
	}
	
	delete this.responseHandlers[oParameters.HandlerId];
};

/**
 * @param {Object} oVcard
 */
CContactsCache.prototype.addVcard = function (oVcard)
{
	this.vcardAttachments.push(oVcard);
};

/**
 * @param {Array} aUids
 */
CContactsCache.prototype.markVcardsNonexistentByUid = function (aUids)
{
	_.each(this.vcardAttachments, function (oVcard) {
		if (-1 !== _.indexOf(aUids, oVcard.uid()))
		{
			oVcard.exists(false);
		}
	});
};

/**
 * @param {string} sFile
 */
CContactsCache.prototype.markVcardExistentByFile = function (sFile)
{
	_.each(this.vcardAttachments, function (oVcard) {
		if (oVcard.file() === sFile)
		{
			oVcard.exists(true);
		}
	});
};

module.exports = new CContactsCache();