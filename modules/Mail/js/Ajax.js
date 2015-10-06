'use strict';

var
	Ajax = require('core/js/Ajax.js')
;

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		var oRequestParameters = {
			'Module': 'Mail',
			'Method': sMethod
		};
		if (oParameters)
		{
			oRequestParameters.Parameters = JSON.stringify(oParameters);
		}
		Ajax.send(oRequestParameters, fResponseHandler, oContext);
	}
};