'use strict';

var
	ContactsCache = require('modules/%ModuleName%/js/Cache.js'),
	MainTabContactsMethods = {
		markVcardsExistentByFile: function (sFile) {
				ContactsCache.markVcardsExistentByFile(sFile);
		}
	}
;

window.MainTabContactsMethods = MainTabContactsMethods;

module.exports = {};