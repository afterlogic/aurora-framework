'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Auth': require('modules/Auth/js/manager.js'),
			'Mail': require('modules/Mail/js/manager.js')
//			'Contacts': require('modules/Contacts/js/manager.js'),
//			'Calendar': require('modules/Calendar/js/manager.js'),
//			'Files': require('modules/Files/js/manager.js'),
//			'Helpdesk': require('modules/HelpDesk/js/manager.js'),
//			'Phone': require('modules/Phone/js/manager.js'),
//			'Settings': require('modules/Settings/js/manager.js'),
//			
//			'OpenPgp': require('modules/OpenPgp/js/manager.js'),
//			'MailSensitivity': require('modules/MailSensitivity/js/manager.js'),
//			'SessionTimeout': require('modules/SessionTimeout/js/manager.js'),
//			'ChangePassword': require('modules/ChangePassword/js/manager.js'),
//			'MobileSync': require('modules/MobileSync/js/manager.js'),
//			'OutlookSync': require('modules/OutlookSync/js/manager.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js'),
		bSwitchingToMobile = App.checkMobile()
	;
	
	if (!bSwitchingToMobile)
	{
		ModulesManager.init(oAvaliableModules, !App.isAuth() && !App.isPublic());
		App.init();
	}
});
