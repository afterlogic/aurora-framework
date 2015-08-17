
/**
 * @constructor
 */
function CIcalModel()
{
	this.uid = ko.observable('');
	this.lastModification = ko.observable(true);
	this.sSequence = 1;
	this.file = ko.observable('');
	this.attendee = ko.observable('');
	
	this.type = ko.observable('');
	this.icalType = ko.observable('');
	this.icalConfig = ko.observable('');
	this.type.subscribe(function () {
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
	
	this.location = ko.observable('');
	this.description = ko.observable('');
	this.when = ko.observable('');
	
	this.calendarId = ko.observable('');
	this.calendars = ko.observableArray([]);
	if (AppData.SingleMode && window.opener)
	{
		this.calendars(window.opener.App.CalendarCache.calendars());
		window.opener.App.CalendarCache.calendars.subscribe(function () {
			this.calendars(window.opener.App.CalendarCache.calendars());
		}, this);
	}
	else
	{
		this.calendars(App.CalendarCache.calendars());
		App.CalendarCache.calendars.subscribe(function () {
			this.calendars(App.CalendarCache.calendars());
		}, this);
	}

	this.selectedCalendarId = ko.observable('');

	this.chosenCalendarName = ko.computed(function () {
		var oFoundCal = null;

		if (this.calendarId() !== '') {
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
}

CIcalModel.prototype.fillDecisions = function ()
{
	var
		oAccount = AppData.Accounts.getCurrent(),
		sSender = oAccount ? oAccount.email() : ''
	;
	
	this.cancelDecision(Utils.i18n('MESSAGE/APPOINTMENT_CANCELED', {'SENDER': sSender}));
	
	switch (this.icalConfig())
	{
		case Enums.IcalConfig.Accepted:
			this.replyDecision(Utils.i18n('MESSAGE/APPOINTMENT_ACCEPTED', {'ATTENDEE': this.attendee()}));
			break;
		case Enums.IcalConfig.Declined:
			this.replyDecision(Utils.i18n('MESSAGE/APPOINTMENT_DECLINED', {'ATTENDEE': this.attendee()}));
			break;
		case Enums.IcalConfig.Tentative:
			this.replyDecision(Utils.i18n('MESSAGE/APPOINTMENT_TENTATIVELY_ACCEPTED', {'ATTENDEE': this.attendee()}));
			break;
	}
};

/**
 * @param {AjaxIcsResponse} oData
 * @param {string} sAttendee
 */
CIcalModel.prototype.parse = function (oData, sAttendee)
{
	var sDescription = '';
	
	if (oData && oData['@Object'] === 'Object/CApiMailIcs')
	{
		sDescription = Utils.pString(oData.Description);
		this.uid(Utils.pString(oData.Uid));
		this.sSequence = Utils.pInt(oData.Sequence);
		this.file(Utils.pString(oData.File));
		this.attendee(Utils.pString(oData.Attendee) || sAttendee);
		this.type(oData.Type);
		this.location(Utils.pString(oData.Location));
		this.description(sDescription.replace(/\r/g, '').replace(/\n/g,"<br />"));
		this.when(Utils.pString(oData.When));
		this.calendarId(Utils.pString(oData.CalendarId));
		this.selectedCalendarId(Utils.pString(oData.CalendarId));
		
		App.CalendarCache.addIcal(this);
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
		if (this.icalConfig() !== sConfig &&
			(sConfig !== Enums.IcalConfig.Declined || this.icalConfig() !== Enums.IcalConfig.NeedsAction)) 
		{
			App.CalendarCache.recivedAnim(true);
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
	if (AppData.SingleMode && window.opener)
	{
		window.opener.App.CalendarCache.markIcalTypeByFile(this.file(), this.type(), this.cancelDecision(),
									this.replyDecision(), this.calendarId(), this.selectedCalendarId());
	}
	else
	{
		App.CalendarCache.markIcalTypeByFile(this.file(), this.type(), this.cancelDecision(),
									this.replyDecision(), this.calendarId(), this.selectedCalendarId());
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIcalModel.prototype.onCalendarAppointmentSetActionResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/UNKNOWN_ERROR'));
	}
	else if (App.CalendarCache)
	{
		App.CalendarCache.calendarChanged(true);
	}
};

CIcalModel.prototype.setAppointmentAction = function ()
{
	var
		oParameters = {
			'Action': 'CalendarAppointmentSetAction',
			'AppointmentAction': this.icalConfig(),
			'CalendarId': this.selectedCalendarId(),
			'File': this.file(),
			'Attendee': this.attendee()
		}
	;

	App.Ajax.send(oParameters, this.onCalendarAppointmentSetActionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIcalModel.prototype.onCalendarSaveIcsResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse);
	}
	else
	{
		if (oResponse.Result.Uid)
		{
			this.uid(oResponse.Result.Uid);
		}
		if (App.CalendarCache)
		{
			App.CalendarCache.calendarChanged(true);
		}
	}
};

CIcalModel.prototype.addEvent = function ()
{
	var
		oParameters = {
			'Action': 'CalendarSaveIcs',
			'CalendarId': this.selectedCalendarId(),
			'File': this.file()
		}
	;
	
	App.Ajax.send(oParameters, this.onCalendarSaveIcsResponse, this);
	
	this.isJustSaved(true);
	this.calendarId(this.selectedCalendarId());
	
	setTimeout(_.bind(function () {
		this.isJustSaved(false);
	}, this), 20000);
	
	App.CalendarCache.recivedAnim(true);
};

CIcalModel.prototype.onEventDelete = function ()
{
	this.calendarId('');
	this.selectedCalendarId('');
	this.changeConfig(Enums.IcalConfig.NeedsAction);
};

CIcalModel.prototype.onEventTentative = function ()
{
	this.changeConfig(Enums.IcalConfig.Tentative);
};

CIcalModel.prototype.onEventAccept = function ()
{
	this.changeConfig(Enums.IcalConfig.Accepted);
};

CIcalModel.prototype.firstCalendarName = function ()
{
	return this.calendars()[0] ? this.calendars()[0].name : '';
};

/**
 * @param {string} sEmail
 */
CIcalModel.prototype.updateAttendeeStatus = function (sEmail)
{
	if (this.icalType() === Enums.IcalType.Cancel || this.icalType() === Enums.IcalType.Reply)
	{
		var
			oParameters = {
				'Action': 'CalendarAttendeeUpdateStatus',
				'File': this.file(),
				'FromEmail': sEmail
			}
		;

		App.Ajax.send(oParameters, this.onCalendarAttendeeUpdateStatusResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CIcalModel.prototype.onCalendarAttendeeUpdateStatusResponse = function (oResponse, oRequest)
{
	if (oResponse.Result && App.CalendarCache)
	{
		App.CalendarCache.recivedAnim(true);
		App.CalendarCache.calendarChanged(true);
	}
};
