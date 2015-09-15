'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	
	UserSettings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js')
;

/**
 * @constructor
 */
function COpenPgpEncryptPopup()
{
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
		var sText = TextUtils.i18n('OPENPGP/BUTTON_SIGN_ENCRYPT');
		if (this.sign() && !this.encrypt())
		{
			sText = TextUtils.i18n('OPENPGP/BUTTON_SIGN');
		}
		if (!this.sign() && this.encrypt())
		{
			sText = TextUtils.i18n('OPENPGP/BUTTON_ENCRYPT');
		}
		return sText;
	}, this);
	this.isEnableSignEncrypt = ko.computed(function () {
		return this.sign() || this.encrypt();
	}, this);
	this.signEncryptCommand = Utils.createCommand(this, this.executeSignEncrypt, this.isEnableSignEncrypt);
	this.signAndSend = ko.observable(false);
}

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @param {Array} aEmails
 * @param {boolean} bSignAndSend
 * @param {Function} fOkCallback
 * @param {Function} fCancelCallback
 */
COpenPgpEncryptPopup.prototype.onShow = function (sData, sFromEmail, aEmails, bSignAndSend, fOkCallback, fCancelCallback)
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

/**
 * @return {string}
 */
COpenPgpEncryptPopup.prototype.popupTemplate = function ()
{
	return 'Popups_OpenPgpEncryptPopupViewModel';
};

COpenPgpEncryptPopup.prototype.executeSignEncrypt = function ()
{
	var fPgpCallback = _.bind(function (oPgp) {
		if (oPgp)
		{
			this.signEncrypt(oPgp);
		}
	}, this);
	
	App.Api.pgp(fPgpCallback, UserSettings.IdUser);
};

/**
 * @param {Object} oPgp
 */
COpenPgpEncryptPopup.prototype.signEncrypt = function (oPgp)
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
			Screens.showError(TextUtils.i18n('OPENPGP/ERROR_TO_ENCRYPT_SPECIFY_RECIPIENTS'));
		}
		else
		{
			if (this.sign())
			{
				sPgpAction = Enums.PgpAction.EncryptSign;
				sOkReport = TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SIGNED_ENCRYPTED_SUCCSESSFULLY');
				oRes = oPgp.signAndEncrypt(sData, sPrivateEmail, aPrincipalsEmail, sPrivateKeyPassword);
			}
			else
			{
				sPgpAction = Enums.PgpAction.Encrypt;
				sOkReport = TextUtils.i18n('OPENPGP/REPORT_MESSAGE_ENCRYPTED_SUCCSESSFULLY');
				oRes = oPgp.encrypt(sData, aPrincipalsEmail);
			}
		}
	}
	else if (this.sign())
	{
		sPgpAction = Enums.PgpAction.Sign;
		sOkReport = TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SIGNED_SUCCSESSFULLY');
		oRes = oPgp.sign(sData, sPrivateEmail, sPrivateKeyPassword);
	}
	
	if (oRes)
	{
		if (oRes.result)
		{
			this.closeCommand();
			if (this.okCallback)
			{
				if (!this.signAndSend())
				{
					Screens.showReport(sOkReport);
				}
				this.okCallback(oRes.result, this.encrypt());
			}
		}
		else
		{
			Api.showPgpErrorByCode(oRes, sPgpAction);
		}
	}
};

COpenPgpEncryptPopup.prototype.cancel = function ()
{
	if (this.cancelCallback)
	{
		this.cancelCallback();
	}
	this.closeCommand();
};

COpenPgpEncryptPopup.prototype.onEscHandler = function ()
{
	this.cancel();
};

module.exports = new COpenPgpEncryptPopup();