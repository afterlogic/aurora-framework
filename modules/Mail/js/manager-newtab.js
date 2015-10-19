'use strict';

module.exports = function (oSettings) {
	require('modules/Mail/js/enums.js');

	var
		TextUtils = require('core/js/utils/Text.js'),

		Settings = require('modules/Mail/js/Settings.js'),
		Cache = null,
		Accounts = null
	;

	Settings.init(oSettings);
	
	Cache = require('modules/Mail/js/Cache.js');
	Cache.init();
	
	return {
		screens: {
			'main': function () {
				return require('modules/Mail/js/views/CMessagePaneView.js');
			}
		},
		getBrowserTitle: function () {
			if (Accounts === null)
			{
				Accounts = require('modules/Mail/js/AccountList.js');
			}
			return Accounts.getEmail() + ' - ' + TextUtils.i18n('TITLE/MAILBOX');
		}
	};
};
