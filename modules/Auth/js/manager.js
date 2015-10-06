'use strict';

var
	Ajax = require('modules/Auth/js/Ajax.js'),
	Settings = require('modules/Auth/js/Settings.js')
;

require('modules/Auth/js/enums.js');

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Auth/js/views/CWrapLoginView.js');
			}
		},
		logout: function (iLastErrorCode, fOnLogoutResponse, oContext)
		{
			var oParameters = iLastErrorCode ? {LastErrorCode: iLastErrorCode} : null;
			
			Ajax.send('Logout', oParameters, fOnLogoutResponse, oContext);
		}
	};
};