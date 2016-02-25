'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
			
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js'),
	EncryptPopup = require('modules/OpenPgp/js/popups/EncryptPopup.js'),
	
	Settings = require('modules/OpenPgp/js/Settings.js')
;

/**
 * @constructor for object that display buttons "PGP Sign/Encrypt" and "Undo PGP"
 */
function CComposeButtonsView()
{
	this.sId = 'OpenPgp';
	
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
	
	this.bComposeModeChanged = false;
}

CComposeButtonsView.prototype.ViewTemplate = 'OpenPgp_ComposeButtonsView';

/**
 * Assigns compose external interface.
 * 
 * @param {Object} oCompose Compose external interface object.
 * @param {Function} oCompose.isHtml Returns **true** if html mode is switched on in html editor.
 * @param {Function} oCompose.hasAttachments Returns **true** if some files were attached to message.
 * @param {Function} oCompose.getPlainText Returns plain text from html editor. If html mode is switched on html text will be converted to plain and returned.
 * @param {Function} oCompose.getFromEmail Returns message sender email.
 * @param {Function} oCompose.getRecipientEmails Returns array of message recipients.
 * @param {Function} oCompose.saveSilently Saves message silently (without buttons disabling and any info messages).
 * @param {Function} oCompose.setPlainTextMode Sets plain text mode switched on.
 * @param {Function} oCompose.setPlainText Sets plain text to html editor.
 * @param {Function} oCompose.setHtmlTextMode Sets html text mode switched on.
 * @param {Function} oCompose.setHtmlText Sets html text to html editor.
 * @param {Function} oCompose.undoHtml Undo last changes in html editor.
 */
CComposeButtonsView.prototype.assignComposeExtInterface = function (oCompose)
{
	this.oCompose = oCompose;
};

/**
 * Receives message properties that are displayed when opening the compose popup.
 * 
 * @param {Object} oMessageProps Receiving message properties.
 * @param {Boolean} oMessageProps.bDraft **true** if message was opened from drafts folder.
 * @param {Boolean} oMessageProps.bPlain **true** if opened for compose message if plain.
 * @param {String} oMessageProps.sRawText Raw plain text of opened for compose message.
 */
CComposeButtonsView.prototype.doAfterPopulatingMessage = function (oMessageProps)
{
	this.bComposeModeChanged = false;
	this.fromDrafts(oMessageProps.bDraft);
	if (oMessageProps.bPlain)
	{
		var
			bPgpEncrypted = oMessageProps.sRawText.indexOf('-----BEGIN PGP MESSAGE-----') !== -1,
			bPgpSigned = oMessageProps.sRawText.indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1
		;

		this.pgpSecured(bPgpSigned || bPgpEncrypted);
		this.pgpEncrypted(bPgpEncrypted);
	}
	else
	{
		this.pgpSecured(false);
		this.pgpEncrypted(false);
	}
};

/**
 * Executes before message sending. May cancel the sending and continue it later if it's necessary.
 * @param {Function} fContinueSending Handler for continue message sending if it's necessary.
 * @returns {Boolean} If **true** message sending will be canceled.
 */
CComposeButtonsView.prototype.doBeforeSend = function (fContinueSending)
{
	if (this.enableOpenPgp() && Settings.AutosignOutgoingEmails && !this.pgpSecured())
	{
		this.openPgpPopup(fContinueSending);
		return true;
	}
	return false;
};

/**
 * Executes before message saving. May cancel the saving and continue it later if it's necessary.
 * @param {Function} fContinueSaving Handler for continue message saving if it's necessary.
 * @returns {Boolean} If **true** message saving will be canceled.
 */
CComposeButtonsView.prototype.doBeforeSave = function (fContinueSaving)
{
	if (this.pgpSecured())
	{
		Popups.showPopup(ConfirmPopup, [TextUtils.i18n('OPENPGP/CONFIRM_SAVE_ENCRYPTED_DRAFT'), fContinueSaving, '', TextUtils.i18n('COMPOSE/ACTION_SAVE')]);
		
		return true;
	}
	return false;
};

CComposeButtonsView.prototype.confirmOpenPgp = function ()
{
	if (this.oCompose)
	{
		if (this.oCompose.isHtml())
		{
			var
				sConfirm = TextUtils.i18n('OPENPGP/CONFIRM_HTML_TO_PLAIN_FORMATTING'),
				fEncryptPopup = _.bind(function (bRes) {
					if (bRes)
					{
						this.openPgpPopup();
					}
				}, this)
			;

			if (this.oCompose.hasAttachments())
			{
				sConfirm += '\r\n\r\n' + TextUtils.i18n('OPENPGP/CONFIRM_HTML_TO_PLAIN_ATTACHMENTS');
			}

			Popups.showPopup(ConfirmPopup, [sConfirm, fEncryptPopup]);
		}
		else
		{
			this.openPgpPopup();
		}
	}
};

/**
 * @param {function} fContinueSending
 */
CComposeButtonsView.prototype.openPgpPopup = function (fContinueSending)
{
	if (this.oCompose)
	{
		var
			bContinueSending = $.isFunction(fContinueSending),
			fOkCallback = _.bind(function (sSignedEncryptedText, bEncrypted) {
				if (!bContinueSending)
				{
					this.oCompose.saveSilently();
				}
				
				if (this.oCompose.isHtml())
				{
					this.oCompose.setPlainTextMode();
					this.bComposeModeChanged = true;
				}
				this.oCompose.setPlainText(sSignedEncryptedText);
				
				if (bContinueSending)
				{
					fContinueSending();
				}
				this.pgpSecured(true);
				this.pgpEncrypted(bEncrypted);
			}, this),
			fCancelCallback = bContinueSending ? fContinueSending : function () {}
		;

		Popups.showPopup(EncryptPopup, [this.oCompose.getPlainText(), this.oCompose.getFromEmail(), this.oCompose.getRecipientEmails(), bContinueSending, fOkCallback, fCancelCallback]);
	}
};

CComposeButtonsView.prototype.undoPgp = function ()
{
	var
		sText = '',
		aText = []
	;

	if (this.oCompose && this.pgpSecured())
	{
		if (this.bComposeModeChanged)
		{
			this.oCompose.setHtmlTextMode();
			this.bComposeModeChanged = false;
		}
		
		if (this.fromDrafts() && !this.pgpEncrypted())
		{
			sText = this.oCompose.getPlainText();
			
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

			if (this.oCompose.isHtml())
			{
				this.oCompose.setHtmlText('<div>' + sText.replace(/\r\n/gi, '<br />') + '</div>');
			}
			else
			{
				this.oCompose.setPlainText(sText);
			}
		}
		else
		{
			this.oCompose.undoHtml();
		}

		this.pgpSecured(false);
		this.pgpEncrypted(false);
	}
};

module.exports = new CComposeButtonsView();
