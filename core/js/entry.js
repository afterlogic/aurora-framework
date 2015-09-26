'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Auth': require('modules/Auth/js/manager.js'),
			'Mail': require('modules/Mail/js/manager.js'),
			'Contacts': require('modules/Contacts/js/manager.js'),
			'Calendar': require('modules/Calendar/js/manager.js'),
			'Files': require('modules/Files/js/manager.js'),
			'Settings': require('modules/Settings/js/manager.js'),
			
			'OpenPgp': require('modules/OpenPgp/js/manager.js'),
			'SessionTimeout': require('modules/SessionTimeout/js/manager.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	ModulesManager.init(oAvaliableModules);
	App.init();
});
