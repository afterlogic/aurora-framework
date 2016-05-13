'use strict';

var
	_ = require('underscore'),
	
	UrlUtils = require('modules/Core/js/utils/Url.js')
;

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
		delete this.init;
		
		this.ResetPassHash = UrlUtils.getRequestParam('reset-pass') || '';
	}
};