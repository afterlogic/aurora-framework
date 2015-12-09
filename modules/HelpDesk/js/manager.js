'use strict';

module.exports = function (oSettings) {
	require('modules/HelpDesk/js/enums.js');

	var
		Settings = require('modules/HelpDesk/js/Settings.js'),
		CheckState = require('modules/HelpDesk/js/CheckState.js')
	;
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				CheckState.end();
				return require('modules/HelpDesk/js/views/HelpdeskView.js');
			}
		},
		getHeaderItem: function () {
			CheckState.start();
			return require('modules/HelpDesk/js/views/HeaderItemView.js');
		}
	};
};
