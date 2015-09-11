'use strict';

var
	ko = require('knockout'),
	$ = require('jquery'),
	
	TextUtils = require('core/js/utils/Text.js')
;

/**
 * @constructor
 */
function CConfirmPopup()
{
	this.fConfirmCallback = null;
	this.confirmDesc = ko.observable('');
	this.title = ko.observable('');
	this.okButtonText = ko.observable(TextUtils.i18n('MAIN/BUTTON_OK'));
	this.cancelButtonText = ko.observable(TextUtils.i18n('MAIN/BUTTON_CANCEL'));
	this.shown = false;
}

/**
 * @param {string} sDesc
 * @param {Function} fConfirmCallback
 * @param {string=} sTitle = ''
 * @param {string=} sOkButtonText = ''
 * @param {string=} sCancelButtonText = ''
 */
CConfirmPopup.prototype.onShow = function (sDesc, fConfirmCallback, sTitle, sOkButtonText, sCancelButtonText)
{
	this.confirmDesc(sDesc);
	this.title(sTitle || '');
	this.okButtonText(sOkButtonText || TextUtils.i18n('MAIN/BUTTON_OK'));
	this.cancelButtonText(sCancelButtonText || TextUtils.i18n('MAIN/BUTTON_CANCEL'));
	if ($.isFunction(fConfirmCallback))
	{
		this.fConfirmCallback = fConfirmCallback;
	}
	this.shown = true;
};

CConfirmPopup.prototype.onHide = function ()
{
	this.shown = false;
};

/**
 * @return {string}
 */
CConfirmPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ConfirmPopupView';
};

CConfirmPopup.prototype.onEnterHandler = function ()
{
	this.yesClick();
};

CConfirmPopup.prototype.yesClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(true);
	}

	this.closeCommand();
};

CConfirmPopup.prototype.noClick = function ()
{
	if (this.fConfirmCallback)
	{
		this.fConfirmCallback(false);
	}

	this.closeCommand();
};

CConfirmPopup.prototype.onEscHandler = function ()
{
	this.noClick();
};

module.exports = new CConfirmPopup();