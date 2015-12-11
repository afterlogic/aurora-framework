'use strict';

var
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	BaseTabMethods = {
		markVcardsExistentByFile: function (sFile) {
				ContactsCache.markVcardsExistentByFile(sFile);
		}
	}
;

window.BaseTabContactsMethods = BaseTabMethods;

module.exports = {};