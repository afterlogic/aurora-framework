'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {})
	;
	
	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return bPublic;
		},
		getScreens: function () {
			var oScreens = {};
			oScreens[Settings.HashModuleName] = function () {
				var CFilesView = require('modules/%ModuleName%/js/views/CFilesView.js');
				return new CFilesView();
			};
			return oScreens;
		}
	};
};
