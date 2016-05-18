'use strict';

var
	Ajax = require('modules/Core/js/Ajax.js')
;

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('SimpleChat', sMethod, oParameters, fResponseHandler, oContext);
	}
};