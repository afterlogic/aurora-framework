'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'AuthClient': require('modules/AuthClient/js/manager.js'),
			'MailClient': require('modules/MailClient/js/manager.js'),
			'ContactsClient': require('modules/ContactsClient/js/manager-mobile.js'),
			'SessionTimeoutClient': require('modules/SessionTimeoutClient/js/manager.js')
		},
		ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
		App = require('modules/CoreClient/js/App.js')
	;
	
	App.setMobile();
	ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
	App.init();
});
