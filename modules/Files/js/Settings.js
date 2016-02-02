'use strict';

var _ = require('underscore');

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
	},
	update: function (sFilesEnable) {
		this.enableFiles(sFilesEnable === '1');
	}
};
