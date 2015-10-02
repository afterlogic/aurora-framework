'use strict';

var
	Ajax = require('core/js/Ajax.js')
;

function GetRequestParameters(sMethod, oParameters)
{
	var oRequestParameters = {
		'Module': 'Files',
		'Method': sMethod
	};
	if (oParameters)
	{
		oRequestParameters.Parameters = JSON.stringify(oParameters);
	}
	return oRequestParameters;
}

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send(GetRequestParameters(sMethod, oParameters), fResponseHandler, oContext);
	},
	sendExt: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.sendExt(GetRequestParameters(sMethod, oParameters), fResponseHandler, oContext);
	}
};