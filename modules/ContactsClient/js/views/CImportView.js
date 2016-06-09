'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Types = require('modules/CoreClient/js/utils/Types.js'),
	
	App = require('modules/CoreClient/js/App.js'),
	CJua = require('modules/CoreClient/js/CJua.js'),
	UserSettings = require('modules/CoreClient/js/Settings.js'),
	Screens = require('modules/CoreClient/js/Screens.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
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

CImportView.prototype.ViewTemplate = '%ModuleName%_ImportView';

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
		'hidden': _.extendOwn({
			'Module': Settings.ServerModuleName,
			'Method': 'UploadContacts'
		}, App.getCommonRequestParameters())
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
		iImportedCount = Types.pInt(oResponse.Result.ImportedCount);

		if (0 < iImportedCount)
		{
			Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_CONTACTS_IMPORTED_PLURAL', {
				'NUM': iImportedCount
			}, null, iImportedCount));
		}
		else
		{
			Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_IMPORT_NO_CONTACT'));
		}
	}
	else
	{
		if (oResponse.ErrorCode && oResponse.ErrorCode === Enums.Errors.IncorrectFileExtension)
		{
			Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_FILE_NOT_CSV_OR_VCF'));
		}
		else
		{
			Screens.showError(TextUtils.i18n('CORE/ERROR_UPLOAD_FILE'));
		}
	}
};

module.exports = CImportView;
