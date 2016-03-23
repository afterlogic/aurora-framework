'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Mail': require('modules/Mail/js/manager-newtab.js'),
			'Contacts': require('modules/Contacts/js/manager-components.js'),
			'Calendar': require('modules/Calendar/js/manager-newtab.js'),
			'MailSensitivity': require('modules/MailSensitivity/js/manager.js'),
			'OpenPgp': require('modules/OpenPgp/js/manager.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js')
	;
	
	App.setNewTab();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
