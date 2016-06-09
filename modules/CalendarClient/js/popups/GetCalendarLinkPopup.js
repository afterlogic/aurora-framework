'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Utils = require('modules/CoreClient/js/utils/Common.js'),
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CGetCalendarLinkPopup()
{
	CAbstractPopup.call(this);
	
	this.fCallback = null;

	this.calendarId = ko.observable(null);
	this.selectedColor = ko.observable('');
	this.calendarUrl = ko.observable('');
	this.exportUrl = ko.observable('');
	this.icsLink = ko.observable('');
	this.isPublic = ko.observable(false);
	this.pubUrl = ko.observable('');
}

_.extendOwn(CGetCalendarLinkPopup.prototype, CAbstractPopup.prototype);

CGetCalendarLinkPopup.prototype.PopupTemplate = '%ModuleName%_GetCalendarLinkPopup';

/**
 * @param {Function} fCallback
 * @param {Object} oCalendar
 */
CGetCalendarLinkPopup.prototype.onShow = function (fCallback, oCalendar)
{
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
	if (oCalendar)
	{
		this.selectedColor(oCalendar.color());
		this.calendarId(oCalendar.id);
		this.calendarUrl(oCalendar.davUrl() + oCalendar.url());
		this.exportUrl(oCalendar.exportUrl());
		this.icsLink(oCalendar.davUrl() + oCalendar.url() + '?export');
		this.isPublic(oCalendar.isPublic());
		this.pubUrl(oCalendar.pubUrl());
		this.exportUrl(oCalendar.exportUrl());
	}
};

CGetCalendarLinkPopup.prototype.cancelPopup = function ()
{
	if (this.fCallback)
	{
		this.fCallback(this.calendarId(), this.isPublic());
	}
	this.closePopup();
};

module.exports = new CGetCalendarLinkPopup();