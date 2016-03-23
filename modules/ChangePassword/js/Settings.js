'use strict';

var
	_ = require('underscore'),
	
	Utils = require('modules/Core/js/utils/Common.js')
;

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
		delete this.init;
		
		this.ResetPassHash = Utils.getRequestParam('reset-pass') || '';
	}
};