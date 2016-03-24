'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CAlertPopup()
{
	CAbstractPopup.call(this);
	
	this.alertDesc = ko.observable('');
	this.closeCallback = null;
	this.popupHeading = ko.observable('');
	this.okButtonText = ko.observable(TextUtils.i18n('CORE/ACTION_OK'));
}

_.extendOwn(CAlertPopup.prototype, CAbstractPopup.prototype);

CAlertPopup.prototype.PopupTemplate = 'Core_AlertPopup';

/**
 * @param {string} sDesc
 * @param {Function=} fCloseCallback = null
 * @param {string=} sHeading = ''
 * @param {string=} sOkButtonText = 'Ok'
 */
CAlertPopup.prototype.onShow = function (sDesc, fCloseCallback, sHeading, sOkButtonText)
{
	this.alertDesc(sDesc);
	this.closeCallback = fCloseCallback || null;
	this.popupHeading(sHeading || '');
	this.okButtonText(sOkButtonText || TextUtils.i18n('CORE/ACTION_OK'));
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
