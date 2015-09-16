'use strict';

require('modules/Auth/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js')
;

module.exports = function () {
	return {
		'ScreenList': require('modules/Auth/js/screenList.js'),
		'HeaderItem': new CHeaderItemView(TextUtils.i18n('HEADER/LOGIN'), TextUtils.i18n('TITLE/LOGIN'))
	};
};