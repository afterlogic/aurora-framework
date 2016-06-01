'use strict';

var ko = require('knockout');

module.exports = {
	enableOpenPgp: ko.observable(true),
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.enableOpenPgp(!!oAppDataSection.EnableModule);
		}
	}
};