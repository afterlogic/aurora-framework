'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
			
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js'),
	EncryptPopup = require('modules/OpenPgp/js/popups/EncryptPopup.js'),
	
	Settings = require('modules/OpenPgp/js/Settings.js')
;

function CComposeButtonsView()
{
	this.enableOpenPgp = ko.observable(true);
	
	this.pgpSecured = ko.observable(false);
	this.pgpEncrypted = ko.observable(false);
	this.fromDrafts = ko.observable(false);
	
	this.disableHeadersEdit = this.pgpEncrypted;
	this.disableBodyEdit = this.pgpSecured;
	this.disableAutosave = this.pgpSecured;
	
	this.visibleDoPgpButton = ko.computed(function () {
		return this.enableOpenPgp() && (!this.pgpSecured() || this.pgpEncrypted() && this.fromDrafts());
	}, this);
	this.visibleUndoPgpButton = ko.computed(function () {
		return this.enableOpenPgp() && this.pgpSecured() && (!this.pgpEncrypted() || !this.fromDrafts());
	}, this);
	
	this.isEnableOpenPgpCommand = ko.computed(function () {
		return this.enableOpenPgp() && !this.pgpSecured();
	}, this);
	this.openPgpCommand = Utils.createCommand(this, this.confirmOpenPgp, this.isEnableOpenPgpCommand);
}

CComposeButtonsView.prototype.ViewTemplate = 'OpenPgp_ComposeButtonsView';

CComposeButtonsView.prototype.setData = function (koComposeHasAttachments, fComposeGetPlainText, fComposeAfterSigning, 
													fComposeSend, fComposeGetFromEmail, koComposeRecipientEmails, fComposeAfterUndoPgpFunction)
{
	this.composeHasAttachments = koComposeHasAttachments;
	this.composeGetPlainTextFunction = fComposeGetPlainText;
	this.composeAfterSigningFunction = fComposeAfterSigning;
	this.composeSendFunction = fComposeSend;
	this.composeGetFromEmailFunction = fComposeGetFromEmail;
	this.composeRecipientEmails = koComposeRecipientEmails;
	this.composeAfterUndoPgpFunction = fComposeAfterUndoPgpFunction;
};

CComposeButtonsView.prototype.checkPgpSecured = function (sMessage)
{
	var
		bPgpEncrypted = sMessage.indexOf('-----BEGIN PGP MESSAGE-----') !== -1,
		bPgpSigned = sMessage.indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1
	;
	
	this.pgpSecured(bPgpSigned || bPgpEncrypted);
	this.pgpEncrypted(bPgpEncrypted);
};

CComposeButtonsView.prototype.reset = function ()
{
	this.pgpSecured(false);
	this.pgpEncrypted(false);
	this.fromDrafts(false);
};

CComposeButtonsView.prototype.populate = function (oMessage)
{
	this.fromDrafts(oMessage.isDraft);
	if (oMessage.isPlain)
	{
		this.checkPgpSecured(oMessage.rawText);
	}
};

CComposeButtonsView.prototype.doBeforeSend = function ()
{
	if (this.enableOpenPgp() && Settings.AutosignOutgoingEmails && !this.pgpSecured())
	{
		this.openPgpPopup(true);
		return true;
	}
	return false;
};

CComposeButtonsView.prototype.doBeforeSave = function (fSave)
{
};

CComposeButtonsView.prototype.confirmAndSaveEncryptedDraft = function (fSave)
{
	var
		sConfirm = TextUtils.i18n('OPENPGP/CONFIRM_SAVE_ENCRYPTED_DRAFT'),
		sOkButton = TextUtils.i18n('COMPOSE/TOOL_SAVE')
	;
	Popups.showPopup(ConfirmPopup, [sConfirm, fSave, '', sOkButton]);
};

CComposeButtonsView.prototype.confirmOpenPgp = function ()
{
	var
		sConfirm = TextUtils.i18n('OPENPGP/CONFIRM_HTML_TO_PLAIN_FORMATTING'),
		fEncryptPopup = _.bind(function (bRes) {
			if (bRes)
			{
				this.openPgpPopup(false);
			}
		}, this)
	;

	if (this.composeHasAttachments())
	{
		sConfirm += '\r\n\r\n' + TextUtils.i18n('OPENPGP/CONFIRM_HTML_TO_PLAIN_ATTACHMENTS');
	}

	Popups.showPopup(ConfirmPopup, [sConfirm, fEncryptPopup]);
};

/**
 * @param {boolean} bSendAfterSigning
 */
CComposeButtonsView.prototype.openPgpPopup = function (bSendAfterSigning)
{
	var
		fOkCallback = _.bind(function (sSignedEncryptedText, bEncrypted) {
			this.composeAfterSigningFunction(bSendAfterSigning, sSignedEncryptedText);
			this.pgpSecured(true);
			this.pgpEncrypted(bEncrypted);
		}, this),
		fCancelCallback = _.bind(function () {
			if (bSendAfterSigning)
			{
				this.composeSendFunction();
			}
		}, this)
	;

	Popups.showPopup(EncryptPopup, [this.composeGetPlainTextFunction(), this.composeGetFromEmailFunction(), this.composeRecipientEmails(), bSendAfterSigning, fOkCallback, fCancelCallback]);
};

CComposeButtonsView.prototype.undoPgp = function ()
{
	var
		sText = '',
		aText = []
	;

	if (this.pgpSecured())
	{
		if (this.fromDrafts() && !this.pgpEncrypted())
		{
			sText = this.composeGetPlainTextFunction();
			
			aText = sText.split('-----BEGIN PGP SIGNED MESSAGE-----');
			if (aText.length === 2)
			{
				sText = aText[1];
			}

			aText = sText.split('-----BEGIN PGP SIGNATURE-----');
			if (aText.length === 2)
			{
				sText = aText[0];
			}

			aText = sText.split('\r\n\r\n');
			if (aText.length > 0)
			{
				aText.shift();
				sText = aText.join('\r\n\r\n');
			}

			sText = '<div>' + sText.replace(/\r\n/gi, '<br />') + '</div>';

		}
		
		this.composeAfterUndoPgpFunction(sText);

		this.pgpSecured(false);
		this.pgpEncrypted(false);
	}
};

module.exports = new CComposeButtonsView();