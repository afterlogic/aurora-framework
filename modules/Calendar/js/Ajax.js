'use strict';

var
	Types = require('modules/Core/js/utils/Types.js'),
	
	Ajax = require('modules/Core/js/Ajax.js')
;

Ajax.registerAbortRequestHandler('Calendar', function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'UpdateEvent':
			var
				oParameters = Types.isNonEmptyString(oRequest.Parameters) ? JSON.parse(oRequest.Parameters) : null,
				oOpenedParameters = Types.isNonEmptyString(oOpenedRequest.Parameters) ? JSON.parse(oOpenedRequest.Parameters): null
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
