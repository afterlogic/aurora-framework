'use strict';

var ko = require('knockout');

/**
 * @constructor
 */
function CMobileSyncSettingsView()
{
	this.davCalendars = ko.observable([]);
	this.visible = ko.computed(function () {
		return this.davCalendars().length > 0;
	}, this);
}

CMobileSyncSettingsView.prototype.ViewTemplate = '%ModuleName%_MobileSyncSettingsView';

/**
 * @param {Object} oDav
 */
CMobileSyncSettingsView.prototype.populate = function (oDav)
{
	this.davCalendars(oDav.Calendars);
};

module.exports = new CMobileSyncSettingsView();
