'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	FilesUtils = require('core/js/utils/Files.js'),
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	App = require('core/js/App.js'),
	UserSettings = require('core/js/Settings.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	MainTabExtMethods = require('modules/Mail/js/MainTabExtMethods.js'),
	Browser = require('core/js/Browser.js'),
	CJua = require('core/js/CJua.js'),
	CAbstractScreenView = require('core/js/views/CAbstractScreenView.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js'),
	AlertPopup = require('core/js/popups/AlertPopup.js'),
	SelectFilesPopup = ModulesManager.run('Files', 'getSelectFilesPopup'),
	
	LinksUtils = require('modules/Mail/js/utils/Links.js'),
	SendingUtils = require('modules/Mail/js/utils/Sending.js'),
	Accounts = require('modules/Mail/js/AccountList.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	Settings = require('modules/Mail/js/Settings.js'),
	SenderSelector = require('modules/Mail/js/SenderSelector.js'),
	CHtmlEditorView = require('modules/Mail/js/views/CHtmlEditorView.js'),
	CMessageModel = require('modules/Mail/js/models/CMessageModel.js'),
	CAttachmentModel = require('modules/Mail/js/models/CAttachmentModel.js'),
	
	MainTab = App.isNewTab() && window.opener && window.opener.MainTabMailMethods,
	bMobileApp = App.isMobile(),
	
	$html = $('html')
;

/**
 * @constructor
 */
function CComposeView()
{
	CAbstractScreenView.call(this);
	
	this.browserTitle = ko.computed(function () {
		return Accounts.getEmail() + ' - ' + TextUtils.i18n('TITLE/COMPOSE');
	});
	
	var self = this;

	this.toAddrDom = ko.observable();
	this.toAddrDom.subscribe(function () {
		this.initInputosaurus(this.toAddrDom, this.toAddr, this.lockToAddr, 'to');
	}, this);
	this.ccAddrDom = ko.observable();
	this.ccAddrDom.subscribe(function () {
		this.initInputosaurus(this.ccAddrDom, this.ccAddr, this.lockCcAddr, 'cc');
	}, this);
	this.bccAddrDom = ko.observable();
	this.bccAddrDom.subscribe(function () {
		this.initInputosaurus(this.bccAddrDom, this.bccAddr, this.lockBccAddr, 'bcc');
	}, this);

	this.folderList = MailCache.folderList;
	this.folderList.subscribe(function () {
		this.getMessageOnRoute();
	}, this);

	this.bNewTab = App.isNewTab();
	this.isDemo = ko.observable(UserSettings.IsDemo);

	this.sending = ko.observable(false);
	this.saving = ko.observable(false);

	this.oHtmlEditor = new CHtmlEditorView(false, this);
	this.textFocused = this.oHtmlEditor.textFocused;

	this.visibleBcc = ko.observable(false);
	this.visibleBcc.subscribe(function () {
		$html.toggleClass('screen-compose-bcc', this.visibleCc());
		_.defer(_.bind(function () {
			$(this.bccAddrDom()).inputosaurus('resizeInput');
		}, this));
	}, this);
	this.visibleCc = ko.observable(false);
	this.visibleCc.subscribe(function () {
		$html.toggleClass('screen-compose-cc', this.visibleCc());
		_.defer(_.bind(function () {
			$(this.ccAddrDom()).inputosaurus('resizeInput');
		}, this));
	}, this);
	this.visibleCounter = ko.observable(false);

	this.readingConfirmation = ko.observable(false);

	this.composeUploaderButton = ko.observable(null);
	this.composeUploaderButton.subscribe(function () {
		this.initUploader();
	}, this);
	this.composeUploaderDropPlace = ko.observable(null);
	this.composeUploaderBodyDragOver = ko.observable(false);
	this.composeUploaderDragOver = ko.observable(false);
	this.allowDragNDrop = ko.observable(false);
	this.uploaderBodyDragOver = ko.computed(function () {
		return this.allowDragNDrop() && this.composeUploaderBodyDragOver();
	}, this);
	this.uploaderDragOver = ko.computed(function () {
		return this.allowDragNDrop() && this.composeUploaderDragOver();
	}, this);

	this.selectedImportance = ko.observable(Enums.Importance.Normal);

	this.senderAccountId = SenderSelector.senderAccountId;
	this.senderList = SenderSelector.senderList;
	this.visibleFrom = ko.computed(function () {
		return this.senderList().length > 1;
	}, this);
	this.selectedSender = SenderSelector.selectedSender;
	this.selectedFetcherOrIdentity = SenderSelector.selectedFetcherOrIdentity;
	this.selectedFetcherOrIdentity.subscribe(function () {
		if (!this.oHtmlEditor.isEditing())
		{
			this.oHtmlEditor.clearUndoRedo();
		}
	}, this);

	this.signature = ko.observable('');
	this.prevSignature = ko.observable(null);
	ko.computed(function () {
		var sSignature = SendingUtils.getClearSignature(this.senderAccountId(), this.selectedFetcherOrIdentity());

		if (this.prevSignature() === null)
		{
			this.prevSignature(sSignature);
			this.signature(sSignature);
		}
		else
		{
			this.prevSignature(this.signature());
			this.signature(sSignature);
			this.oHtmlEditor.changeSignatureContent(this.signature(), this.prevSignature());
		}
	}, this);

	this.lockToAddr = ko.observable(false);
	this.toAddr = ko.observable('').extend({'reversible': true});
	this.toAddr.subscribe(function () {
		if (!this.lockToAddr())
		{
			$(this.toAddrDom()).val(this.toAddr());
			$(this.toAddrDom()).inputosaurus('refresh');
		}
	}, this);
	this.lockCcAddr = ko.observable(false);
	this.ccAddr = ko.observable('').extend({'reversible': true});
	this.ccAddr.subscribe(function () {
		if (!this.lockCcAddr())
		{
			$(this.ccAddrDom()).val(this.ccAddr());
			$(this.ccAddrDom()).inputosaurus('refresh');
		}
	}, this);
	this.lockBccAddr = ko.observable(false);
	this.bccAddr = ko.observable('').extend({'reversible': true});
	this.bccAddr.subscribe(function () {
		if (!this.lockBccAddr())
		{
			$(this.bccAddrDom()).val(this.bccAddr());
			$(this.bccAddrDom()).inputosaurus('refresh');
		}
	}, this);
	this.recipientEmails = ko.computed(function () {
		var
			aRecip = [this.toAddr(), this.ccAddr(), this.bccAddr()].join(',').split(','),
			aEmails = []
		;
		_.each(aRecip, function (sRecip) {
			var
				sTrimmedRecip = $.trim(sRecip),
				oRecip = null
			;
			if (sTrimmedRecip !== '')
			{
				oRecip = AddressUtils.getEmailParts(sTrimmedRecip);
				if (oRecip.email)
				{
					aEmails.push(oRecip.email);
				}
			}
		});
		return aEmails;
	}, this);
	this.subject = ko.observable('').extend({'reversible': true});
	this.counter = ko.observable(0);
	this.plainText = ko.observable(false);
	this.textBody = ko.observable('');
	this.textBody.subscribe(function () {
		this.oHtmlEditor.setText(this.textBody(), this.plainText());
		this.oHtmlEditor.commit();
	}, this);

	this.focusedField = ko.observable();
	this.textFocused.subscribe(function () {
		if (this.textFocused())
		{
			this.focusedField('text');
		}
	}, this);
	this.subjectFocused = ko.observable(false);
	this.subjectFocused.subscribe(function () {
		if (this.subjectFocused())
		{
			this.focusedField('subject');
		}
	}, this);

	this.draftUid = ko.observable('');
	this.draftUid.subscribe(function () {
		MailCache.editedDraftUid(this.draftUid());
	}, this);
	this.draftInfo = ko.observableArray([]);
	this.routeType = ko.observable('');
	this.routeParams = ko.observableArray([]);
	this.inReplyTo = ko.observable('');
	this.references = ko.observable('');

	this.bUploadStatus = false;
	this.iUploadAttachmentsTimer = 0;
	this.messageUploadAttachmentsStarted = ko.observable(false);

	this.messageUploadAttachmentsStarted.subscribe(function (bValue) {
		window.clearTimeout(self.iUploadAttachmentsTimer);
		if (bValue)
		{
			self.iUploadAttachmentsTimer = window.setTimeout(function () {
				self.bUploadStatus = true;
				Screens.showLoading(TextUtils.i18n('COMPOSE/INFO_ATTACHMENTS_LOADING'));
			}, 4000);
		}
		else
		{
			if (self.bUploadStatus)
			{
				self.iUploadAttachmentsTimer = window.setTimeout(function () {
					self.bUploadStatus = false;
					Screens.hideLoading();
				}, 1000);
			}
			else
			{
				Screens.hideLoading();
			}
		}
	}, this);

	this.attachments = ko.observableArray([]);
	this.attachmentsChanged = ko.observable(false);
	this.attachments.subscribe(function () {
		this.attachmentsChanged(true);
	}, this);
	this.notUploadedAttachments = ko.computed(function () {
		return _.filter(this.attachments(), function (oAttach) {
			return !oAttach.uploaded();
		});
	}, this);

	this.allAttachmentsUploaded = ko.computed(function () {
		return this.notUploadedAttachments().length === 0 && !this.messageUploadAttachmentsStarted();
	}, this);

	this.notInlineAttachments = ko.computed(function () {
		return _.filter(this.attachments(), function (oAttach) {
			return !oAttach.linked();
		});
	}, this);
	this.notInlineAttachments.subscribe(function () {
		$html.toggleClass('screen-compose-attachments', this.notInlineAttachments().length > 0);
	}, this);

	this.allowStartSending = ko.computed(function() {
		return !this.saving();
	}, this);
	this.allowStartSending.subscribe(function () {
		if (this.allowStartSending() && this.requiresPostponedSending())
		{
			SendingUtils.sendPostponedMail(this.draftUid());
			this.requiresPostponedSending(false);
		}
	}, this);
	this.requiresPostponedSending = ko.observable(false);

	// file uploader
	this.oJua = null;

	this.isDraftsCleared = ko.observable(false);

	this.backToListOnSendOrSave = ko.observable(false);

	this.composeShown = ko.computed(function () {
		return !!this.opened && this.opened() || !!this.shown && this.shown();
	}, this);
	
	this.toolbarControllers = ko.observableArray([]);
	this.disableHeadersEdit = ko.computed(function () {
		var bDisableHeadersEdit = false;
		
		_.each(this.toolbarControllers(), function (oController) {
			bDisableHeadersEdit = bDisableHeadersEdit || !!oController.disableHeadersEdit && oController.disableHeadersEdit();
		});
		
		return bDisableHeadersEdit;
	}, this);
	ko.computed(function () {
		var bDisableBodyEdit = false;
		
		_.each(this.toolbarControllers(), function (oController) {
			bDisableBodyEdit = bDisableBodyEdit || !!oController.disableBodyEdit && oController.disableBodyEdit();
		});
		
		this.oHtmlEditor.disabled(bDisableBodyEdit);
	}, this);
	this.iAutosaveInterval = -1;
	ko.computed(function () {
		var bAllowAutosave = Settings.AllowAutosaveInDrafts && this.composeShown() && !this.sending() && !this.saving();
		_.each(this.toolbarControllers(), function (oController) {
			bAllowAutosave = bAllowAutosave && !(!!oController.disableAutosave && oController.disableAutosave());
		});
		
		window.clearInterval(this.iAutosaveInterval);
		
		if (bAllowAutosave)
		{
			this.iAutosaveInterval = window.setInterval(_.bind(this.executeSave, this, true), Settings.AutoSaveIntervalSeconds * 1000);
		}
	}, this);

	this.backToListCommand = Utils.createCommand(this, this.executeBackToList);
	this.sendCommand = Utils.createCommand(this, this.executeSend, this.isEnableSending);
	this.saveCommand = Utils.createCommand(this, this.executeSaveCommand, this.isEnableSaving);

	this.messageFields = ko.observable(null);
	this.bottomPanel = ko.observable(null);

	this.sHotkeysHintsViewTemplate = !Browser.mobileDevice && !bMobileApp ? 'Mail_Compose_HotkeysHintsView' : '';
	this.sAttachmentsViewTemplate = bMobileApp ? '' : 'Mail_Compose_AttachmentsView';
	this.sAttachmentsMobileViewTemplate = bMobileApp ? 'Mail_Compose_AttachmentsMobileView' : '';
	this.sCcBccSwitchersViewTemplate = bMobileApp ? '' : 'Mail_Compose_CcBccSwitchersView';
	this.sCcBccSwitchersMobileViewTemplate = bMobileApp ? 'Mail_Compose_CcBccSwitchersMobileView' : '';
	this.sPopupButtonsViewTemplate = !bMobileApp && !App.isNewTab() ? 'Mail_Compose_PopupButtonsView' : '';
	this.bAllowHeadersCompressing = !bMobileApp;

	this.aHotkeys = [
		{ value: 'Ctrl+Enter', action: TextUtils.i18n('COMPOSE/HOTKEY_SEND') },
		{ value: 'Ctrl+S', action: TextUtils.i18n('COMPOSE/HOTKEY_SAVE') },
		{ value: 'Ctrl+Z', action: TextUtils.i18n('COMPOSE/HOTKEY_UNDO') },
		{ value: 'Ctrl+Y', action: TextUtils.i18n('COMPOSE/HOTKEY_REDO') },
		{ value: 'Ctrl+K', action: TextUtils.i18n('COMPOSE/HOTKEY_LINK') },
		{ value: 'Ctrl+B', action: TextUtils.i18n('COMPOSE/HOTKEY_BOLD') },
		{ value: 'Ctrl+I', action: TextUtils.i18n('COMPOSE/HOTKEY_ITALIC') },
		{ value: 'Ctrl+U', action: TextUtils.i18n('COMPOSE/HOTKEY_UNDERLINE') }
	];

	this.allowFiles = !!SelectFilesPopup;

	this.closeBecauseSingleCompose = ko.observable(false);
	this.changedInPreviousWindow = ko.observable(false);

	this.minHeightAdjustTrigger = ko.observable(false).extend({'autoResetToFalse': 105});
	this.minHeightRemoveTrigger = ko.observable(false).extend({'autoResetToFalse': 105});
	this.jqContainers = $('.pSevenMain:first, .popup.compose_popup');
	
	this.hasSomethingToSave = ko.computed(function () {
		return this.isChanged() && this.isEnableSaving();
	}, this);

	this.saveAndCloseTooltip = ko.computed(function () {
		return this.hasSomethingToSave() ? TextUtils.i18n('COMPOSE/TOOL_SAVE_CLOSE') : TextUtils.i18n('COMPOSE/TOOL_CLOSE');
	}, this);

	if (MainTab)
	{
		setTimeout(function() {
			window.onbeforeunload = function () {
				if (self.hasSomethingToSave())
				{
					self.beforeHide(window.close);
					return '';
				}
			};
		}, 1000);
	}

	this.splitterDom = ko.observable();

	this.headersCompressed = ko.observable(false);
	this.allowCcBccSwitchers = ko.computed(function () {
		return !this.disableHeadersEdit() && !this.headersCompressed();
	}, this);
	
	this.registerOwnToolbarControllers();
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

_.extendOwn(CComposeView.prototype, CAbstractScreenView.prototype);

CComposeView.prototype.ViewTemplate = App.isNewTab() ? 'Mail_ComposeScreenView' : 'Mail_ComposeView';
CComposeView.prototype.__name = 'CComposeView';

/**
 * Determines if sending a message is allowed.
 */
CComposeView.prototype.isEnableSending = function ()
{
	var
		bRecipientIsEmpty = this.toAddr().length === 0 && this.ccAddr().length === 0 && this.bccAddr().length === 0,
		bFoldersLoaded = this.folderList() && this.folderList().iAccountId !== 0
	;

	return bFoldersLoaded && !this.sending() && !bRecipientIsEmpty && this.allAttachmentsUploaded();
};

/**
 * Determines if saving a message is allowed.
 */
CComposeView.prototype.isEnableSaving = function ()
{
	var bFoldersLoaded = this.folderList() && this.folderList().iAccountId !== 0;

	return this.composeShown() && bFoldersLoaded && !this.sending() && !this.saving();
};

/**
 * @param {Object} koAddrDom
 * @param {Object} koAddr
 * @param {Object} koLockAddr
 * @param {string} sFocusedField
 */
CComposeView.prototype.initInputosaurus = function (koAddrDom, koAddr, koLockAddr, sFocusedField)
{
	if (koAddrDom() && $(koAddrDom()).length > 0)
	{
		$(koAddrDom()).inputosaurus({
			width: 'auto',
			parseOnBlur: true,
			autoCompleteSource: ModulesManager.run('Contacts', 'getSuggestionsAutocompleteComposeCallback') || function () {},
			autoCompleteDeleteItem: ModulesManager.run('Contacts', 'getSuggestionsAutocompleteDeleteHandler') || function () {},
			autoCompleteAppendTo: $(koAddrDom()).closest('td'),
			change : _.bind(function (ev) {
				koLockAddr(true);
				this.setRecipient(koAddr, ev.target.value);
				this.minHeightAdjustTrigger(true);
				koLockAddr(false);
			}, this),
			copy: _.bind(function (sVal) {
				this.inputosaurusBuffer = sVal;
			}, this),
			paste: _.bind(function () {
				var sInputosaurusBuffer = this.inputosaurusBuffer || '';
				this.inputosaurusBuffer = '';
				return sInputosaurusBuffer;
			}, this),
			focus: _.bind(this.focusedField, this, sFocusedField),
			mobileDevice: Browser.mobileDevice
		});
	}
};

/**
 * Colapse from to table.
 */
CComposeView.prototype.changeHeadersCompressed = function ()
{
	this.headersCompressed(!this.headersCompressed());
};

/**
 * Executes after applying bindings.
 */
CComposeView.prototype.onBind = function ()
{
	ko.computed(function () {
		this.minHeightAdjustTrigger();
		this.minHeightRemoveTrigger();
		_.delay(function () {
			$('.compose_popup .panel_content .panels').trigger('resize');
		}, 200);
	}, this);
	
	this.jqContainers = $('.pSevenMain:first, .popup.compose_popup');
	
	(this.$popupDom || this.$viewDom).find('.panel_content').on('resize', _.debounce(_.bind(function () {
		this.oHtmlEditor.resize();
	}, this), 1));
	
	ko.computed(function () {
		this.visibleBcc();
		this.visibleCc();
		this.headersCompressed();
		
		this.minHeightAdjustTrigger(true);
	}, this);
	
	ModulesManager.run('SessionTimeout', 'registerFunction', [_.bind(this.executeSave, this, false)]);

	this.hotKeysBind();
};

CComposeView.prototype.hotKeysBind = function ()
{
	(this.$popupDom || this.$viewDom).on('keydown', $.proxy(function(ev) {

		if (ev && ev.ctrlKey && !ev.altKey && !ev.shiftKey)
		{
			var
				nKey = ev.keyCode,
				bComputed = this.composeShown() && (!this.minimized || !this.minimized()) && ev && ev.ctrlKey
			;

			if (bComputed && nKey === Enums.Key.s)
			{
				ev.preventDefault();
				ev.returnValue = false;

				if (this.isEnableSaving())
				{
					this.saveCommand();
				}
			}
			else if (bComputed && nKey === Enums.Key.Enter && this.toAddr() !== '')
			{
				this.sendCommand();
			}
		}

	},this));
};

CComposeView.prototype.getMessageOnRoute = function ()
{
	var
		aParams = this.routeParams(),
		sFolderName = '',
		sUid = ''
	;

	if (this.routeType() !== '' && aParams.length === 3)
	{
		sFolderName = aParams[1];
		sUid = aParams[2];

		MailCache.getMessage(sFolderName, sUid, this.onMessageResponse, this);
	}
};

/**
 * Executes if the view model shows. Requests a folder list from the server to know the full names
 * of the folders Drafts and Sent Items.
 */
CComposeView.prototype.onShow = function ()
{
	if (App.isNewTab())
	{
		var AppTab = require('core/js/AppTab.js');
		AppTab.changeFavicon('favicon-single-compose.ico');
	}
	
	var sFocusedField = this.focusedField();

	$(this.splitterDom()).trigger('resize');

	this.oHtmlEditor.initCrea(this.textBody(), this.plainText(), '7');
	this.oHtmlEditor.commit();

	this.initUploader();

	this.backToListOnSendOrSave(false);

	this.focusedField(sFocusedField);//oHtmlEditor initialization puts focus on it and changes the variable focusedField

	$html.addClass('screen-compose');

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(true);
	}
};

CComposeView.prototype.reset = function ()
{
	this.plainText(false);
	
	this.bUploadStatus = false;
	window.clearTimeout(this.iUploadAttachmentsTimer);
	this.messageUploadAttachmentsStarted(false);

	this.draftUid('');
	this.draftInfo.removeAll();
	this.setDataFromMessage(new CMessageModel());

	this.isDraftsCleared(false);
};

/**
 * Executes if routing was changed.
 *
 * @param {Array} aParams
 */
CComposeView.prototype.onRoute = function (aParams)
{
	this.reset();
	
	this.routeType((aParams.length > 0) ? aParams[0] : '');
	switch (this.routeType())
	{
		case Enums.ReplyType.Reply:
		case Enums.ReplyType.ReplyAll:
		case Enums.ReplyType.Resend:
		case Enums.ReplyType.Forward:
		case 'drafts':
			this.routeParams(aParams);
			if (this.folderList().iAccountId !== 0)
			{
				this.getMessageOnRoute();
			}
			break;
		default:
			this.fillDefault(aParams);
			break;
	}
};

/**
 * @param {Array} aParams
 */
CComposeView.prototype.fillDefault = function (aParams)
{
	var
		sSignature = SendingUtils.getSignatureText(this.senderAccountId(), this.selectedFetcherOrIdentity(), true),
		oComposedMessage = MainTab ? MainTab.getComposedMessage(window.name) : null,
		oToAddr = (this.routeType() === 'to' && aParams.length === 2) ? LinksUtils.parseToAddr(aParams[1]) : null
	;

	if (oComposedMessage)
	{
		this.setMessageDataInNewTab(oComposedMessage);
		if (this.changedInPreviousWindow())
		{
			_.defer(_.bind(this.executeSave, this, true));
		}
	}
	else if (sSignature !== '')
	{
		this.textBody('<br /><br />' + sSignature + '<br />');
	}

	if (oToAddr)
	{
		this.setRecipient(this.toAddr, oToAddr.to);
		if (oToAddr.hasMailto)
		{
			this.subject(oToAddr.subject);
			this.setRecipient(this.ccAddr, oToAddr.cc);
			this.setRecipient(this.bccAddr, oToAddr.bcc);
			this.textBody('<div>' + oToAddr.body + '</div>');
		}
	}

	if (this.routeType() === 'vcard' && aParams.length === 2)
	{
		this.addContactAsAttachment(aParams[1]);
	}

	if (this.routeType() === 'file' && aParams.length === 2)
	{
		this.addFilesAsAttachment(aParams[1]);
	}

	if (this.routeType() === 'data-as-file' && aParams.length === 3)
	{
		this.addDataAsAttachment(aParams[1], aParams[2]);
	}

	_.defer(_.bind(function () {
		this.focusAfterFilling();
	}, this));

	this.visibleCc(this.ccAddr() !== '');
	this.visibleBcc(this.bccAddr() !== '');
	this.commit(true);
};

CComposeView.prototype.focusToAddr = function ()
{
	$(this.toAddrDom()).inputosaurus('focus');
};

CComposeView.prototype.focusCcAddr = function ()
{
	$(this.ccAddrDom()).inputosaurus('focus');
};

CComposeView.prototype.focusBccAddr = function ()
{
	$(this.bccAddrDom()).inputosaurus('focus');
};

CComposeView.prototype.focusAfterFilling = function ()
{
	switch (this.focusedField())
	{
		case 'to':
			this.focusToAddr();
			break;
		case 'cc':
			this.visibleCc(true);
			this.focusCcAddr();
			break;
		case 'bcc':
			this.visibleBcc(true);
			this.focusBccAddr();
			break;
		case 'subject':
			this.subjectFocused(true);
			break;
		case 'text':
			this.oHtmlEditor.setFocus();
			break;
		default:
			if (this.toAddr().length === 0)
			{
				this.focusToAddr();
			}
			else if (this.subject().length === 0)
			{
				this.subjectFocused(true);
			}
			else
			{
				this.oHtmlEditor.setFocus();
			}
			break;
	}
};

/**
 * @param {Function} fContinueScreenChanging
 */
CComposeView.prototype.beforeHide = function (fContinueScreenChanging)
{
	var
		sConfirm = TextUtils.i18n('COMPOSE/CONFIRM_DISCARD_CHANGES'),
		fOnConfirm = _.bind(function (bOk) {
			if (bOk && $.isFunction(fContinueScreenChanging))
			{
				this.commit();
				fContinueScreenChanging();
			}
			else
			{
				Routing.historyBackWithoutParsing('mail-compose');
			}
		}, this)
	;

	if (!this.closeBecauseSingleCompose() && this.hasSomethingToSave())
	{
		Popups.showPopup(ConfirmPopup, [sConfirm, fOnConfirm]);
	}
	else if ($.isFunction(fContinueScreenChanging))
	{
		fContinueScreenChanging();
	}
};

/**
 * Executes if view model was hidden.
 */
CComposeView.prototype.onHide = function ()
{
	if (!$.isFunction(this.closePopup) && this.hasSomethingToSave())
	{
		this.executeSave(true);
	}

	this.headersCompressed(false);

	this.routeParams([]);

	this.subjectFocused(false);
	this.focusedField('');

	this.oHtmlEditor.closeAllPopups();
	this.oHtmlEditor.visibleLinkPopup(false);

	this.messageUploadAttachmentsStarted(false);

	$html.removeClass('screen-compose').removeClass('screen-compose-cc').removeClass('screen-compose-bcc').removeClass('screen-compose-attachments');
	this.minHeightRemoveTrigger(true);

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(false);
	}
};

/**
 * @param {Object} koRecipient
 * @param {string} sRecipient
 */
CComposeView.prototype.setRecipient = function (koRecipient, sRecipient)
{
	if (koRecipient() === sRecipient)
	{
		koRecipient.valueHasMutated();
	}
	else
	{
		koRecipient(sRecipient);
	}
};

/**
 * @param {Object} oMessage
 */
CComposeView.prototype.onMessageResponse = function (oMessage)
{
	var oReplyData = null;

	if (oMessage === null)
	{
		this.setDataFromMessage(new CMessageModel());
	}
	else
	{
		switch (this.routeType())
		{
			case Enums.ReplyType.Reply:
			case Enums.ReplyType.ReplyAll:
				SenderSelector.setFetcherOrIdentityByReplyMessage(oMessage);

				oReplyData = SendingUtils.getReplyDataFromMessage(oMessage, this.routeType(), this.senderAccountId(), this.selectedFetcherOrIdentity(), true);

				this.draftInfo(oReplyData.DraftInfo);
				this.draftUid(oReplyData.DraftUid);
				this.setRecipient(this.toAddr, oReplyData.To);
				this.setRecipient(this.ccAddr, oReplyData.Cc);
				this.setRecipient(this.bccAddr, oReplyData.Bcc);
				this.subject(oReplyData.Subject);
				this.textBody(oReplyData.Text);
				this.attachments(oReplyData.Attachments);
				this.inReplyTo(oReplyData.InReplyTo);
				this.references(oReplyData.References);
				break;

			case Enums.ReplyType.Forward:
				SenderSelector.setFetcherOrIdentityByReplyMessage(oMessage);

				oReplyData = SendingUtils.getReplyDataFromMessage(oMessage, this.routeType(), this.senderAccountId(), this.selectedFetcherOrIdentity(), true);

				this.draftInfo(oReplyData.DraftInfo);
				this.draftUid(oReplyData.DraftUid);
				this.setRecipient(this.toAddr, oReplyData.To);
				this.setRecipient(this.ccAddr, oReplyData.Cc);
				this.subject(oReplyData.Subject);
				this.textBody(oReplyData.Text);
				this.attachments(oReplyData.Attachments);
				this.inReplyTo(oReplyData.InReplyTo);
				this.references(oReplyData.References);
				break;

			case Enums.ReplyType.Resend:
				this.setDataFromMessage(oMessage);
				break;

			case 'drafts':
				this.draftUid(oMessage.uid());
				this.setDataFromMessage(oMessage);
				break;
		}

		this.routeType('');
	}

	if (this.attachments().length > 0)
	{
		this.requestAttachmentsTempName();
	}

	this.visibleCc(this.ccAddr() !== '');
	this.visibleBcc(this.bccAddr() !== '');
	this.commit(true);

	_.defer(_.bind(function () {
		this.focusAfterFilling();
	}, this));

	this.minHeightAdjustTrigger(true);
};

/**
 * @param {Object} oMessage
 */
CComposeView.prototype.setDataFromMessage = function (oMessage)
{
	var
		sTextBody = '',
		oFetcherOrIdentity = SendingUtils.getFirstFetcherOrIdentityByRecipientsOrDefault(oMessage.oFrom.aCollection, oMessage.accountId())
	;

	SenderSelector.changeSenderAccountId(oMessage.accountId(), oFetcherOrIdentity);

	if (oMessage.isPlain())
	{
		sTextBody = oMessage.textRaw();
	}
	else
	{
		sTextBody = oMessage.getConvertedHtml();
	}
	this.draftInfo(oMessage.draftInfo());
	this.inReplyTo(oMessage.inReplyTo());
	this.references(oMessage.references());
	this.setRecipient(this.toAddr, oMessage.oTo.getFull());
	this.setRecipient(this.ccAddr, oMessage.oCc.getFull());
	this.setRecipient(this.bccAddr, oMessage.oBcc.getFull());
	this.subject(oMessage.subject());
	this.attachments(oMessage.attachments());
	this.plainText(oMessage.isPlain());
	this.textBody(sTextBody);
	this.selectedImportance(oMessage.importance());
	this.readingConfirmation(oMessage.readingConfirmation());
	
	_.each(this.toolbarControllers(), function (oController) {
		if ($.isFunction(oController.doAfterPopulatingMessage))
		{
			oController.doAfterPopulatingMessage({
				bDraft: !!oMessage.folderObject() && (oMessage.folderObject().type() === Enums.FolderTypes.Drafts),
				bPlain: oMessage.isPlain(),
				sRawText: oMessage.textRaw(),
				iSensitivity: oMessage.sensitivity()
			});
		}
	});
};

/**
 * @param {string} sData
 * @param {string} sFileName
 */
CComposeView.prototype.addDataAsAttachment = function (sData, sFileName)
{
	var
		sHash = 'data-as-attachment-' + Math.random(),
		oParameters = {
			'Action': 'DataAsAttachmentUpload',
			'Data': sData,
			'FileName': sFileName,
			'Hash': sHash
		},
		oAttach = new CAttachmentModel()
	;

	this.subject(sFileName.substr(0, sFileName.length - 4));

	oAttach.fileName(sFileName);
	oAttach.hash(sHash);
	oAttach.uploadStarted(true);

	this.attachments.push(oAttach);

	this.messageUploadAttachmentsStarted(true);

	Ajax.send(oParameters, this.onDataAsAttachmentUpload, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeView.prototype.onDataAsAttachmentUpload = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		sHash = oRequest.Hash,
		oAttachment = _.find(this.attachments(), function (oAttach) {
			return oAttach.hash() === sHash;
		})
	;

	this.messageUploadAttachmentsStarted(false);

	if (oAttachment)
	{
		if (oResult && oResult.Attachment)
		{
			oAttachment.parseFromUpload(oResult.Attachment, oResponse.AccountID);
		}
		else
		{
			oAttachment.errorFromUpload();
		}
	}
};

/**
 * @param {Array} aFiles
 */
CComposeView.prototype.addFilesAsAttachment = function (aFiles)
{
	var
		oAttach = null,
		aHashes = [],
		oParameters = null
	;

	_.each(aFiles, function (oFile) {
		oAttach = new CAttachmentModel();
		oAttach.fileName(oFile.fileName());
		oAttach.hash(oFile.hash());
		oAttach.thumb(oFile.thumb());
		oAttach.uploadStarted(true);

		this.attachments.push(oAttach);

		aHashes.push(oFile.hash());
	}, this);

	if (aHashes.length > 0)
	{
		oParameters = {
			'Action': 'FilesUpload',
			'Hashes': aHashes
		};

		this.messageUploadAttachmentsStarted(true);

		Ajax.send(oParameters, this.onFilesUpload, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeView.prototype.onFilesUpload = function (oResponse, oRequest)
{
	var
		aResult = oResponse.Result,
		aHashes = oRequest.Hashes,
		sThumbSessionUid = Date.now().toString()
	;
	
	this.messageUploadAttachmentsStarted(false);
	if (_.isArray(aResult))
	{
		_.each(aResult, function (oFileData) {
			var oAttachment = _.find(this.attachments(), function (oAttach) {
				return oAttach.hash() === oFileData.Hash;
			});

			if (oAttachment)
			{
				oAttachment.parseFromUpload(oFileData, oResponse.AccountID);
				oAttachment.hash(oFileData.NewHash);
				oAttachment.getInThumbQueue(sThumbSessionUid);
			}
		}, this);
	}
	else
	{
		_.each(aHashes, function (sHash) {
			var oAttachment = _.find(this.attachments(), function (oAttach) {
				return oAttach.hash() === sHash;
			});

			if (oAttachment)
			{
				oAttachment.errorFromUpload();
			}
		}, this);
	}
};

/**
 * @param {Object} oContact
 */
CComposeView.prototype.addContactAsAttachment = function (oContact)
{
	var
		oAttach = new CAttachmentModel(),
		oParameters = null
	;

	if (oContact)
	{
		oAttach.fileName('contact-' + oContact.idContact() + '.vcf');
		oAttach.uploadStarted(true);

		this.attachments.push(oAttach);

		oParameters = {
			'Action': 'ContactVCardUpload',
			'ContactId': oContact.idContact(),
			'Global': oContact.global() ? '1' : '0',
			'Name': oAttach.fileName(),
			'SharedWithAll': oContact.sharedToAll() ? '1' : '0'
		};

		this.messageUploadAttachmentsStarted(true);

		Ajax.send(oParameters, this.onContactVCardUpload, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeView.prototype.onContactVCardUpload = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		oAttach = null
	;

	this.messageUploadAttachmentsStarted(false);

	if (oResult)
	{
		oAttach = _.find(this.attachments(), function (oAttach) {
			return oAttach.fileName() === oResult.Name && oAttach.uploadStarted();
		});

		if (oAttach)
		{
			oAttach.parseFromUpload(oResult, oResponse.AccountID);
		}
	}
	else
	{
		oAttach = _.find(this.attachments(), function (oAttach) {
			return oAttach.fileName() === oRequest.Name && oAttach.uploadStarted();
		});

		if (oAttach)
		{
			oAttach.errorFromUpload();
		}
	}
};

CComposeView.prototype.requestAttachmentsTempName = function ()
{
	var
		aHash = _.map(this.attachments(), function (oAttach) {
			oAttach.uploadUid(oAttach.hash());
			oAttach.uploadStarted(true);
			return oAttach.hash();
		}),
		oParameters = {
			'Action': 'MessageAttachmentsUpload',
			'Attachments': aHash
		}
	;

	if (aHash.length > 0)
	{
		this.messageUploadAttachmentsStarted(true);

		Ajax.send(oParameters, this.onMessageUploadAttachmentsResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeView.prototype.onMessageUploadAttachmentsResponse = function (oResponse, oRequest)
{
	var aHashes = oRequest.Attachments;

	this.messageUploadAttachmentsStarted(false);

	if (oResponse.Result)
	{
		_.each(oResponse.Result, _.bind(this.setAttachTepmNameByHash, this));
	}
	else
	{
		_.each(aHashes, function (sHash) {
			var oAttachment = _.find(this.attachments(), function (oAttach) {
				return oAttach.hash() === sHash;
			});

			if (oAttachment)
			{
				oAttachment.errorFromUpload();
			}
		}, this);
		Screens.showError(TextUtils.i18n('COMPOSE/UPLOAD_ERROR_REPLY_ATTACHMENTS'));
	}
};

/**
 * @param {string} sHash
 * @param {string} sTempName
 */
CComposeView.prototype.setAttachTepmNameByHash = function (sHash, sTempName)
{
	_.each(this.attachments(), function (oAttach) {
		if (oAttach.hash() === sHash)
		{
			oAttach.tempName(sTempName);
			oAttach.uploadStarted(false);
		}
	});
};

/**
 * @param {Object} oParameters
 */
CComposeView.prototype.setMessageDataInNewTab = function (oParameters)
{
	this.draftInfo(oParameters.draftInfo);
	this.draftUid(oParameters.draftUid);
	this.inReplyTo(oParameters.inReplyTo);
	this.references(oParameters.references);
	this.setRecipient(this.toAddr, oParameters.toAddr);
	this.setRecipient(this.ccAddr, oParameters.ccAddr);
	this.setRecipient(this.bccAddr, oParameters.bccAddr);
	this.subject(oParameters.subject);
	this.attachments(_.map(oParameters.attachments, function (oRawAttach) {
		var oAttach = new CAttachmentModel();
		oAttach.parse(oRawAttach, this.senderAccountId());
		return oAttach;
	}, this));
	this.textBody(oParameters.textBody);
	this.selectedImportance(oParameters.selectedImportance);
	this.readingConfirmation(oParameters.readingConfirmation);
	this.changedInPreviousWindow(oParameters.changedInPreviousWindow);

	_.each(this.toolbarControllers(), function (oController) {
		if ($.isFunction(oController.doAfterApplyingMainTabParameters))
		{
			oController.doAfterApplyingMainTabParameters(oParameters);
		}
	});
	
	SenderSelector.changeSenderAccountId(oParameters.senderAccountId, oParameters.selectedFetcherOrIdentity);
	this.focusedField(oParameters.focusedField);
};

/**
 * @param {boolean=} bOnlyCurrentWindow = false
 */
CComposeView.prototype.commit = function (bOnlyCurrentWindow)
{
	this.toAddr.commit();
	this.ccAddr.commit();
	this.bccAddr.commit();
	this.subject.commit();
	this.oHtmlEditor.commit();
	this.attachmentsChanged(false);
	if (!bOnlyCurrentWindow)
	{
		this.changedInPreviousWindow(false);
	}
};

CComposeView.prototype.isChanged = function ()
{
	var
		toAddr = this.toAddr.changed(),
		ccAddr = this.ccAddr.changed(),
		bccAddr = this.bccAddr.changed(),
		subject = this.subject.changed(),
		oHtmlEditor = this.oHtmlEditor.textChanged(),
		attachmentsChanged = this.attachmentsChanged(),
		changedInPreviousWindow = this.changedInPreviousWindow()
	;

	return toAddr || ccAddr || bccAddr ||
			subject || oHtmlEditor ||
			attachmentsChanged || changedInPreviousWindow;
};

CComposeView.prototype.executeBackToList = function ()
{
	if (App.isNewTab())
	{
		window.close();
	}
	else if (!!this.shown && this.shown())
	{
		Routing.setPreviousHash();
	}
	this.backToListOnSendOrSave(false);
};

/**
 * Creates new attachment for upload.
 *
 * @param {string} sFileUid
 * @param {Object} oFileData
 */
CComposeView.prototype.onFileUploadSelect = function (sFileUid, oFileData)
{
	var oAttach;

	if (FilesUtils.showErrorIfAttachmentSizeLimit(oFileData.FileName, Utils.pInt(oFileData.Size)))
	{
		return false;
	}
	
	oAttach = new CAttachmentModel();
	oAttach.onUploadSelect(sFileUid, oFileData);
	this.attachments.push(oAttach);

	return true;
};

/**
 * Returns attachment found by uid.
 *
 * @param {string} sFileUid
 */
CComposeView.prototype.getAttachmentByUid = function (sFileUid)
{
	return _.find(this.attachments(), function (oAttach) {
		return oAttach.uploadUid() === sFileUid;
	});
};

/**
 * Finds attachment by uid. Calls it's function to start upload.
 *
 * @param {string} sFileUid
 */
CComposeView.prototype.onFileUploadStart = function (sFileUid)
{
	var oAttach = this.getAttachmentByUid(sFileUid);

	if (oAttach)
	{
		oAttach.onUploadStart();
	}
};

/**
 * Finds attachment by uid. Calls it's function to progress upload.
 *
 * @param {string} sFileUid
 * @param {number} iUploadedSize
 * @param {number} iTotalSize
 */
CComposeView.prototype.onFileUploadProgress = function (sFileUid, iUploadedSize, iTotalSize)
{
	var oAttach = this.getAttachmentByUid(sFileUid);

	if (oAttach)
	{
		oAttach.onUploadProgress(iUploadedSize, iTotalSize);
	}
};

/**
 * Finds attachment by uid. Calls it's function to complete upload.
 *
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResult
 */
CComposeView.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResult)
{
	var
		oAttach = this.getAttachmentByUid(sFileUid),
		sThumbSessionUid = Date.now().toString()
	;

	if (oAttach)
	{
		oAttach.onUploadComplete(sFileUid, bResponseReceived, oResult);
		if (oAttach.type().substr(0, 5) === 'image')
		{
			oAttach.thumb(true);
			oAttach.getInThumbQueue(sThumbSessionUid);
		}
	}
};

/**
 * Finds attachment by uid. Calls it's function to cancel upload.
 *
 * @param {string} sFileUid
 */
CComposeView.prototype.onFileRemove = function (sFileUid)
{
	var oAttach = this.getAttachmentByUid(sFileUid);

	if (this.oJua)
	{
		this.oJua.cancel(sFileUid);
	}

	this.attachments.remove(oAttach);
};

/**
 * Initializes file uploader.
 */
CComposeView.prototype.initUploader = function ()
{
	if (this.composeShown() && this.composeUploaderButton() && this.oJua === null)
	{
		this.oJua = new CJua({
			'action': '?/Upload/',
			'name': 'jua-uploader',
			'queueSize': 2,
			'clickElement': this.composeUploaderButton(),
			'hiddenElementsPosition': UserSettings.isRTL ? 'right' : 'left',
			'dragAndDropElement': this.composeUploaderDropPlace(),
			'disableAjaxUpload': false,
			'disableFolderDragAndDrop': false,
			'disableDragAndDrop': false,
			'hidden': {
				'Module': 'Mail',
				'Method': 'UploadAttachment',
				'Token': UserSettings.CsrfToken,
				'AccountID': function () {
					return App.currentAccountId();
				}
			}
		});

		this.oJua
			.on('onDragEnter', _.bind(this.composeUploaderDragOver, this, true))
			.on('onDragLeave', _.bind(this.composeUploaderDragOver, this, false))
			.on('onBodyDragEnter', _.bind(this.composeUploaderBodyDragOver, this, true))
			.on('onBodyDragLeave', _.bind(this.composeUploaderBodyDragOver, this, false))
			.on('onProgress', _.bind(this.onFileUploadProgress, this))
			.on('onSelect', _.bind(this.onFileUploadSelect, this))
			.on('onStart', _.bind(this.onFileUploadStart, this))
			.on('onComplete', _.bind(this.onFileUploadComplete, this))
		;
		
		this.allowDragNDrop(this.oJua.isDragAndDropSupported());
	}
};

/**
 * @param {boolean} bRemoveSignatureAnchor
 */
CComposeView.prototype.getSendSaveParameters = function (bRemoveSignatureAnchor)
{
	var
		oAttachments = SendingUtils.convertAttachmentsForSending(this.attachments()),
		oParameters = null
	;

	_.each(this.oHtmlEditor.uploadedImagePathes(), function (oAttach) {
		oAttachments[oAttach.TempName] = [oAttach.Name, oAttach.CID, '1', '1'];
	});

	oParameters = {
		'AccountID': this.senderAccountId(),
		'FetcherID': this.selectedFetcherOrIdentity() && this.selectedFetcherOrIdentity().FETCHER ? this.selectedFetcherOrIdentity().id() : '',
		'IdentityID': this.selectedFetcherOrIdentity() && !this.selectedFetcherOrIdentity().FETCHER ? this.selectedFetcherOrIdentity().id() : '',
		'DraftInfo': this.draftInfo(),
		'DraftUid': this.draftUid(),
		'To': this.toAddr(),
		'Cc': this.ccAddr(),
		'Bcc': this.bccAddr(),
		'Subject': this.subject(),
		'Text': this.plainText() ? this.oHtmlEditor.getPlainText() : this.oHtmlEditor.getText(bRemoveSignatureAnchor),
		'IsHtml': this.plainText() ? '0' : '1',
		'Importance': this.selectedImportance(),
		'ReadingConfirmation': this.readingConfirmation() ? '1' : '0',
		'Attachments': oAttachments,
		'InReplyTo': this.inReplyTo(),
		'References': this.references()
	};
	
	_.each(this.toolbarControllers(), function (oController) {
		if ($.isFunction(oController.doAfterPreparingSendMessageParameters))
		{
			oController.doAfterPreparingSendMessageParameters(oParameters);
		}
	});
	
	return oParameters;
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeView.prototype.onSendOrSaveMessageResponse = function (oResponse, oRequest)
{
	var
		oResData = SendingUtils.onSendOrSaveMessageResponse(oResponse, oRequest, this.requiresPostponedSending()),
		oParameters = JSON.parse(oRequest.Parameters)
	;

	this.commit();

	switch (oResData.Method)
	{
		case 'SaveMessage':
			if (oResData.Result && oParameters.DraftUid === this.draftUid())
			{
				this.draftUid(Utils.pString(oResData.NewUid));
				
				if (this instanceof CComposeView)// it is screen, not popup
				{
					Routing.replaceHashDirectly(LinksUtils.getComposeFromMessage('drafts', oParameters.DraftFolder, this.draftUid()));
				}
			}
			this.saving(false);
			break;
		case 'SendMessage':
			if (oResData.Result)
			{
				if (this.backToListOnSendOrSave())
				{
					if ($.isFunction(this.closePopup))
					{
						this.closePopup();
					}
					else
					{
						this.executeBackToList();
					}
				}
			}
			this.sending(false);
			break;
	}
};

CComposeView.prototype.verifyDataForSending = function ()
{
	var
		aToIncorrect = AddressUtils.getIncorrectEmailsFromAddressString(this.toAddr()),
		aCcIncorrect = AddressUtils.getIncorrectEmailsFromAddressString(this.ccAddr()),
		aBccIncorrect = AddressUtils.getIncorrectEmailsFromAddressString(this.bccAddr()),
		aIncorrect = _.union(aToIncorrect, aCcIncorrect, aBccIncorrect),
		sWarning = TextUtils.i18n('COMPOSE/WARNING_INPUT_CORRECT_EMAILS') + aIncorrect.join(', ')
	;

	if (aIncorrect.length > 0)
	{
		Popups.showPopup(AlertPopup, [sWarning]);
		return false;
	}

	return true;
};

/**
 * @param {mixed} mParam
 */
CComposeView.prototype.executeSend = function (mParam)
{
	var
		bCancelSend = false,
		fContinueSending = _.bind(function () {
			this.sending(true);
			this.requiresPostponedSending(!this.allowStartSending());
			
			SendingUtils.send('SendMessage', this.getSendSaveParameters(true), true, this.onSendOrSaveMessageResponse, this, this.requiresPostponedSending());
			
			this.backToListOnSendOrSave(true);
		}, this)
	;

	if (this.isEnableSending() && this.verifyDataForSending())
	{
		_.each(this.toolbarControllers(), function (oController) {
			if ($.isFunction(oController.doBeforeSend))
			{
				bCancelSend = bCancelSend || oController.doBeforeSend(fContinueSending);
			}
		});
		
		if (!bCancelSend)
		{
			fContinueSending();
		}
	}
};

CComposeView.prototype.executeSaveCommand = function ()
{
	this.executeSave(false);
};

/**
 * @param {boolean=} bAutosave = false
 * @param {boolean=} bWaitResponse = true
 */
CComposeView.prototype.executeSave = function (bAutosave, bWaitResponse)
{
	bAutosave = Utils.isUnd(bAutosave) ? false : bAutosave;
	bWaitResponse = Utils.isUnd(bWaitResponse) ? true : bWaitResponse;

	if (bAutosave && MailCache.disableComposeAutosave())
	{
		return;
	}

	var
		fOnSaveMessageResponse = bWaitResponse ? this.onSendOrSaveMessageResponse : SendingUtils.onSendOrSaveMessageResponse,
		oContext = bWaitResponse ? this : SendingUtils,
		fSave = _.bind(function (bSave) {
			if (bSave)
			{
				this.saving(bWaitResponse);
				SendingUtils.send('SaveMessage', this.getSendSaveParameters(false), !bAutosave, fOnSaveMessageResponse, oContext);
			}
		}, this),
		bCancelSaving = false
	;

	if (this.isEnableSaving())
	{
		if (!bAutosave || this.isChanged())
		{
			if (!bAutosave)
			{
				_.each(this.toolbarControllers(), function (oController) {
					if ($.isFunction(oController.doBeforeSave))
					{
						bCancelSaving = bCancelSaving || oController.doBeforeSave(fSave);
					}
				}, this);
			}
			if (!bCancelSaving)
			{
				fSave(true);
			}
		}

		this.backToListOnSendOrSave(true);
	}
};

/**
 * Changes visibility of bcc field.
 */
CComposeView.prototype.changeBccVisibility = function ()
{
	this.visibleBcc(!this.visibleBcc());

	if (this.visibleBcc())
	{
		this.focusBccAddr();
	}
	else
	{
		this.focusToAddr();
	}

};

/**
 * Changes visibility of bcc field.
 */
CComposeView.prototype.changeCcVisibility = function ()
{
	this.visibleCc(!this.visibleCc());

	if (this.visibleCc())
	{
		this.focusCcAddr();
	}
	else
	{
		this.focusToAddr();
	}
};

CComposeView.prototype.getMessageDataForNewTab = function ()
{
	var
		aAttachments = _.map(this.attachments(), function (oAttach)
		{
			return {
				'@Object': 'Object/CApiMailAttachment',
				'FileName': oAttach.fileName(),
				'TempName': oAttach.tempName(),
				'MimeType': oAttach.type(),
				'MimePartIndex': oAttach.mimePartIndex(),
				'EstimatedSize': oAttach.size(),
				'CID': oAttach.cid(),
				'ContentLocation': oAttach.contentLocation(),
				'IsInline': oAttach.inline(),
				'IsLinked': oAttach.linked(),
				'Hash': oAttach.hash()
			};
		}),
		oParameters = null
	;

	oParameters = {
		accountId: this.senderAccountId(),
		draftInfo: this.draftInfo(),
		draftUid: this.draftUid(),
		inReplyTo: this.inReplyTo(),
		references: this.references(),
		senderAccountId: this.senderAccountId(),
		selectedFetcherOrIdentity: this.selectedFetcherOrIdentity(),
		toAddr: this.toAddr(),
		ccAddr: this.ccAddr(),
		bccAddr: this.bccAddr(),
		subject: this.subject(),
		attachments: aAttachments,
		textBody: this.oHtmlEditor.getText(),
		selectedImportance: this.selectedImportance(),
		readingConfirmation: this.readingConfirmation(),
		changedInPreviousWindow: this.isChanged(),
		focusedField: this.focusedField()
	};
	
	_.each(this.toolbarControllers(), function (oController) {
		if ($.isFunction(oController.doAfterPreparingMainTabParameters))
		{
			oController.doAfterPreparingMainTabParameters(oParameters);
		}
	});
	
	return oParameters;
};

CComposeView.prototype.openInNewWindow = function ()
{
	var
		sWinName = 'id' + Math.random().toString(),
		oMessageParametersFromCompose = {},
		oWin = null,
		sHash = Routing.buildHashFromArray(LinksUtils.getCompose())
	;

	this.closeBecauseSingleCompose(true);
	oMessageParametersFromCompose = this.getMessageDataForNewTab();

	if (this.draftUid().length > 0 && !this.isChanged())
	{
		sHash = Routing.buildHashFromArray(LinksUtils.getComposeFromMessage('drafts', MailCache.folderList().draftsFolderFullName(), this.draftUid(), true));
		oWin = WindowOpener.openTab('?message-newtab' + sHash);
	}
	else if (!this.isChanged())
	{
		sHash = Routing.buildHashFromArray(_.union(['mail-compose'], this.routeParams()));
		oWin = WindowOpener.openTab('?message-newtab' + sHash);
	}
	else
	{
		MainTabExtMethods.passComposedMessage(sWinName, oMessageParametersFromCompose);
		oWin = WindowOpener.openTab('?message-newtab' + sHash, sWinName);
	}

	this.commit();

	if ($.isFunction(this.closePopup))
	{
		this.closePopup();
	}
	else
	{
		this.executeBackToList();
	}
};

CComposeView.prototype.onShowFilesPopupClick = function ()
{
	if (SelectFilesPopup)
	{
		Popups.showPopup(SelectFilesPopup, [_.bind(this.addFilesAsAttachment, this)]);
	}
};

CComposeView.prototype.registerOwnToolbarControllers = function ()
{
	this.registerToolbarController({
		ViewTemplate: 'Mail_Compose_BackButtonView',
		sId: 'back',
		bOnlyMobile: true,
		backToListCommand: this.backToListCommand
	});
	this.registerToolbarController({
		ViewTemplate: 'Mail_Compose_SendButtonView',
		sId: 'send',
		bAllowMobile: true,
		sendCommand: this.sendCommand
	});
	this.registerToolbarController({
		ViewTemplate: 'Mail_Compose_SaveButtonView',
		sId: 'save',
		bAllowMobile: true,
		saveCommand: this.saveCommand
	});
	this.registerToolbarController({
		ViewTemplate: 'Mail_Compose_ImportanceDropdownView',
		sId: 'importance',
		selectedImportance: this.selectedImportance
	});
	this.registerToolbarController({
		ViewTemplate: 'Mail_Compose_ConfirmationCheckboxView',
		sId: 'confirmation',
		readingConfirmation: this.readingConfirmation
	});
};

/**
 * @param {Object} oController
 */
CComposeView.prototype.registerToolbarController = function (oController)
{
	var
		bAllowRegister = bMobileApp ? (oController.bOnlyMobile || oController.bAllowMobile) : (!oController.bOnlyMobile),
		iLastIndex = Settings.ComposeToolbarOrder.length
	;
	
	if (bAllowRegister)
	{
		this.toolbarControllers.push(oController);
		this.toolbarControllers(_.sortBy(this.toolbarControllers(), function (oContr) {
			var iIndex = _.indexOf(Settings.ComposeToolbarOrder, oContr.sId);
			return iIndex !== -1 ? iIndex : iLastIndex;
		}));
		if ($.isFunction(oController.assignComposeExtInterface))
		{
			oController.assignComposeExtInterface(this.getExtInterface());
		}
	}
};

/**
 * @returns {Object}
 */
CComposeView.prototype.getExtInterface = function ()
{
	return {
		isHtml: _.bind(function () {
			return !this.plainText();
		}, this),
		hasAttachments: _.bind(function () {
			return this.notInlineAttachments().length > 0;
		}, this),
		getPlainText: _.bind(this.oHtmlEditor.getPlainText, this.oHtmlEditor),
		getFromEmail: _.bind(function () {
			return Accounts.getEmail(this.senderAccountId());
		}, this),
		getRecipientEmails: _.bind(function () {
			return this.recipientEmails();
		}, this),
		
		saveSilently: _.bind(this.executeSave, this, true),
		setPlainTextMode: _.bind(this.plainText, this, true),
		setPlainText: _.bind(function (sText) {
			this.textBody(sText);
		}, this),
		setHtmlTextMode: _.bind(this.plainText, this, false),
		setHtmlText: _.bind(function (sHtml) {
			this.textBody(sHtml);
		}, this),
		undoHtml: _.bind(this.oHtmlEditor.undoAndClearRedo, this.oHtmlEditor)
	};
};

module.exports = CComposeView;