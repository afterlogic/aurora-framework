'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	App = require('core/js/App.js'),
	Api = require('core/js/Api.js'),
	
	Ajax = require('modules/Calendar/js/Ajax.js'),
	CalendarCache = require('modules/Calendar/js/Cache.js'),
	HeaderItemView = App.isNewTab() ? require('modules/Calendar/js/views/HeaderItemView.js') : null,
	
	BaseTab = App.isNewTab() && window.opener ? window.opener.BaseTabCalendarMethods : null
;

/**
 * @constructor
 * @param {Object} oRawIcal
 * @param {string} sAttendee
 */
function CIcalModel(oRawIcal, sAttendee)
{
	this.oRawIcal = oRawIcal;
	
	this.uid = ko.observable(Utils.pString(oRawIcal.Uid));
	this.lastModification = ko.observable(true);
	this.sSequence = Utils.pInt(oRawIcal.Sequence);
	this.file = ko.observable(Utils.pString(oRawIcal.File));
	this.attendee = ko.observable(Utils.pString(oRawIcal.Attendee) || sAttendee);
	this.type = ko.observable(Utils.pString(oRawIcal.Type));
	this.location = ko.observable(Utils.pString(oRawIcal.Location));
	this.description = ko.observable(Utils.pString(oRawIcal.Description).replace(/\r/g, '').replace(/\n/g,"<br />"));
	this.when = ko.observable(Utils.pString(oRawIcal.When));
	this.calendarId = ko.observable(Utils.pString(oRawIcal.CalendarId));
	this.selectedCalendarId = ko.observable(Utils.pString(oRawIcal.CalendarId));
	CalendarCache.addIcal(this);
	
	this.icalType = ko.observable('');
	this.icalConfig = ko.observable('');
	this.type.subscribe(function () {
		this.parseType();
	}, this);
	
	this.isRequestType = ko.computed(function () {
		return this.icalType() === Enums.IcalType.Request;
	}, this);
	this.isCancelType = ko.computed(function () {
		return this.icalType() === Enums.IcalType.Cancel;
	}, this);
	this.cancelDecision = ko.observable('');
	this.isReplyType = ko.computed(function () {
		return this.icalType() === Enums.IcalType.Reply;
	}, this);
	this.replyDecision = ko.observable('');
	this.isSaveType = ko.computed(function () {
		return this.icalType() === Enums.IcalType.Save;
	}, this);
	this.isJustSaved = ko.observable(false);
	
	this.isAccepted = ko.computed(function () {
		return this.icalConfig() === Enums.IcalConfig.Accepted;
	}, this);
	this.isDeclined = ko.computed(function () {
		return this.icalConfig() === Enums.IcalConfig.Declined;
	}, this);
	this.isTentative = ko.computed(function () {
		return this.icalConfig() === Enums.IcalConfig.Tentative;
	}, this);
	this.calendars = ko.observableArray(CalendarCache.calendars());
	
	if (this.calendars().length === 0)
	{
		var fCalSubscription = CalendarCache.calendars.subscribe(function () {
			this.calendars(CalendarCache.calendars());
			fCalSubscription.dispose();
		}, this);
	}

	this.chosenCalendarName = ko.computed(function () {
		var oFoundCal = null;

		if (this.calendarId() !== '')
		{
			oFoundCal = _.find(this.calendars(), function (oCal) {
				return oCal.id === this.calendarId();
			}, this);
		}
		
		return oFoundCal ? oFoundCal.name : '';
	}, this);
	
	this.calendarIsChosen = ko.computed(function () {
		return this.chosenCalendarName() !== '';
	}, this);
	
	this.visibleCalendarDropdown = ko.computed(function () {
		return !this.calendarIsChosen() && this.calendars().length > 1 && (this.isRequestType() || this.isSaveType());
	}, this);
	
	this.visibleCalendarName = ko.computed(function () {
		return this.calendarIsChosen();
	}, this);
	
	this.firstCalendarName = function () {
		return this.calendars()[0] ? this.calendars()[0].name : '';
	},
	
	this.visibleFirstCalendarName = ko.computed(function () {
		return this.calendars().length === 1 && !this.calendarIsChosen();
	}, this);
	
	this.visibleCalendarRow = ko.computed(function () {
		return this.attendee() !== '' && (this.visibleCalendarDropdown() || this.visibleCalendarName() || this.visibleFirstCalendarName());
	}, this);
	
	this.visibleRequestButtons = ko.computed(function () {
		return this.isRequestType() && this.attendee() !== '';
	}, this);
	
	// animation of buttons turns on with delay
	// so it does not trigger when placing initial values
	this.animation = ko.observable(false);
	
	this.parseType();
}

CIcalModel.prototype.parseType = function ()
{
	var
		aTypeParts = this.type().split('-'),
		sType = aTypeParts.shift(),
		sFoundType = _.find(Enums.IcalType, function (sIcalType) {
			return sType === sIcalType;
		}, this),
		sConfig = aTypeParts.join('-'),
		sFoundConfig = _.find(Enums.IcalConfig, function (sIcalConfig) {
			return sConfig === sIcalConfig;
		}, this)
	;

	if (sType !== sFoundType)
	{
		sType = Enums.IcalType.Save;
	}
	this.icalType(sType);

	if (sConfig !== sFoundConfig)
	{
		sConfig = Enums.IcalConfig.NeedsAction;
	}
	this.icalConfig(sConfig);

	this.fillDecisions();
};

CIcalModel.prototype.fillDecisions = function ()
{
	this.cancelDecision(TextUtils.i18n('MESSAGE/APPOINTMENT_CANCELED', {'SENDER': App.currentAccountEmail()}));
	
	switch (this.icalConfig())
	{
		case Enums.IcalConfig.Accepted:
			this.replyDecision(TextUtils.i18n('MESSAGE/APPOINTMENT_ACCEPTED', {'ATTENDEE': this.attendee()}));
			break;
		case Enums.IcalConfig.Declined:
			this.replyDecision(TextUtils.i18n('MESSAGE/APPOINTMENT_DECLINED', {'ATTENDEE': this.attendee()}));
			break;
		case Enums.IcalConfig.Tentative:
			this.replyDecision(TextUtils.i18n('MESSAGE/APPOINTMENT_TENTATIVELY_ACCEPTED', {'ATTENDEE': this.attendee()}));
			break;
	}
};

CIcalModel.prototype.acceptAppointment = function ()
{
	this.calendarId(this.selectedCalendarId());
	this.changeAndSaveConfig(Enums.IcalConfig.Accepted);
};

CIcalModel.prototype.tentativeAppointment = function ()
{
	this.calendarId(this.selectedCalendarId());
	this.changeAndSaveConfig(Enums.IcalConfig.Tentative);
};

CIcalModel.prototype.declineAppointment = function ()
{
	this.calendarId('');
	this.selectedCalendarId('');
	this.changeAndSaveConfig(Enums.IcalConfig.Declined);
};

/**
 * @param {string} sConfig
 */
CIcalModel.prototype.changeAndSaveConfig = function (sConfig)
{
	if (this.icalConfig() !== sConfig)
	{
		if (sConfig !== Enums.IcalConfig.Declined || this.icalConfig() !== Enums.IcalConfig.NeedsAction)
		{
			this.showChanges();
		}

		this.changeConfig(sConfig);
		this.setAppointmentAction();
	}
};

/**
 * @param {string} sConfig
 */
CIcalModel.prototype.changeConfig = function (sConfig)
{
	this.type(this.icalType() + '-' + sConfig);
	if (BaseTab)
	{
		BaseTab.markIcalTypeByFile(this.file(), this.type(), this.cancelDecision(),
									this.replyDecision(), this.calendarId(), this.selectedCalendarId());
	}
	else
	{
		CalendarCache.markIcalTypeByFile(this.file(), this.type(), this.cancelDecision(),
									this.replyDecision(), this.calendarId(), this.selectedCalendarId());
	}
};

CIcalModel.prototype.markNeededAction = function ()
{
	this.calendarId('');
	this.selectedCalendarId('');
	this.changeConfig(Enums.IcalConfig.NeedsAction);
};

CIcalModel.prototype.markTentative = function ()
{
	this.changeConfig(Enums.IcalConfig.Tentative);
};

CIcalModel.prototype.markAccepted = function ()
{
	this.changeConfig(Enums.IcalConfig.Accepted);
};

CIcalModel.prototype.setAppointmentAction = function ()
{
	Ajax.send('SetAppointmentAction', {
		'AppointmentAction': this.icalConfig(),
		'CalendarId': this.selectedCalendarId(),
		'File': this.file(),
		'Attendee': this.attendee()
	}, this.onSetAppointmentActionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIcalModel.prototype.onSetAppointmentActionResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
	else
	{
		this.markChanges();
	}
};

CIcalModel.prototype.addEvents = function ()
{
	Ajax.send('AddEventsFromFile', {
		'CalendarId': this.selectedCalendarId(),
		'File': this.file()
	}, this.onAddEventsFromFileResponse, this);
	
	this.isJustSaved(true);
	this.calendarId(this.selectedCalendarId());
	
	setTimeout(_.bind(function () {
		this.isJustSaved(false);
	}, this), 20000);
	
	this.showChanges();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIcalModel.prototype.onAddEventsFromFileResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		if (oResponse.Result.Uid)
		{
			this.uid(oResponse.Result.Uid);
		}
		this.markChanges();
	}
};

/**
 * @param {string} sEmail
 */
CIcalModel.prototype.updateAttendeeStatus = function (sEmail)
{
	if (this.icalType() === Enums.IcalType.Cancel || this.icalType() === Enums.IcalType.Reply)
	{
		Ajax.send('UpdateAttendeeStatus', {
			'File': this.file(),
			'FromEmail': sEmail
		}, this.onUpdateAttendeeStatusResponse, this);
		
		this.showChanges();
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIcalModel.prototype.onUpdateAttendeeStatusResponse = function (oResponse, oRequest)
{
	if (oResponse.Result)
	{
		this.markChanges();
	}
};

CIcalModel.prototype.showChanges = function ()
{
	if (HeaderItemView)
	{
		HeaderItemView.recivedAnim(true);
	}
};

CIcalModel.prototype.markChanges = function ()
{
	if (BaseTab)
	{
		BaseTab.markCalendarChanged();
	}
	else
	{
		CalendarCache.calendarChanged(true);
	}
};

module.exports = CIcalModel;