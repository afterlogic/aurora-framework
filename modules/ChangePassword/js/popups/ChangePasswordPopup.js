'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Ajax = require('core/js/Ajax.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	Settings = require('modules/ChangePassword/js/Settings.js')
;

/**
 * @constructor
 */
function CChangePasswordPopup()
{
	CAbstractPopup.call(this);
	
	this.currentPassword = ko.observable('');
	this.newPassword = ko.observable('');
	this.confirmPassword = ko.observable('');
	
	this.hasOldPassword = ko.observable(false);
	this.oParams = null;
}

_.extendOwn(CChangePasswordPopup.prototype, CAbstractPopup.prototype);

CChangePasswordPopup.prototype.PopupTemplate = 'ChangePassword_ChangePasswordPopup';

/**
 * @param {Object} oParams
 * @param {String} oParams.sModule
 * @param {boolean} oParams.bHasOldPassword
 * @param {Function} oParams.fAfterPasswordChanged
 */
CChangePasswordPopup.prototype.onShow = function (oParams)
{
	this.currentPassword('');
	this.newPassword('');
	this.confirmPassword('');
	
	this.hasOldPassword(oParams.bHasOldPassword);
	this.oParams = oParams;
};

CChangePasswordPopup.prototype.change = function ()
{
	if (this.confirmPassword() !== this.newPassword())
	{
		Screens.showError(TextUtils.i18n('CORE/WARNING_PASSWORDS_DO_NOT_MATCH'));
	}
	else if (Settings.PasswordMinLength > 0 && this.newPassword().length < Settings.PasswordMinLength) 
	{ 
		Screens.showError(TextUtils.i18n('CHANGEPASSWORD/PASSWORDS_MIN_LENGTH_ERROR').replace('%N%', Settings.PasswordMinLength));
	}
	else if (Settings.PasswordMustBeComplex && (!this.newPassword().match(/([0-9])/) || !this.newPassword().match(/([!,%,&,@,#,$,^,*,?,_,~])/)))
	{
		Screens.showError(TextUtils.i18n('CHANGEPASSWORD/PASSWORD_MUST_BE_COMPLEX'));
	}
	else
	{
		this.sendChangeRequest();
	}
};

CChangePasswordPopup.prototype.sendChangeRequest = function ()
{
	var oParameters = {
		'CurrentPassword': this.currentPassword(), // CurrentIncomingMailPassword
		'NewPassword': this.newPassword() // NewIncomingMailPassword
	};

	if (Settings.ResetPassHash)
	{
		oParameters.Hash = Settings.ResetPassHash;
	}
	
	Ajax.send(this.oParams.sModule, 'ChangePassword', oParameters, this.onUpdatePasswordResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CChangePasswordPopup.prototype.onUpdatePasswordResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('CHANGEPASSWORD/NEW_PASSWORD_UPDATE_ERROR'));
	}
	else
	{
		if (this.hasOldPassword())
		{
			Screens.showReport(TextUtils.i18n('CHANGEPASSWORD/CHANGE_PASSWORD_SUCCESS'));
		}
		else
		{
			Screens.showReport(TextUtils.i18n('CHANGEPASSWORD/SET_PASSWORD_SUCCESS'));
		}
		
		this.closePopup();
		
		if ($.isFunction(this.oParams.fAfterPasswordChanged))
		{
			this.oParams.fAfterPasswordChanged();
		}
		
		Settings.ResetPassHash = '';
	}
};

module.exports = new CChangePasswordPopup();
