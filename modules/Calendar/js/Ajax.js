'use strict';

var
	Utils = require('core/js/utils/Common.js'),
	Ajax = require('core/js/Ajax.js')
;

Ajax.registerAbortRequestHandler('Calendar', function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'UpdateEvent':
			var
				oParameters = Utils.isNonEmptyString(oRequest.Parameters) ? JSON.parse(oRequest.Parameters) : null,
				oOpenedParameters = Utils.isNonEmptyString(oOpenedRequest.Parameters) ? JSON.parse(oOpenedRequest.Parameters): null
			;
			return	oOpenedRequest.Method === 'UpdateEvent' && 
					oOpenedParameters.calendarId === oParameters.calendarId && 
					oOpenedParameters.uid === oParameters.uid;
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