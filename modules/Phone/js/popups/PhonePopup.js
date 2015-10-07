'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),	
	
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	Phone = require('modules/Phone/js/Phone.js')
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
	this.callback = null;
}

_.extendOwn(CPhonePopup.prototype, CAbstractPopup.prototype);

CPhonePopup.prototype.PopupTemplate = 'Phone_PhonePopup';

CPhonePopup.prototype.onShow = function (oParameters)
{
	this.text(oParameters.text);
	this.callback = oParameters.Callback || function () {};
};

CPhonePopup.prototype.onOKClick = function ()
{
	this.closePopup();
	this.callback();
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