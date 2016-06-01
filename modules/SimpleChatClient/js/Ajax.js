'use strict';

var Ajax = require('modules/Core/js/Ajax.js');

/**
 * Aborts Ajax requests to prevent receiving of obsolete data.
 * 
 * @param {Object} oRequest Parameters of request that is preparing to sending.
 * @param {Object} oOpenedRequest Parameters of request that was sent earlier and is opened now.
 */
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
	/**
	 * Proxy for send method of the Core. Adds to parameters module name.
	 * 
	 * @param {string} sMethod Method of the request.
	 * @param {Object} oParameters Parameters of the request.
	 * @param {Function} fResponseHandler Callback that should be called after response receiving.
	 * @param {Object} oContext Context for callback.
	 */
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('SimpleChat', sMethod, oParameters, fResponseHandler, oContext);
	}
};