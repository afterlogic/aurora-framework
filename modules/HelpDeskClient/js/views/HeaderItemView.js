'use strict';

var
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	
	CHeaderItemView = require('modules/CoreClient/js/views/CHeaderItemView.js'),
	HeaderItemView = new CHeaderItemView(TextUtils.i18n('%MODULENAME%/ACTION_SHOW_HELPDESK'))
;

//HeaderItemView.allowChangeTitle(true);

module.exports = HeaderItemView;
