'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
	
	CHeaderItemView = require('core/js/views/CHeaderItemView.js')
;

module.exports = new CHeaderItemView(TextUtils.i18n('CONTACTS/HEADER_TABNAME'));
