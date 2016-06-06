'use strict';

var
	TextUtils = require('modules/Core/js/utils/Text.js'),
			
	CHeaderItemView = require('modules/Core/js/views/CHeaderItemView.js'),
	
	PublicHeaderItem = new CHeaderItemView(TextUtils.i18n('%MODULENAME%/ACTION_SHOW_CALENDAR'))
;

module.exports = PublicHeaderItem;
