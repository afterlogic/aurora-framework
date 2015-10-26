'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
			
	TextUtils = require('core/js/utils/Text.js'),
	Screens = require('core/js/Screens.js'),
	
	ErrorsUtils = require('modules/OpenPgp/js/utils/Errors.js'),
	Settings = require('modules/OpenPgp/js/Settings.js'),
	Enums = require('modules/OpenPgp/js/Enums.js'),
	OpenPgp = require('modules/OpenPgp/js/OpenPgp.js')
;

function CMessageControlsView()
{
	this.sText = '';
	this.sAccountEmail = '';
	this.sFromEmail = '';
	
	this.decryptPassword = ko.observable('');
	
	this.visibleDecryptControl = ko.observable(false);
	this.visibleVerifyControl = ko.observable(false);
	
	this.visible = ko.computed(function () {
		return this.visibleDecryptControl() || this.visibleVerifyControl();
	}, this);
}

CMessageControlsView.prototype.ViewTemplate = 'OpenPgp_MessageControlsView';

CMessageControlsView.prototype.reset = function ()
{
	this.sText = '';
	this.sAccountEmail = '';
	this.sFromEmail = '';
	this.fChangeText = function () {};
	
	this.decryptPassword('');
	
	this.visibleDecryptControl(false);
	this.visibleVerifyControl(false);
};

/**
 * @param {object} oMessagePaneExtInterface
 */
CMessageControlsView.prototype.populate = function (oMessagePaneExtInterface)
{
	if (oMessagePaneExtInterface.bPlain)
	{
		this.sText = oMessagePaneExtInterface.sRawText;
		this.sAccountEmail = oMessagePaneExtInterface.sAccountEmail;
		this.sFromEmail = oMessagePaneExtInterface.sFromEmail;
		this.fChangeText = oMessagePaneExtInterface.changeText;

		this.decryptPassword('');

		if (Settings.enableOpenPgp())
		{
			this.visibleDecryptControl(oMessagePaneExtInterface.sText.indexOf('-----BEGIN PGP MESSAGE-----') !== -1);
			this.visibleVerifyControl(oMessagePaneExtInterface.sText.indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1);
			if (this.visible())
			{
				this.fChangeText('<pre>' + TextUtils.encodeHtml(this.sText) + '</pre>');
			}
		}
	}
	else
	{
		this.reset();
	}
};

CMessageControlsView.prototype.decryptMessage = function ()
{
	var
		sPrivateKeyPassword = this.decryptPassword(),
		oRes = OpenPgp.decryptAndVerify(this.sText, this.sAccountEmail, this.sFromEmail, sPrivateKeyPassword),
		bNoSignDataNotice = false
	;
	
	if (oRes && oRes.result && !oRes.errors)
	{
		this.fChangeText('<pre>' + TextUtils.encodeHtml(oRes.result) + '</pre>');
		
		this.decryptPassword('');
		this.visibleDecryptControl(false);
		
		if (!oRes.notices)
		{
			Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_DECRYPTED_AND_VERIFIED'));
		}
		else
		{
			Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_DECRYPTED'));
		}
	}
	
	if (oRes && (oRes.errors || oRes.notices))
	{
		bNoSignDataNotice = ErrorsUtils.showPgpErrorByCode(oRes, Enums.PgpAction.DecryptVerify);
		if (bNoSignDataNotice)
		{
			Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_DECRYPTED_AND_NOT_SIGNED'));
		}
	}
};

CMessageControlsView.prototype.verifyMessage = function ()
{
	var oRes = OpenPgp.verify(this.sText, this.sFromEmail);
	
	if (oRes && oRes.result && !(oRes.errors || oRes.notices))
	{
		this.fChangeText('<pre>' + TextUtils.encodeHtml(oRes.result) + '</pre>');
		
		this.visibleVerifyControl(false);
		
		Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_VERIFIED'));
	}
	
	if (oRes && (oRes.errors || oRes.notices))
	{
		ErrorsUtils.showPgpErrorByCode(oRes, Enums.PgpAction.Verify);
	}
};

module.exports = new CMessageControlsView();