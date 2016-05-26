'use strict';

var
	Types = require('modules/Core/js/utils/Types.js'),
	
	Ajax = require('modules/Core/js/Ajax.js')
;

Ajax.registerAbortRequestHandler('SimpleChat', function (oRequest, oOpenedRequest) {
	var
		oParameters = oRequest.Parameters || {},
		oOpenedParameters = oRequest.Parameters || {}
	;
	
	switch (oRequest.Method)
	{
		case 'CreatePost':
			return	oOpenedRequest.Method === 'GetPosts' && oOpenedParameters.Offset === 0;
		case 'GetPosts':
			return	oOpenedRequest.Method === 'GetPosts' && oParameters.Offset === oOpenedParameters.Offset;
	}
	
	return false;
});

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('SimpleChat', sMethod, oParameters, fResponseHandler, oContext);
	}
};