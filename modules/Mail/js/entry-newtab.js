'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Mail': require('modules/Mail/js/manager-newtab.js'),
			'Contacts': require('modules/Contacts/js/manager-components.js'),
			'MailSensitivity': require('modules/MailSensitivity/js/manager.js'),
			'OpenPgp': require('modules/OpenPgp/js/manager.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	App.setNewTab();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
