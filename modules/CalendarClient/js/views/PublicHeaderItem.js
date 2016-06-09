'use strict';

var
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
			
	CHeaderItemView = require('modules/CoreClient/js/views/CHeaderItemView.js'),
	
	PublicHeaderItem = new CHeaderItemView(TextUtils.i18n('%MODULENAME%/ACTION_SHOW_CALENDAR'))
;

module.exports = PublicHeaderItem;
