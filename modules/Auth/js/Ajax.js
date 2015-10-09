'use strict';

var Ajax = require('core/js/Ajax.js');

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('Auth', sMethod, oParameters, fResponseHandler, oContext);
	}
};