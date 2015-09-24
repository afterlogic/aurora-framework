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
	this.Options = {
		getMessageData: function () {
			return {
				Text: '',
				AccountEmail: '',
				FromEmail: ''
			};
		},
		changeDecryptedOrVerifiedMessage: function () {}
	};
	
	this.decryptPassword = ko.observable('');
	
	this.visibleDecryptControl = ko.observable(false);
	this.visibleVerifyControl = ko.observable(false);
	
	this.visible = ko.computed(function () {
		return this.visibleDecryptControl() || this.visibleVerifyControl();
	}, this);
}

CMessageControlsView.prototype.ViewTemplate = 'OpenPgp_MessageControlsView';

CMessageControlsView.prototype.setOptions = function (oOptions)
{
	_.extendOwn(this.Options, oOptions);
};

CMessageControlsView.prototype.reset = function ()
{
	this.decryptPassword('');
	
	this.visibleDecryptControl(false);
	this.visibleVerifyControl(false);
};

CMessageControlsView.prototype.populate = function (sText)
{
	this.decryptPassword('');

	if (Settings.enableOpenPgp())
	{
		this.visibleDecryptControl(sText.indexOf('-----BEGIN PGP MESSAGE-----') !== -1);
		this.visibleVerifyControl(sText.indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1);
	}
};

CMessageControlsView.prototype.decryptMessage = function ()
{
	var
		oData = this.Options.getMessageData(),
		sPrivateKeyPassword = this.decryptPassword(),
		oRes = OpenPgp.decryptAndVerify(oData.Text, oData.AccountEmail, oData.FromEmail, sPrivateKeyPassword),
		bNoSignDataNotice = false
	;
	
	if (oRes && oRes.result && !oRes.errors)
	{
		this.Options.changeDecryptedOrVerifiedMessage(oRes.result);
		
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
	var
		oData = this.Options.getMessageData(),
		oRes = OpenPgp.verify(oData.Text, oData.FromEmail)
	;
	
	if (oRes && oRes.result && !(oRes.errors || oRes.notices))
	{
		this.Options.changeDecryptedOrVerifiedMessage(oRes.result);
		
		this.visibleVerifyControl(false);
		
		Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_VERIFIED'));
	}
	
	if (oRes && (oRes.errors || oRes.notices))
	{
		ErrorsUtils.showPgpErrorByCode(oRes, Enums.PgpAction.Verify);
	}
};

module.exports = new CMessageControlsView();