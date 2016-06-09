'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');
	
	require('modules/%ModuleName%/js/koBindings.js');

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
				return require('modules/%ModuleName%/js/views/LoginView.js');
			};
			return oScreens;
		}
	};
};
