'use strict';

var
	App = require('core/js/App.js'),
	Settings = require('core/js/Settings.js'),
			
	aSessionTimeoutFunctions = [],
	iSessionTimeout = 0
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
	iSessionTimeout = setTimeout(LogoutBySessionTimeout, Settings.IdleSessionTimeout);
}

if (App.isAuth() && typeof Settings.IdleSessionTimeout === 'number' && Settings.IdleSessionTimeout > 0)
{
	SetSessionTimeout();
	
	$('body')
		.on('click', SetSessionTimeout)
		.on('keydown', SetSessionTimeout)
	;
}

module.exports = {
	registerFunction: function (oSessionTimeoutFunction) {
		aSessionTimeoutFunctions.push(oSessionTimeoutFunction);
	}
};