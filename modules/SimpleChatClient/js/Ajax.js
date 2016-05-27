'use strict';

var
	Ajax = require('modules/Core/js/Ajax.js')
;

Ajax.registerAbortRequestHandler('SimpleChat', function (oRequest, oOpenedRequest) {
	var
		oParameters = oRequest.ParametersObject,
		oOpenedParameters = oOpenedRequest.ParametersObject
	;
	
	switch (oRequest.Method)
	{
		case 'CreatePost':
			return	oOpenedRequest.Method === 'GetPosts' && oOpenedParameters.Offset === 0;
		case 'GetPosts':
			return	oOpenedRequest.Method === 'GetPosts' && oParameters.Offset <= oOpenedParameters.Offset;
	}
	
	return false;
});

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('SimpleChat', sMethod, oParameters, fResponseHandler, oContext);
	}
};