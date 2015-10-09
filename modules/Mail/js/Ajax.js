'use strict';

var Ajax = require('core/js/Ajax.js');

Ajax.registerAbortRequestHandler('Mail', function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'MoveMessages':
		case 'DeleteMessages':
			return	oOpenedRequest.Method === 'GetMessage' || 
					oOpenedRequest.Method === 'GetMessages' && oOpenedRequest.Parameters.Folder === oRequest.Parameters.Folder;
		case 'GetMessages':
		case 'SetMessagesSeen':
		case 'MessageSetFlagged':
			return oOpenedRequest.Method === 'GetMessages' && oOpenedRequest.Parameters.Folder === oRequest.Parameters.Folder;
		case 'MessagesSetAllSeen':
			return (oOpenedRequest.Method === 'GetMessages' || oOpenedRequest.Method === 'GetMessages') &&
					oOpenedRequest.Parameters.Folder === oRequest.Parameters.Folder;
		case 'ClearFolder':
			// GetRelevantFoldersInformation-request aborted during folder cleaning, not to get the wrong information.
			return	oOpenedRequest.Method === 'GetRelevantFoldersInformation' || 
					oOpenedRequest.Method === 'GetMessages' && oOpenedRequest.Parameters.Folder === oRequest.Parameters.Folder;
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
		var iTimeout = (sMethod === 'GetMessagesBodies') ? 100000 : undefined;
		Ajax.send('Mail', sMethod, oParameters, fResponseHandler, oContext, iTimeout);
	}
};