'use strict';

require('modules/Mail/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
			
	Accounts = require('modules/Mail/js/AccountList.js'),
	HeaderItemView = require('modules/Mail/js/views/HeaderItemView.js')
;

module.exports = function () {
	return {
		'ScreenList': require('modules/Mail/js/screenList.js'),
		'HeaderItem': HeaderItemView,
		getBrowserTitle: function (bBrowserFocused) {
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