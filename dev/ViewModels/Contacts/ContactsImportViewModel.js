
/**
 * @param {CContactsViewModel} oParent
 * @constructor
 */
function CContactsImportViewModel(oParent)
{
	this.oJua = null;
	this.oParent = oParent;

	this.visibility = ko.observable(false);
	this.importing = ko.observable(false);
}

/**
 * @param {Object} $oViewModel
 */
CContactsImportViewModel.prototype.onApplyBindings = function ($oViewModel)
{
	this.oJua = new Jua({
		'action': '?/Upload/Contacts/',
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
			}
		}
	});

	this.oJua
		.on('onStart', _.bind(this.onFileUploadStart, this))
		.on('onComplete', _.bind(this.onFileUploadComplete, this))
	;
};

CContactsImportViewModel.prototype.onFileUploadStart = function ()
{
	this.importing(true);
};

/**
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResponse
 */
CContactsImportViewModel.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResponse)
{
	var
		bError = !bResponseReceived || !oResponse || oResponse.Error|| oResponse.Result.Error || false,
		iImportedCount = 0
		;

	this.importing(false);
	this.oParent.requestContactList();

	if (!bError)
	{
		iImportedCount = Utils.pInt(oResponse.Result.ImportedCount);

		if (0 < iImportedCount)
		{
			App.Api.showReport(Utils.i18n('CONTACTS/CONTACT_IMPORT_HINT_PLURAL', {
				'NUM': iImportedCount
			}, null, iImportedCount));
		}
		else
		{
			App.Api.showError(Utils.i18n('WARNING/CONTACTS_IMPORT_NO_CONTACTS'));
		}
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