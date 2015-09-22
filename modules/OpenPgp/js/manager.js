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
		},
		getMessageControls: function () {
			if (IsPgpSupported())
			{
				return require('modules/OpenPgp/js/views/MessageControlsView.js');
			}
			else
			{
				return null;
			}
		},
		isMessageEncryptedOrSigned: function (sText) {
			if (IsPgpSupported())
			{
				return (sText.indexOf('-----BEGIN PGP MESSAGE-----') !== -1) || (sText.indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1);
			}
			return false;
		}
	};
};