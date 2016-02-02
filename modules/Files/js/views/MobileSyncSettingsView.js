'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	App = require('core/js/App.js'),
	UserSettings = require('core/js/Settings.js')
;

/**
 * @constructor
 */
function CMobileSyncSettingsView()
{
	this.davServer = ko.observable('');
	this.credentialsHintText = ko.observable(TextUtils.i18n('SETTINGS/MOBILE_CREDENTIALS_TITLE', {'EMAIL': App.defaultAccountEmail()}));
	this.bDemo = UserSettings.IsDemo;
}

CMobileSyncSettingsView.prototype.ViewTemplate = 'Files_MobileSyncSettingsView';

/**
 * @param {Object} oDav
 */
CMobileSyncSettingsView.prototype.populate = function (oDav)
{
	this.davServer(oDav.Server);
};

module.exports = new CMobileSyncSettingsView();
