'use strict';

var
	$ = require('jquery'),
	_ = require('underscore'),
	
	Ajax = require('modules/Contacts/js/Ajax.js'),
	CContactModel = require('modules/Contacts/js/models/CContactModel.js')
;

/**
 * @constructor
 */
function CContactsCache()
{
	this.oContacts = {};
	this.oResponseHandlers = {};
	this.aRequestedEmails = [];
	
	this.aVcardAttachments = [];
	
	this.oNewContactParams = null;
}

/**
 * @param {string} sEmail
 */
CContactsCache.prototype.clearInfoAboutEmail = function (sEmail)
{
	this.oContacts[sEmail] = undefined;
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
	return;
	var
		aContacts = [],
		aEmailsForRequest = [],
		sHandlerId = Math.random().toString()
	;
	_.each(aEmails, _.bind(function (sEmail) {
		var oContact = this.oContacts[sEmail];
		if (oContact !== undefined)
		{
			aContacts[sEmail] = oContact;
		}
		else if (_.indexOf(this.aRequestedEmails, sEmail) === -1)
		{
			aEmailsForRequest.push(sEmail);
		}
	}, this));
	
	if ($.isFunction(fResponseHandler))
	{
		fResponseHandler(aContacts);
	}

	if (aEmailsForRequest.length > 0)
	{
		this.oResponseHandlers[sHandlerId] = fResponseHandler;
		
		this.aRequestedEmails = _.union(this.aRequestedEmails, aEmailsForRequest);
		
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
		oParameters = oRequest.Parameters || {},
		fResponseHandler = this.oResponseHandlers[oParameters.HandlerId],
		oResult = oResponse.Result,
		aEmails = oParameters.Emails.split(','),
		oContacts = {}
	;
	
	if (oResult)
	{
		_.each(oResult, _.bind(function (oRawContact, sEmail) {
			var oContact = new CContactModel();
			
			if (oContact)
			{
				oContact.parse(oRawContact);
				this.oContacts[sEmail] = oContact;
			}
		}, this));
	}
	
	this.aRequestedEmails = _.difference(this.aRequestedEmails, aEmails);
		
	_.each(aEmails, _.bind(function (sEmail) {
		if (!this.oContacts[sEmail])
		{
			this.oContacts[sEmail] = null;
		}
		oContacts[sEmail] = this.oContacts[sEmail];
	}, this));
	
	if ($.isFunction(fResponseHandler))
	{
		fResponseHandler(oContacts);
	}
	
	delete this.oResponseHandlers[oParameters.HandlerId];
};

/**
 * @param {Object} oVcard
 */
CContactsCache.prototype.addVcard = function (oVcard)
{
	this.aVcardAttachments.push(oVcard);
};

/**
 * @param {string} sFile
 */
CContactsCache.prototype.getVcard = function (sFile)
{
	return _.find(this.aVcardAttachments, function (oVcard) {
		return oVcard.file() === sFile;
	});
};

/**
 * @param {string} sFile
 */
CContactsCache.prototype.markVcardsExistentByFile = function (sFile)
{
	_.each(this.aVcardAttachments, function (oVcard) {
		if (oVcard.file() === sFile)
		{
			oVcard.exists(true);
		}
	});
};

/**
 * @param {Array} aUids
 */
CContactsCache.prototype.markVcardsNonexistentByUid = function (aUids)
{
	_.each(this.aVcardAttachments, function (oVcard) {
		if (-1 !== _.indexOf(aUids, oVcard.uid()))
		{
			oVcard.exists(false);
		}
	});
};

/**
 * @param {Object} oNewContactParams
 */
CContactsCache.prototype.saveNewContactParams = function (oNewContactParams)
{
	this.oNewContactParams = oNewContactParams;
};

/**
 * @returns {Object}
 */
CContactsCache.prototype.getNewContactParams = function ()
{
	var oNewContactParams = this.oNewContactParams;
	this.oNewContactParams = null;
	return oNewContactParams;
};

module.exports = new CContactsCache();