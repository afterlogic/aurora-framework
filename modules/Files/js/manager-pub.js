'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');

	var Settings = require('modules/%ModuleName%/js/Settings.js');
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return bPublic;
		},
		getScreens: function () {
			return {
				'main': function () {
					var CFilesView = require('modules/%ModuleName%/js/views/CFilesView.js');
					return new CFilesView();
				}
			};
		}
	};
};
