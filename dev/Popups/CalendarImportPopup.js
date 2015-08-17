/**
 * @constructor
 */
function CalendarImportPopup()
{
	this.fCallback = null;
	
	this.oJua = null;
	this.allowDragNDrop = ko.observable(false);
	
	this.visibility = ko.observable(false);
	this.importing = ko.observable(false);

	this.color	= ko.observable('');
	this.calendarId	= ko.observable('');
}

/**
 * @param {Function} fCallback
 * @param {Object} oCalendar
 */
CalendarImportPopup.prototype.onShow = function (fCallback, oCalendar)
{
	if (Utils.isFunc(fCallback))
	{
		this.fCallback = fCallback;
	}
	if (!Utils.isUnd(oCalendar))
	{
		this.color(oCalendar.color ? oCalendar.color() : '');
		this.calendarId(oCalendar.id ? oCalendar.id : '');
	}
};

/**
 * @return {string}
 */
CalendarImportPopup.prototype.popupTemplate = function ()
{
	return 'Popups_Calendar_ImportPopupViewModel';
};

/**
 * @param {Object} $oViewModel
 */
CalendarImportPopup.prototype.onApplyBindings = function ($oViewModel)
{
	var self = this;
	this.oJua = new Jua({
		'action': '?/Upload/Calendars/',
		'name': 'jua-uploader',
		'queueSize': 1,
		'clickElement': $('#jue_import_button', $oViewModel),
		'hiddenElementsPosition': Utils.isRTL() ? 'right' : 'left',
		'disableAjaxUpload': false,
		'disableDragAndDrop': true,
		'disableMultiple': true,
		'hidden': {
			'Token': function () {
				return AppData.Token;
			},
			'AccountID': function () {
				return AppData.Accounts.currentId();
			},
			'AdditionalData':  function () {
				return JSON.stringify({
					'CalendarID': self.calendarId()
				});
			}
		}
	});

	this.oJua
		.on('onStart', _.bind(this.onFileUploadStart, this))
		.on('onComplete', _.bind(this.onFileUploadComplete, this))
	;
	
	this.allowDragNDrop(this.oJua.isDragAndDropSupported());
};

CalendarImportPopup.prototype.onFileUploadStart = function ()
{
	this.importing(true);
};

/**
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResponse
 */
CalendarImportPopup.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResponse)
{
	var bError = !bResponseReceived || !oResponse || oResponse.Error|| oResponse.Result.Error || false;

	if (!bError)
	{
		this.importing(false);
		this.fCallback();
		this.closeCommand();
	}
	else
	{
		if (oResponse.ErrorCode && oResponse.ErrorCode === Enums.Errors.IncorrectFileExtension)
		{
			App.Api.showError(Utils.i18n('CONTACTS/ERROR_INCORRECT_FILE_EXTENSION'));
		}
		else
		{
			App.Api.showError(Utils.i18n('WARNING/ERROR_UPLOAD_FILE'));
		}
	}
};

CalendarImportPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};