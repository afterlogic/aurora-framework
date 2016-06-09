'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'AuthClient': require('modules/AuthClient/js/manager.js'),
			'MailClient': require('modules/MailClient/js/manager.js'),
			'ContactsClient': require('modules/ContactsClient/js/manager.js'),
			'CalendarClient': require('modules/CalendarClient/js/manager.js'),
			'FilesClient': require('modules/FilesClient/js/manager.js'),
			'HelpDeskClient': require('modules/HelpDeskClient/js/manager.js'),
			'PhoneClient': require('modules/PhoneClient/js/manager.js'),
			'SettingsClient': require('modules/SettingsClient/js/manager.js'),
			'SimpleChatClient': require('modules/SimpleChatClient/js/manager.js'),
			'SimpleChatEmojiClient': require('modules/SimpleChatEmojiClient/js/manager.js'),
			
			'OpenPgpClient': require('modules/OpenPgpClient/js/manager.js'),
			'MailSensitivityClient': require('modules/MailSensitivityClient/js/manager.js'),
			'SessionTimeoutClient': require('modules/SessionTimeoutClient/js/manager.js'),
			'ChangePasswordClient': require('modules/ChangePasswordClient/js/manager.js'),
			'MobileSyncClient': require('modules/MobileSyncClient/js/manager.js'),
			'OutlookSyncClient': require('modules/OutlookSyncClient/js/manager.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js'),
		bSwitchingToMobile = App.checkMobile()
	;
	
	if (!bSwitchingToMobile)
	{
		ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
		App.init();
	}
});
