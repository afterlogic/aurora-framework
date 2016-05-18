'use strict';

module.exports = function (oSettings) {
	require('modules/Mail/js/enums.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/Mail/js/Settings.js'),
		Cache = null,
		ComposeView = null,
		GetComposeView = function () {
			if (ComposeView === null)
			{
				var CComposeView = require('modules/Mail/js/views/CComposeView.js');
				ComposeView = new CComposeView();
			}
			return ComposeView;
		}
	;

	Settings.init(oSettings);
	
	Cache = require('modules/Mail/js/Cache.js');
	Cache.init();
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function () {
			require('modules/Mail/js/koBindings.js');
		},
		screens: {
			'view': function () {
				return require('modules/Mail/js/views/MessagePaneView.js');
			},
			'compose': function () {
				return GetComposeView();
			}
		},
		registerMessagePaneController: function (oController, sPlace) {
			var MessagePaneView = require('modules/Mail/js/views/MessagePaneView.js');
			MessagePaneView.registerController(oController, sPlace);
		},
		registerComposeToolbarController: function (oController) {
			var ComposeView = GetComposeView();
			ComposeView.registerToolbarController(oController);
		},
		getComposeMessageToAddresses: function () {
			var
				bAllowSendMail = true,
				ComposeUtils = require('modules/Mail/js/utils/ScreenCompose.js')
			;
			
			return bAllowSendMail ? ComposeUtils.composeMessageToAddresses : false;
		},
		getSearchMessagesInCurrentFolder: function () {
			var MainTab = window.opener && window.opener.MainTabMailMethods;
			return MainTab ? _.bind(MainTab.searchMessagesInCurrentFolder, MainTab) : false;
		}
	};
};
