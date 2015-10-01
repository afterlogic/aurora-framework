'use strict';

var
	Ajax = require('core/js/Ajax.js')
;

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		var oRequestParameters = {
			'Module': 'Calendar',
			'Method': sMethod
		};
		if (oParameters)
		{
			oRequestParameters.Parameters = JSON.stringify(oParameters);
		}
		Ajax.send(oRequestParameters, fResponseHandler, oContext);
	},
	sendExt: function (sMethod, oParameters, fResponseHandler, oContext) {
		var oRequestParameters = {
			'Module': 'Calendar',
			'Method': sMethod
		};
		if (oParameters)
		{
			oRequestParameters.Parameters = JSON.stringify(oParameters);
		}
		Ajax.sendExt(oRequestParameters, fResponseHandler, oContext);
	}
};