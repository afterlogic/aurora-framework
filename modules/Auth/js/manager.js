'use strict';

module.exports = function (oSettings) {
	require('modules/Auth/js/enums.js');

	var
		_ = require('underscore'),
				
		Utils = require('core/js/utils/Common.js'),
		
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
			var oParameters = {'AuthToken': Storage.getData('AuthToken')};
			
			if (iLastErrorCode)
			{
				_.extendOwn(oParameters, {'LastErrorCode': iLastErrorCode});
			}
			
			Ajax.send('Logout', oParameters, fOnLogoutResponse, oContext);
		},
		beforeAppRunning: function (bAuth) {
			if (!bAuth && Utils.isNonEmptyString(Settings.CustomLoginUrl))
			{
				window.location.href = Settings.CustomLoginUrl;
			}
			Storage.setData('AuthToken', Storage.getData('AuthToken'));
		}
	};
};