'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
	
	Popups = require('core/js/Popups.js'),
	AlertPopup = require('core/js/popups/AlertPopup.js'),
	
	Settings = require('modules/Mail/js/Settings.js'),
	
	ErrorUtils = {}
;
/**
 * @param {string} sFileName
 * @param {number} iSize
 * @returns {Boolean}
 */
ErrorUtils.showErrorIfAttachmentSizeLimit = function (sFileName, iSize)
{
	var
		sWarning = TextUtils.i18n('COMPOSE/UPLOAD_ERROR_FILENAME_SIZE', {
			'FILENAME': sFileName,
			'MAXSIZE': TextUtils.getFriendlySize(Settings.AttachmentSizeLimit)
		})
	;
	
	if (Settings.AttachmentSizeLimit > 0 && iSize > Settings.AttachmentSizeLimit)
	{
		Popups.showPopup(AlertPopup, [sWarning]);
		return true;
	}
	
	return false;
};

module.exports = ErrorUtils;