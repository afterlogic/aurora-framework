'use strict';

var
	ko = require('knockout'),
			
	TextUtils = require('core/js/utils/Text.js'),
	
	Screens = require('core/js/Screens.js'),
	
	ErrorsUtils = require('modules/OpenPgp/js/utils/Errors.js'),
	
	Enums = require('modules/OpenPgp/js/Enums.js'),
	OpenPgp = require('modules/OpenPgp/js/OpenPgp.js'),
	Settings = require('modules/OpenPgp/js/Settings.js')
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
	
	this.decryptPassword('');
	
	this.visibleDecryptControl(false);
	this.visibleVerifyControl(false);
};

/**
 * Assigns message pane external interface.
 * 
 * @param {Object} oMessagePane Message pane external interface.
 * @param {Function} oMessagePane.changeText(sText) Function changes displaying text in message pane and in message too so exactly this text will be shown next time.
 */
CMessageControlsView.prototype.assignMessagePaneExtInterface = function (oMessagePane)
{
	this.oMessagePane = oMessagePane;
};

/**
 * Receives properties of the message that is displaying in the message pane. 
 * It is called every time the message is changing in the message pane.
 * Receives null if there is no message in the pane.
 * 
 * @param {Object|null} oMessageProps Information about message in message pane.
 * @param {Boolean} oMessageProps.bPlain **true**, if displaying message is plain.
 * @param {String} oMessageProps.sRawText Raw plain text of message.
 * @param {String} oMessageProps.sText Prepared for displaying plain text of message.
 * @param {String} oMessageProps.sAccountEmail Email of account that received message.
 * @param {String} oMessageProps.sFromEmail Message sender email.
 */
CMessageControlsView.prototype.doAfterPopulatingMessage = function (oMessageProps)
{
	if (oMessageProps && oMessageProps.bPlain)
	{
		this.sText = oMessageProps.sRawText;
		this.sAccountEmail = oMessageProps.sAccountEmail;
		this.sFromEmail = oMessageProps.sFromEmail;

		this.decryptPassword('');

		if (Settings.enableOpenPgp())
		{
			this.visibleDecryptControl(oMessageProps.sText.indexOf('-----BEGIN PGP MESSAGE-----') !== -1);
			this.visibleVerifyControl(oMessageProps.sText.indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1);
			if (this.visible() && this.oMessagePane)
			{
				this.oMessagePane.changeText('<pre>' + TextUtils.encodeHtml(this.sText) + '</pre>');
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
	
	if (oRes && oRes.result && !oRes.errors && this.oMessagePane)
	{
		this.oMessagePane.changeText('<pre>' + TextUtils.encodeHtml(oRes.result) + '</pre>');
		
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
	
	if (oRes && oRes.result && !(oRes.errors || oRes.notices) && this.oMessagePane)
	{
		this.oMessagePane.changeText('<pre>' + TextUtils.encodeHtml(oRes.result) + '</pre>');
		
		this.visibleVerifyControl(false);
		
		Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_VERIFIED'));
	}
	
	if (oRes && (oRes.errors || oRes.notices))
	{
		ErrorsUtils.showPgpErrorByCode(oRes, Enums.PgpAction.Verify);
	}
};

module.exports = new CMessageControlsView();
