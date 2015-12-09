'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CAlertPopup()
{
	CAbstractPopup.call(this);
	
	this.alertDesc = ko.observable('');
	this.closeCallback = null;
	this.title = ko.observable('');
	this.okButtonText = ko.observable(TextUtils.i18n('MAIN/BUTTON_OK'));
}

_.extendOwn(CAlertPopup.prototype, CAbstractPopup.prototype);

CAlertPopup.prototype.PopupTemplate = 'Core_AlertPopup';

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

CAlertPopup.prototype.onEnterHandler = function ()
{
	this.closePopup();
};

CAlertPopup.prototype.cancelPopup = function ()
{
	if ($.isFunction(this.closeCallback))
	{
		this.closeCallback();
	}
	this.closePopup();
};

module.exports = new CAlertPopup();
