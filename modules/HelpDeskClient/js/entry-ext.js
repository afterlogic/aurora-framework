'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Auth': require('modules/Auth/js/manager.js'),
			'Helpdesk': require('modules/HelpDeskClient/js/manager-ext.js'),
			'Settings': require('modules/Settings/js/manager.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js')
	;
	
	App.setPublic();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
