'use strict';

var
	_ = require('underscore'),
	
	Types = require('modules/CoreClient/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: 'Contacts',
	HashModuleName: 'contacts',
	
	ContactsPerPage: 20,
	ImportContactsLink: '',
	Storages: ['personal', 'global', 'shared'],
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.ContactsPerPage = Types.pInt(oAppDataSection.ContactsPerPage);
			this.ImportContactsLink = Types.pString(oAppDataSection.ImportingContacts);
			this.Storages = _.isArray(oAppDataSection.Storages) ? oAppDataSection.Storages : [];
		}
	}
};