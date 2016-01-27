'use strict';

var
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	MainTabContactsMethods = {
		markVcardsExistentByFile: function (sFile) {
				ContactsCache.markVcardsExistentByFile(sFile);
		}
	}
;

window.MainTabContactsMethods = MainTabContactsMethods;

module.exports = {};