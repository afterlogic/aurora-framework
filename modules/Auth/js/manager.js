'use strict';

module.exports = function (oSettings) {
	require('modules/Auth/js/enums.js');

	var
		Ajax = require('modules/Auth/js/Ajax.js'),
		Settings = require('modules/Auth/js/Settings.js')
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
			var oParameters = iLastErrorCode ? {LastErrorCode: iLastErrorCode} : null;
			
			Ajax.send('Logout', oParameters, fOnLogoutResponse, oContext);
		}
	};
};