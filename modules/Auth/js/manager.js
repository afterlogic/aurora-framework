'use strict';

module.exports = function (oSettings) {
	require('modules/Auth/js/enums.js');

	var
		_ = require('underscore'),
				
		Types = require('core/js/utils/Types.js'),
		
		Ajax = require('modules/Auth/js/Ajax.js'),
		Settings = require('modules/Auth/js/Settings.js'),
		Storage = require('core/js/Storage.js')
	;

	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Auth/js/views/WrapLoginView.js');
			}
		},
		logout: function (iLastErrorCode, fOnLogoutResponse, oContext)
		{
			Ajax.send('Logout', iLastErrorCode ? {'LastErrorCode': iLastErrorCode} : null, fOnLogoutResponse, oContext);
		},
		beforeAppRunning: function (bAuth) {
			if (!bAuth && Types.isNonEmptyString(Settings.CustomLoginUrl))
			{
				window.location.href = Settings.CustomLoginUrl;
			}
		}
	};
};
