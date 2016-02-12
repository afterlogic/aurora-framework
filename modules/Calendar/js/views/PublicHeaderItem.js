'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
			
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	
	PublicHeaderItem = new CHeaderItemView(TextUtils.i18n('CALENDAR/HEADER_TABNAME'))
;

module.exports = PublicHeaderItem;
