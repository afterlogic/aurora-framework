'use strict';

var
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

Ajax.registerAbortRequestHandler(Settings.ServerModuleName, function (oRequest, oOpenedRequest) {
	switch (oRequest.Method)
	{
		case 'UpdateEvent':
			var
				oParameters = oRequest.Parameters,
				oOpenedParameters = oOpenedRequest.Parameters
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
		Ajax.send(Settings.ServerModuleName, sMethod, oParameters, fResponseHandler, oContext);
	}
};
