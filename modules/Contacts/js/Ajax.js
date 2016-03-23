'use strict';

var Ajax = require('modules/Core/js/Ajax.js');

Ajax.registerAbortRequestHandler('Contacts', function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'GetContacts':
			return oOpenedRequest.Method === 'GetContacts';
		case 'GetContact':
			return oOpenedRequest.Method === 'GetContact';
	}
	
	return false;
});

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('Contacts', sMethod, oParameters, fResponseHandler, oContext);
	}
};