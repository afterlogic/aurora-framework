'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CMobileSyncSettingsView()
{
	this.bVisiblePersonalContacts = -1 !== $.inArray('personal', Settings.Storages);
	this.bVisibleSharedWithAllContacts = -1 !== $.inArray('shared', Settings.Storages);
	this.bVisibleGlobalContacts = -1 !== $.inArray('global', Settings.Storages);

	this.davPersonalContactsUrl = ko.observable('');
	this.davCollectedAddressesUrl = ko.observable('');
	this.davSharedWithAllUrl = ko.observable('');
	this.davGlobalAddressBookUrl = ko.observable('');
}

CMobileSyncSettingsView.prototype.ViewTemplate = 'Contacts_MobileSyncSettingsView';

/**
 * @param {Object} oDav
 */
CMobileSyncSettingsView.prototype.populate = function (oDav)
{
	this.davPersonalContactsUrl(oDav.PersonalContactsUrl);
	this.davCollectedAddressesUrl(oDav.CollectedAddressesUrl);
	this.davSharedWithAllUrl(oDav.SharedWithAllUrl);
	this.davGlobalAddressBookUrl(oDav.GlobalAddressBookUrl);
};

module.exports = new CMobileSyncSettingsView();
