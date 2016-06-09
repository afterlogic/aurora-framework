'use strict';

var
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
			
	CHeaderItemView = require('modules/CoreClient/js/views/CHeaderItemView.js')
;

module.exports = new CHeaderItemView(TextUtils.i18n('%MODULENAME%/ACTION_SHOW_CALENDAR'));
