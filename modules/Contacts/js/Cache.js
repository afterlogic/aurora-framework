'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	Ajax = require('modules/Contacts/js/Ajax.js')
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
 * @param {Object} oResponseContext Context in which the function should be called.
 */
CContactsCache.prototype.getContactsByEmails = function (aEmails, fResponseHandler, oResponseContext)
{
//	if (AppData.User.ShowContacts)
//	{
		var
			aEmailsForRequest = [],
			sHandlerId = Math.random().toString()
		;

		_.each(aEmails, _.bind(function (sEmail) {
			var oContact = this.contacts[sEmail];
			
			if (oContact !== undefined)
			{
				fResponseHandler.apply(oResponseContext, [oContact, sEmail]);
			}
			else
			{
				this.contacts[sEmail] = null;
				aEmailsForRequest.push(sEmail);
			}
		}, this));
		
		if (aEmailsForRequest.length > 0)
		{
			this.responseHandlers[sHandlerId] = {
				'func': fResponseHandler,
				'context': oResponseContext
			};
			
			Ajax.send('GetContactsByEmails', {
				'Emails': aEmailsForRequest.join(','),
				'HandlerId': sHandlerId
			}, this.onContactsGetByEmailsResponse, this);
		}
//	}
};

/**
 * Receives data from the server, parses them and passes on.
 * 
 * @param {Object} oResponse Data obtained from the server.
 * @param {Object} oRequest Data has been transferred to the server.
 */
CContactsCache.prototype.onContactsGetByEmailsResponse = function (oResponse, oRequest)
{
	var
		oHandler = this.responseHandlers[oRequest.HandlerId],
		oResult = oResponse.Result
	;
	
	if (oResult)
	{
		_.each(oResult, _.bind(function (oRawContact, sEmail) {
			var oContact = new CContactModel();
			
			if (oContact)
			{
				oContact.parse(oRawContact);
				this.contacts[sEmail] = oContact;

				if (oHandler)
				{
					oHandler.func.apply(oHandler.context, [oContact, sEmail]);
				}
			}
		}, this));
	}
	
	this.responseHandlers[oRequest.HandlerId] = undefined;
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