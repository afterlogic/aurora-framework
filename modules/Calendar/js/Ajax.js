'use strict';

var Ajax = require('core/js/Ajax.js');

Ajax.registerAbortRequestHandler('Calendar', function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'UpdateEvent':
			return	oOpenedRequest.Method === 'UpdateEvent' && 
					oOpenedRequest.Parameters.calendarId === oRequest.Parameters.calendarId && 
					oOpenedRequest.Parameters.uid === oRequest.Parameters.uid;
		case 'GetCalendars':
			return oOpenedRequest.Method === 'GetCalendars';
		case 'GetEvents':
			return oOpenedRequest.Method === 'GetEvents';
	}
	
	return false;
});

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('Calendar', sMethod, oParameters, fResponseHandler, oContext);
	}
};