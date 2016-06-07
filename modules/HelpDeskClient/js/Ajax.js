'use strict';

var
	Ajax = require('modules/Core/js/Ajax.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js'),
	
	bExtApp = false
;

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		oParameters.IsExt = bExtApp ? 1 : 0;
		Ajax.send(Settings.ServerModuleName, sMethod, oParameters, fResponseHandler, oContext);
	}
};