'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	App = require('core/js/App.js'),
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	UserSettings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js'),
	CJua = require('core/js/CJua.js')
;

/**
 * @param {CContactsViewModel} oParent
 * @constructor
 */
function CImportView(oParent)
{
	this.oJua = null;
	this.oParent = oParent;

	this.visibility = ko.observable(false);
	this.importing = ko.observable(false);
	
	this.importButtonDom = ko.observable(null);
	
	this.bVisibleCloseButton = App.isMobile();
}

CImportView.prototype.ViewTemplate = 'Contacts_ImportView';

CImportView.prototype.onBind = function ()
{
	this.oJua = new CJua({
		'action': '?/Upload/',
		'name': 'jua-uploader',
		'queueSize': 1,
		'clickElement': this.importButtonDom(),
		'hiddenElementsPosition': UserSettings.IsRTL ? 'right' : 'left',
		'disableAjaxUpload': false,
		'disableDragAndDrop': true,
		'disableMultiple': true,
		'hidden': {
			'Module': 'Contacts',
			'Method': 'UploadContacts',
			'Token': UserSettings.CsrfToken,
			'AccountID': App.defaultAccountId()
		}
	});

	this.oJua
		.on('onStart', _.bind(this.onFileUploadStart, this))
		.on('onComplete', _.bind(this.onFileUploadComplete, this))
	;
};

CImportView.prototype.onFileUploadStart = function ()
{
	this.importing(true);
};

/**
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResponse
 */
CImportView.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResponse)
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
			Screens.showReport(TextUtils.i18n('CONTACTS/CONTACT_IMPORT_HINT_PLURAL', {
				'NUM': iImportedCount
			}, null, iImportedCount));
		}
		else
		{
			Screens.showError(TextUtils.i18n('WARNING/CONTACTS_IMPORT_NO_CONTACTS'));
		}
	}
	else
	{
		if (oResponse.ErrorCode && oResponse.ErrorCode === Enums.Errors.IncorrectFileExtension)
		{
			Screens.showError(TextUtils.i18n('CONTACTS/ERROR_INCORRECT_FILE_EXTENSION'));
		}
		else
		{
			Screens.showError(TextUtils.i18n('WARNING/ERROR_UPLOAD_FILE'));
		}
	}
};

module.exports = CImportView;