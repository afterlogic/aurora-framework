'use strict';

var
	Ajax = require('modules/Core/js/Ajax.js')
;

Ajax.registerAbortRequestHandler('Calendar', function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'UpdateEvent':
			var
				oParameters = oRequest.ParametersObject,
				oOpenedParameters = oOpenedRequest.ParametersObject
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
