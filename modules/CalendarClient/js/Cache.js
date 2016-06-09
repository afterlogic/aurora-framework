'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	App = require('modules/CoreClient/js/App.js'),
	
	Ajax = require('modules/%ModuleName%/js/Ajax.js')
;

/**
 * @constructor
 */
function CCalendarCache()
{
	// uses only for ical-attachments
	this.calendars = ko.observableArray([]);
	this.calendarsLoadingStarted = ko.observable(false);
	
	this.icalAttachments = [];
	
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	
	this.calendarSettingsChanged = ko.observable(false);
	this.calendarChanged = ko.observable(false);
}

/**
 * @param {Object} oIcal
 */
CCalendarCache.prototype.addIcal = function (oIcal)
{
	_.each(this.icalAttachments, function (oIcalItem) {
		if (oIcalItem.uid() === oIcal.uid())
		{
			if (oIcal.sSequence >= oIcalItem.sSequence)
			{
				oIcalItem.lastModification(false);
			}
			else
			{
				oIcal.lastModification(false);
			}
		}
	});
	this.icalAttachments.push(oIcal);
	if (this.calendars().length === 0)
	{
		this.requestCalendarList();
	}
};

/**
 * @param {string} sFile
 */
CCalendarCache.prototype.getIcal = function (sFile)
{
	return _.find(this.icalAttachments, function (oIcal) {
		return (sFile === oIcal.file());
	});
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCalendarCache.prototype.onGetCalendarsResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result && oResponse.Result.Calendars)
	{
		var
			sCurrentEmail = App.currentAccountEmail ? App.currentAccountEmail() : '',
			aEditableCalendars = _.filter(oResponse.Result.Calendars, function (oCalendar) {
				return oCalendar.Owner === sCurrentEmail ||
					oCalendar.Access === Enums.CalendarAccess.Full ||
					oCalendar.Access === Enums.CalendarAccess.Write;
			})
		;
		this.calendars(_.map(aEditableCalendars, function (oCalendar) {
			return {'name': oCalendar.Name + ' <' + oCalendar.Owner + '>', 'id': oCalendar.Id};
		}));
	}
	
	this.calendarsLoadingStarted(false);
};

CCalendarCache.prototype.requestCalendarList = function ()
{
	if (!this.calendarsLoadingStarted())
	{
		Ajax.send('GetCalendars', null, this.onGetCalendarsResponse, this);
		
		this.calendarsLoadingStarted(true);
	}
};

/**
 * @param {string} sFile
 * @param {string} sType
 * @param {string} sCancelDecision
 * @param {string} sReplyDecision
 * @param {string} sCalendarId
 * @param {string} sSelectedCalendar
 */
CCalendarCache.prototype.markIcalTypeByFile = function (sFile, sType, sCancelDecision, sReplyDecision,
														sCalendarId, sSelectedCalendar)
{
	_.each(this.icalAttachments, function (oIcal) {
		if (sFile === oIcal.file())
		{
			oIcal.type(sType);
			oIcal.cancelDecision(sCancelDecision);
			oIcal.replyDecision(sReplyDecision);
			oIcal.calendarId(sCalendarId);
			oIcal.selectedCalendarId(sSelectedCalendar);
		}
	});
};

/**
 * @param {string} sUid
 */
CCalendarCache.prototype.markIcalNonexistent = function (sUid)
{
	_.each(this.icalAttachments, function (oIcal) {
		if (sUid === oIcal.uid())
		{
			oIcal.markNeededAction();
		}
	});
};

/**
 * @param {string} sUid
 */
CCalendarCache.prototype.markIcalNotSaved = function (sUid)
{
	_.each(this.icalAttachments, function (oIcal) {
		if (sUid === oIcal.uid())
		{
			oIcal.markNotSaved();
		}
	});
};

/**
 * @param {string} sUid
 */
CCalendarCache.prototype.markIcalTentative = function (sUid)
{
	_.each(this.icalAttachments, function (oIcal) {
		if (sUid === oIcal.uid())
		{
			oIcal.markTentative();
		}
	});
};

/**
 * @param {string} sUid
 */
CCalendarCache.prototype.markIcalAccepted = function (sUid)
{
	_.each(this.icalAttachments, function (oIcal) {
		if (sUid === oIcal.uid())
		{
			oIcal.markAccepted();
		}
	});
};

module.exports = new CCalendarCache();