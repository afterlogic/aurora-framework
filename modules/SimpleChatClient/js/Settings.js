'use strict';

var ko = require('knockout');

module.exports = {
	enableModule: ko.observable(false),
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.enableModule(!!oAppDataSection.EnableModule);
		}
	},
	
	update: function (bEnableModule) {
		this.enableModule(bEnableModule);
	}
};
