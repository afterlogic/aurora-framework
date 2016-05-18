'use strict';


module.exports = function (oSettings) {
	require('modules/Auth/js/enums.js');
	require('jquery.cookie');

	var
		$ = require('jquery'),
		Types = require('modules/Core/js/utils/Types.js'),
		
		Ajax = require('modules/Auth/js/Ajax.js'),
		Settings = require('modules/Auth/js/Settings.js'),
		
		bAllowLoginView = true
	;

	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			bAllowLoginView = iUserRole === Enums.UserRole.Anonymous;
			return !bPublic;
		},
		getScreens: function () {
			var oScreens = {};
			if (bAllowLoginView)
			{
				oScreens['main'] = function () {
					return require('modules/Auth/js/views/WrapLoginView.js');
				};
			}
			return oScreens;
		},
		logout: function (iLastErrorCode, fOnLogoutResponse, oContext)
		{
			Ajax.send('Logout', iLastErrorCode ? {'LastErrorCode': iLastErrorCode} : null, fOnLogoutResponse, oContext);
			
			$.removeCookie('AuthToken');
		},
		beforeAppRunning: function (bAuth) {
			if (!bAuth && Types.isNonEmptyString(Settings.CustomLoginUrl))
			{
				window.location.href = Settings.CustomLoginUrl;
			}
		}
	};
};
