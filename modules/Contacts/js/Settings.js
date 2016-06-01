'use strict';

var
	_ = require('underscore'),
	
	Types = require('modules/Core/js/utils/Types.js')
;

module.exports = {
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