'use strict';

var _ = require('underscore');

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
	},
	update: function (sFilesEnable) {
		this.filesEnable(sFilesEnable === '1');
	}
};
