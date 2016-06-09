'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),	
	
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js'),
	
	Phone = require('modules/%ModuleName%/js/Phone.js')
;

/**
 * @constructor
 */
function CPhonePopup()
{
	CAbstractPopup.call(this);
	
	this.action = Phone.action;
	this.report = Phone.report;

	this.text = ko.observable('');
}

_.extendOwn(CPhonePopup.prototype, CAbstractPopup.prototype);

CPhonePopup.prototype.PopupTemplate = '%ModuleName%_PhonePopup';

CPhonePopup.prototype.onShow = function (sText)
{
	this.text(sText);
};

CPhonePopup.prototype.onOKClick = function ()
{
	this.closePopup();
};

CPhonePopup.prototype.answer = function ()
{
	this.action(Enums.PhoneAction.IncomingConnect);
};

CPhonePopup.prototype.hangup = function ()
{
	this.action(Enums.PhoneAction.Online);
};

module.exports = new CPhonePopup();