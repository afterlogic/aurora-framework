'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	HeaderItemView = new CHeaderItemView(TextUtils.i18n('HEADER/HELPDESK'), TextUtils.i18n('TITLE/HELPDESK'))
;

//HeaderItemView.allowChangeTitle(true);

module.exports = HeaderItemView;