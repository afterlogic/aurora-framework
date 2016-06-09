'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Mail': require('modules/MailClient/js/manager-newtab.js'),
			'Contacts': require('modules/ContactsClient/js/manager-components.js'),
			'Calendar': require('modules/CalendarClient/js/manager-newtab.js'),
			'MailSensitivity': require('modules/MailSensitivityClient/js/manager.js'),
			'OpenPgp': require('modules/OpenPgpClient/js/manager.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js')
	;
	
	App.setNewTab();
	ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
	App.init();
});
