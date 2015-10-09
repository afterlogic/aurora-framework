'use strict';

var Ajax = require('core/js/Ajax.js');

module.exports = {
	send: function (sMethod, oParameters, fResponseHandler, oContext) {
		Ajax.send('Files', sMethod, oParameters, fResponseHandler, oContext);
	}
};