'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	
	Popups = require('core/js/Popups.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	AlertPopup = require('core/js/popups/AlertPopup.js')
;

/**
 * @constructor
 */
function CEditCalendarPopup()
{
	CAbstractPopup.call(this);
	
	this.fCallback = null;
	
	this.calendarId = ko.observable(null);
	this.calendarName = ko.observable('');
	this.calendarDescription = ko.observable('');
	
	this.calendarNameFocus = ko.observable(false);
	this.calendarDescriptionFocus = ko.observable(false);
	
	this.colors = ko.observableArray([]);
	this.selectedColor = ko.observable(this.colors()[0]);
	
	this.popupTitle = ko.observable('');
}

_.extendOwn(CEditCalendarPopup.prototype, CAbstractPopup.prototype);

CEditCalendarPopup.prototype.PopupTemplate = 'Calendar_EditCalendarPopup';

CEditCalendarPopup.prototype.clearFields = function ()
{
	this.calendarName('');
	this.calendarDescription('');
	this.selectedColor(this.colors[0]);
	this.calendarId(null);
};

/**
 * @param {Function} fCallback
 * @param {Array} aColors
 * @param {Object} oCalendar
 */
CEditCalendarPopup.prototype.onShow = function (fCallback, aColors, oCalendar)
{
	this.clearFields();
	
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
	
	if (!Utils.isUnd(aColors))
	{
		this.colors(aColors);
		this.selectedColor(aColors[0]);		
	}
	
	if (!Utils.isUnd(oCalendar))
	{
		this.popupTitle(oCalendar.name() ? TextUtils.i18n("CALENDAR/TITLE_EDIT_CALENDAR") : TextUtils.i18n("CALENDAR/TITLE_CREATE_CALENDAR"));
		this.calendarName(oCalendar.name ? oCalendar.name() : '');
		this.calendarDescription(oCalendar.description ? oCalendar.description() : '');
		this.selectedColor(oCalendar.color ? oCalendar.color() : '');
		this.calendarId(oCalendar.id ? oCalendar.id : null);
	}
	else
	{
		this.popupTitle(TextUtils.i18n("CALENDAR/TITLE_CREATE_CALENDAR"));
	}

//	$(document).on('keyup.calendar_create', _.bind(function(ev) {
//		if (ev.keyCode === Enums.Key.Enter)
//		{
//			this.onSaveClick();
//		}
//	}, this));
};

CEditCalendarPopup.prototype.onHide = function (fCallback, aColors, oCalendar)
{
//	$(document).off('keyup.calendar_create');
};

CEditCalendarPopup.prototype.onSaveClick = function ()
{
	if (this.calendarName() === '')
	{
		Popups.showPopup(AlertPopup, [TextUtils.i18n('CALENDAR/WARNING_BLANK_CALENDAR_NAME')]);
	}
	else
	{
		if (this.fCallback)
		{
			this.fCallback(this.calendarName(), this.calendarDescription(), this.selectedColor(), this.calendarId());
			this.clearFields();
		}
		this.closePopup();
	}
};

module.exports = new CEditCalendarPopup();