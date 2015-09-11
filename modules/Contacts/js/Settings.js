'use strict';

var AppData = window.pSevenAppData;

module.exports = {
	Storages: ['personal', 'global', 'shared'],
	ContactsPerPage: 20,
	ImportingContactsLink: AppData && AppData['Links'] && AppData['Links']['ImportingContacts'] ? AppData['Links']['ImportingContacts'] : ''
};