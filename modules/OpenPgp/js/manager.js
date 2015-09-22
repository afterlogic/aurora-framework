'use strict';

var
	Settings = require('modules/OpenPgp/js/Settings.js')
;

function IsPgpSupported()
{
	return !!(window.crypto && window.crypto.getRandomValues);
}

if (IsPgpSupported())
{
	self.openPgp = window.openpgp ? new OpenPgp(window.openpgp, 'user_' + (sUserUid || '0') + '_') : false;
}

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		getComposeButtons: function () {
			if (IsPgpSupported())
			{
				return require('modules/OpenPgp/js/views/ComposeButtonsView.js');
			}
			else
			{
				return null;
			}
		}
	};
};