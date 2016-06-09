'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		
		Cache = null,
		ComposeView = null,
		GetComposeView = function () {
			if (ComposeView === null)
			{
				var CComposeView = require('modules/%ModuleName%/js/views/CComposeView.js');
				ComposeView = new CComposeView();
			}
			return ComposeView;
		}
	;

	Settings.init(oSettings);
	
	Cache = require('modules/%ModuleName%/js/Cache.js');
	Cache.init();
	
	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function () {
			require('modules/%ModuleName%/js/koBindings.js');
		},
		getScreens: function () {
			var oScreens = {};
			oScreens[Settings.HashModuleName + '-view'] = function () {
				return require('modules/%ModuleName%/js/views/MessagePaneView.js');
			};
			oScreens[Settings.HashModuleName + '-compose'] = function () {
				return GetComposeView();
			};
			return oScreens;
		},
		registerMessagePaneController: function (oController, sPlace) {
			var MessagePaneView = require('modules/%ModuleName%/js/views/MessagePaneView.js');
			MessagePaneView.registerController(oController, sPlace);
		},
		registerComposeToolbarController: function (oController) {
			var ComposeView = GetComposeView();
			ComposeView.registerToolbarController(oController);
		},
		getComposeMessageToAddresses: function () {
			var
				bAllowSendMail = true,
				ComposeUtils = require('modules/%ModuleName%/js/utils/ScreenCompose.js')
			;
			
			return bAllowSendMail ? ComposeUtils.composeMessageToAddresses : false;
		},
		getSearchMessagesInCurrentFolder: function () {
			var MainTab = window.opener && window.opener.MainTabMailMethods;
			return MainTab ? _.bind(MainTab.searchMessagesInCurrentFolder, MainTab) : false;
		}
	};
};
