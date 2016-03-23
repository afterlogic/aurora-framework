'use strict';

var
	Types = require('modules/Core/js/utils/Types.js'),
	
	Ajax = require('modules/Core/js/Ajax.js')
;

Ajax.registerAbortRequestHandler('Mail', function (oRequest, oOpenedRequest) {
	var
		oParameters = Types.isNonEmptyString(oRequest.Parameters) ? JSON.parse(oRequest.Parameters) : null,
		oOpenedParameters = Types.isNonEmptyString(oOpenedRequest.Parameters) ? JSON.parse(oOpenedRequest.Parameters): null
	;
	
	switch (oRequest.Method)
	{
		case 'MoveMessages':
		case 'DeleteMessages':
			return	oOpenedRequest.Method === 'GetMessage' || 
					oOpenedRequest.Method === 'GetMessages' && oOpenedParameters.Folder === oParameters.Folder;
		case 'GetMessages':
		case 'SetMessagesSeen':
		case 'SetMessageFlagged':
			return oOpenedRequest.Method === 'GetMessages' && oOpenedParameters.Folder === oParameters.Folder;
		case 'SetAllMessagesSeen':
			return (oOpenedRequest.Method === 'GetMessages' || oOpenedRequest.Method === 'GetMessages') &&
					oOpenedParameters.Folder === oParameters.Folder;
		case 'ClearFolder':
			// GetRelevantFoldersInformation-request aborted during folder cleaning, not to get the wrong information.
			return	oOpenedRequest.Method === 'GetRelevantFoldersInformation' || 
					oOpenedRequest.Method === 'GetMessages' && oOpenedParameters.Folder === oParameters.Folder;
		case 'GetRelevantFoldersInformation':
			return oOpenedRequest.Method === 'GetRelevantFoldersInformation';
		case 'GetMessagesFlags':
			return oOpenedRequest.Method === 'GetMessagesFlags';
	}
	
	return false;
});

module.exports = {
	getOpenedRequest: function (sMethod) {
		Ajax.getOpenedRequest('Mail', sMethod);
	},
	hasOpenedRequests: function (sMethod) {
		Ajax.hasOpenedRequests('Mail', sMethod);
	},
	registerOnAllRequestsClosedHandler: Ajax.registerOnAllRequestsClosedHandler,
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		var
			MailCache = require('modules/Mail/js/Cache.js'),
			iTimeout = (sMethod === 'GetMessagesBodies') ? 100000 : undefined
		;
		if (oParameters && !oParameters.AccountID)
		{
			oParameters.AccountID = MailCache.currentAccountId();
		}
		Ajax.send('Mail', sMethod, oParameters, fResponseHandler, oContext, iTimeout);
	}
};
