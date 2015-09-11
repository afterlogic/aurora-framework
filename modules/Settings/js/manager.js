'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js')
;

module.exports = function () {
	return {
		'ScreenList': require('modules/Settings/js/screenList.js'),
		'HeaderItem': new CHeaderItemView(TextUtils.i18n('HEADER/SETTINGS')),
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/SETTINGS');
		}
	};
};