'use strict';

var
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * Aborts Ajax requests to prevent receiving of obsolete data.
 * 
 * @param {Object} oRequest Parameters of request that is preparing to sending.
 * @param {Object} oOpenedRequest Parameters of request that was sent earlier and is opened now.
 */
Ajax.registerAbortRequestHandler(Settings.ServerModuleName, function (oRequest, oOpenedRequest) {
	var
		oParameters = oRequest.Parameters,
		oOpenedParameters = oOpenedRequest.Parameters
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
	/**
	 * Proxy for send method of the Core. Adds to parameters module name.
	 * 
	 * @param {string} sMethod Method of the request.
	 * @param {Object} oParameters Parameters of the request.
	 * @param {Function} fResponseHandler Callback that should be called after response receiving.
	 * @param {Object} oContext Context for callback.
	 */
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send(Settings.ServerModuleName, sMethod, oParameters, fResponseHandler, oContext);
	}
};