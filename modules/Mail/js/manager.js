'use strict';

require('modules/Mail/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
			
	Settings = require('modules/Mail/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': function () {
				return require('modules/Mail/js/views/CMailView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Mail/js/views/HeaderItemView.js');
		},
		prefetcher: require('modules/Mail/js/Prefetcher.js'),
		getBrowserTitle: function (bBrowserFocused) {
			var Accounts = require('modules/Mail/js/AccountList.js');
			
			if (bBrowserFocused || HeaderItemView.unseenCount() === 0)
			{
				return Accounts.getEmail() + ' - ' + TextUtils.i18n('TITLE/MAILBOX');
			}
			else
			{
				return TextUtils.i18n('TITLE/HAS_UNSEEN_MESSAGES_PLURAL', {'COUNT': HeaderItemView.unseenCount()}, null, HeaderItemView.unseenCount()) + ' - ' + Accounts.getEmail()
			}
		}
	};
};