/**
 * @constructor
 */
function CalendarGetLinkPopup()
{
	this.fCallback = null;

	this.calendarId = ko.observable(null);
	this.selectedColor = ko.observable('');
	this.calendarUrl = ko.observable('');
	this.exportUrl = ko.observable('');
	this.icsLink = ko.observable('');
	this.isPublic = ko.observable(false);
	this.pubUrl = ko.observable('');
}

/**
 * @param {Function} fCallback
 * @param {Object} oCalendar
 */
CalendarGetLinkPopup.prototype.onShow = function (fCallback, oCalendar)
{
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
	if (!Utils.isUnd(oCalendar))
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

/**
 * @return {string}
 */
CalendarGetLinkPopup.prototype.popupTemplate = function ()
{
	return 'Popups_Calendar_GetLinkPopupViewModel';
};

CalendarGetLinkPopup.prototype.onCancelClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(this.calendarId(), this.isPublic());
	}
	this.closeCommand();
};

CalendarGetLinkPopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};
