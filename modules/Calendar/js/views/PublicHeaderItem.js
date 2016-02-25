'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
			
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	
	PublicHeaderItem = new CHeaderItemView(TextUtils.i18n('CALENDAR/ACTION_SHOW_CALENDAR'))
;

module.exports = PublicHeaderItem;
