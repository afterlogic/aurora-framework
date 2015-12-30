'use strict';

var
	_ = require('underscore'),
	
	Utils = require('core/js/utils/Common.js')
;

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
		delete this.init;
		
		this.ResetPassHash = Utils.getRequestParam('reset-pass') || '';
	}
};