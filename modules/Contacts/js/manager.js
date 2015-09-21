'use strict';

require('modules/Contacts/js/enums.js');

var
	TextUtils = require('core/js/utils/Text.js'),
	CHeaderItemView = require('core/js/views/CHeaderItemView.js'),
	
	Settings = require('modules/Contacts/js/Settings.js')
;

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': {
				'Model': require('modules/Contacts/js/views/CContactsView.js'),
				'TemplateName': 'Contacts_ContactsView'
			}
		},
		headerItem: new CHeaderItemView(TextUtils.i18n('HEADER/CONTACTS'), TextUtils.i18n('TITLE/CONTACTS')),
		getBrowserTitle: function () {
			return TextUtils.i18n('TITLE/CONTACTS');
		}
	};
};
