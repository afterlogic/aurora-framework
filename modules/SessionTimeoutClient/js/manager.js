'use strict';

var
	$ = require('jquery'),
	
	App = require('modules/Core/js/App.js'),
			
	aSessionTimeoutFunctions = [],
	iSessionTimeout = 0,
	iTimeoutSeconds = 0
;

function LogoutBySessionTimeout()
{
	_.each(aSessionTimeoutFunctions, function (oFunc) {
		oFunc();
	});

	_.delay(function () {
		App.logout();
	}, 500);
}

function SetSessionTimeout()
{
	clearTimeout(iSessionTimeout);
	iSessionTimeout = setTimeout(LogoutBySessionTimeout, iTimeoutSeconds * 1000);
}

module.exports = function (oSettings) {
	if (App.getUserRole() !== Enums.UserRole.Anonymous && typeof oSettings.TimeoutSeconds === 'number' && oSettings.TimeoutSeconds > 0)
	{
		iTimeoutSeconds = oSettings.TimeoutSeconds;
		
		SetSessionTimeout();

		$('body')
			.on('click', SetSessionTimeout)
			.on('keydown', SetSessionTimeout)
		;
	}
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		registerFunction: function (oSessionTimeoutFunction) {
			aSessionTimeoutFunctions.push(oSessionTimeoutFunction);
		}
	};
};