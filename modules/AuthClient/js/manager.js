'use strict';


module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');
	require('jquery.cookie');

	var
		$ = require('jquery'),
		Types = require('modules/Core/js/utils/Types.js'),
		
		Ajax = require('modules/%ModuleName%/js/Ajax.js'),
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		
		bAllowLoginView = true
	;

	Settings.init(oSettings);
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			bAllowLoginView = iUserRole === Enums.UserRole.Anonymous;
			return !bPublic;
		},
		getScreens: function () {
			var oScreens = {};
			if (bAllowLoginView)
			{
				oScreens[Settings.HashModuleName] = function () {
					return require('modules/%ModuleName%/js/views/WrapLoginView.js');
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
