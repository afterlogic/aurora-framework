'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'MailClient': require('modules/MailClient/js/manager-newtab.js'),
			'ContactsClient': require('modules/ContactsClient/js/manager-components.js'),
			'CalendarClient': require('modules/CalendarClient/js/manager-newtab.js'),
			'MailSensitivityClient': require('modules/MailSensitivityClient/js/manager.js'),
			'OpenPgpClient': require('modules/OpenPgpClient/js/manager.js')
		},
		ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
		App = require('modules/CoreClient/js/App.js')
	;
	
	App.setNewTab();
	ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
	App.init();
});
