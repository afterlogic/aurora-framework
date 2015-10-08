'use strict';

var
	Ajax = require('core/js/Ajax.js'),
	
	bExtApp = false
;

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		var oRequestParameters = {
			'Module': 'Helpdesk',
			'Method': sMethod
		};
		
		if (!oParameters)
		{
			oParameters = {};
		}
		oParameters.IsExt = bExtApp ? 1 : 0;
		oRequestParameters.Parameters = JSON.stringify(oParameters);
		
		if (bExtApp)
		{
			Ajax.sendExt(oRequestParameters, fResponseHandler, oContext);
		}
		else
		{
			Ajax.send(oRequestParameters, fResponseHandler, oContext);
		}
	}
};