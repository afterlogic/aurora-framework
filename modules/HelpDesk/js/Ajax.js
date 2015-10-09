'use strict';

var
	Ajax = require('core/js/Ajax.js'),
	
	bExtApp = false
;

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		oParameters.IsExt = bExtApp ? 1 : 0;
		Ajax.send('Helpdesk', sMethod, oParameters, fResponseHandler, oContext);
	}
};