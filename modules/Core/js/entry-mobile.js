'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Auth': require('modules/Auth/js/manager.js'),
			'Mail': require('modules/Mail/js/manager.js'),
			'Contacts': require('modules/Contacts/js/manager-mobile.js'),
			'SessionTimeout': require('modules/SessionTimeout/js/manager.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js')
	;
	
	App.setMobile();
	ModulesManager.init(oAvaliableModules, !App.isAuth() && !App.isPublic());
	App.init();
});
