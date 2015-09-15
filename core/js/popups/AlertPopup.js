'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
	
	TextUtils = require('core/js/utils/Text.js')
;

/**
 * @constructor
 */
function CAlertPopup()
{
	this.alertDesc = ko.observable('');
	this.closeCallback = null;
	this.title = ko.observable('');
	this.okButtonText = ko.observable(TextUtils.i18n('MAIN/BUTTON_OK'));
}

/**
 * @param {string} sDesc
 * @param {Function=} fCloseCallback = null
 * @param {string=} sTitle = ''
 * @param {string=} sOkButtonText = 'Ok'
 */
CAlertPopup.prototype.onShow = function (sDesc, fCloseCallback, sTitle, sOkButtonText)
{
	this.alertDesc(sDesc);
	this.closeCallback = fCloseCallback || null;
	this.title(sTitle || '');
	this.okButtonText(sOkButtonText || TextUtils.i18n('MAIN/BUTTON_OK'));
};

/**
 * @return {string}
 */
CAlertPopup.prototype.popupTemplate = function ()
{
	return 'Popups_AlertPopupViewModel';
};

CAlertPopup.prototype.onEnterHandler = function ()
{
	this.close();
};

CAlertPopup.prototype.close = function ()
{
	if ($.isFunction(this.closeCallback))
	{
		this.closeCallback();
	}
	this.closeCommand();
};

module.exports = new CAlertPopup();