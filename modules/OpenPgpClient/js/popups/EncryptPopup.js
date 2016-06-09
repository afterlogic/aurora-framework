'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Utils = require('modules/CoreClient/js/utils/Common.js'),
	
	Screens = require('modules/CoreClient/js/Screens.js'),
	
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js'),
	
	ErrorsUtils = require('modules/%ModuleName%/js/utils/Errors.js'),
	
	Enums = require('modules/%ModuleName%/js/Enums.js'),
	OpenPgp = require('modules/%ModuleName%/js/OpenPgp.js')
;

/**
 * @constructor
 */
function CEncryptPopup()
{
	CAbstractPopup.call(this);
	
	this.data = ko.observable('');
	this.fromEmail = ko.observable('');
	this.emails = ko.observableArray([]);
	this.okCallback = null;
	this.cancelCallback = null;
	this.sign = ko.observable(true);
	this.password = ko.observable('');
	this.passwordFocused = ko.observable(false);
	this.encrypt = ko.observable(true);
	this.signEncryptButtonText = ko.computed(function () {
		var sText = TextUtils.i18n('%MODULENAME%/ACTION_SIGN_ENCRYPT');
		if (this.sign() && !this.encrypt())
		{
			sText = TextUtils.i18n('%MODULENAME%/ACTION_SIGN');
		}
		if (!this.sign() && this.encrypt())
		{
			sText = TextUtils.i18n('%MODULENAME%/ACTION_ENCRYPT');
		}
		return sText;
	}, this);
	this.isEnableSignEncrypt = ko.computed(function () {
		return this.sign() || this.encrypt();
	}, this);
	this.signEncryptCommand = Utils.createCommand(this, this.executeSignEncrypt, this.isEnableSignEncrypt);
	this.signAndSend = ko.observable(false);
}

_.extendOwn(CEncryptPopup.prototype, CAbstractPopup.prototype);

CEncryptPopup.prototype.PopupTemplate = '%ModuleName%_EncryptPopup';

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @param {Array} aEmails
 * @param {boolean} bSignAndSend
 * @param {Function} fOkCallback
 * @param {Function} fCancelCallback
 */
CEncryptPopup.prototype.onShow = function (sData, sFromEmail, aEmails, bSignAndSend, fOkCallback, fCancelCallback)
{
	this.data(sData);
	this.fromEmail(sFromEmail);
	this.emails(aEmails);
	this.okCallback = fOkCallback;
	this.cancelCallback = fCancelCallback;
	this.sign(true);
	this.password('');
	this.encrypt(!bSignAndSend);
	this.signAndSend(bSignAndSend);
};

CEncryptPopup.prototype.executeSignEncrypt = function ()
{
	var
		sData = this.data(),
		sPrivateEmail = this.sign() ? this.fromEmail() : '',
		aPrincipalsEmail = this.emails(),
		sPrivateKeyPassword = this.sign() ? this.password() : '',
		oRes = null,
		sOkReport = '',
		sPgpAction = ''
	;
	
	if (this.encrypt())
	{
		if (aPrincipalsEmail.length === 0)
		{
			Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_TO_ENCRYPT_SPECIFY_RECIPIENTS'));
		}
		else
		{
			if (this.sign())
			{
				sPgpAction = Enums.PgpAction.EncryptSign;
				sOkReport = TextUtils.i18n('%MODULENAME%/REPORT_MESSAGE_SIGNED_ENCRYPTED_SUCCSESSFULLY');
				oRes = OpenPgp.signAndEncrypt(sData, sPrivateEmail, aPrincipalsEmail, sPrivateKeyPassword);
			}
			else
			{
				sPgpAction = Enums.PgpAction.Encrypt;
				sOkReport = TextUtils.i18n('%MODULENAME%/REPORT_MESSAGE_ENCRYPTED_SUCCSESSFULLY');
				oRes = OpenPgp.encrypt(sData, aPrincipalsEmail);
			}
		}
	}
	else if (this.sign())
	{
		sPgpAction = Enums.PgpAction.Sign;
		sOkReport = TextUtils.i18n('%MODULENAME%/REPORT_MESSAGE_SIGNED_SUCCSESSFULLY');
		oRes = OpenPgp.sign(sData, sPrivateEmail, sPrivateKeyPassword);
	}
	
	if (oRes)
	{
		if (oRes.result)
		{
			this.closePopup();
			if (this.okCallback)
			{
				if (!this.signAndSend())
				{
					Utils.log('CEncryptPopup', sOkReport);
					Screens.showReport(sOkReport);
				}
				this.okCallback(oRes.result, this.encrypt());
			}
		}
		else
		{
			ErrorsUtils.showPgpErrorByCode(oRes, sPgpAction);
		}
	}
};

CEncryptPopup.prototype.cancelPopup = function ()
{
	if (this.cancelCallback)
	{
		this.cancelCallback();
	}
	
	this.closePopup();
};

module.exports = new CEncryptPopup();
