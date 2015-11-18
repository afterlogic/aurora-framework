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
		registerMessagePaneTopController: function (oController) {
			var MessagePaneView = require('modules/Mail/js/views/MessagePaneView.js');
			MessagePaneView.registerTopController(oController);
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
			var BaseTab = window.opener && window.opener.BaseTabMethods;
			return BaseTab ? _.bind(BaseTab.searchMessagesInCurrentFolder, BaseTab) : false;
		}
	};
};
