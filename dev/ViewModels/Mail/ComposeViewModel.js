
/**
 * @constructor
 */
function CComposeViewModel()
{
    var self = this;

	if (AppData.SingleMode)
	{
		Utils.changeFavicon("favicon-single-compose.ico");
	}

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

    this.folderList = App.MailCache.folderList;
    this.folderList.subscribe(function () {
        this.getMessageOnRoute();
    }, this);

    this.singleMode = ko.observable(AppData.SingleMode);
    this.isDemo = ko.observable(AppData.User.IsDemo);

    this.sending = ko.observable(false);
    this.sending.subscribe(this.sendingAndSavingSubscription, this);
    this.saving = ko.observable(false);
    this.saving.subscribe(this.sendingAndSavingSubscription, this);

    this.oHtmlEditor = new CHtmlEditorViewModel(false, this);
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
    this.saveMailInSentItems = ko.observable(true);
    this.useSaveMailInSentItems = ko.observable(false);

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
    this.selectedSensitivity = ko.observable(Enums.Sensitivity.Nothing);

    this.oSenderSelector = new CSenderSelector();
    this.senderAccountId = this.oSenderSelector.senderAccountId;
    this.senderList = this.oSenderSelector.senderList;
    this.visibleFrom = ko.computed(function () {
        return this.senderList().length > 1;
    }, this);
    this.selectedSender = this.oSenderSelector.selectedSender;
    this.selectedFetcherOrIdentity = this.oSenderSelector.selectedFetcherOrIdentity;
    this.selectedFetcherOrIdentity.subscribe(function () {
        if (!this.oHtmlEditor.isEditing())
        {
            this.oHtmlEditor.clearUndoRedo();
        }
    }, this);

    this.signature = ko.observable('');
    this.prevSignature = ko.observable(null);
    ko.computed(function () {
        var sSignature = App.MessageSender.getClearSignature(this.senderAccountId(), this.selectedFetcherOrIdentity());

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
                sTrimmedRecip = Utils.trim(sRecip),
                oRecip = null
                ;
            if (sTrimmedRecip !== '')
            {
                oRecip = Utils.Address.getEmailParts(sTrimmedRecip);
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
        App.MailCache.editedDraftUid(this.draftUid());
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
                App.Api.showLoading(Utils.i18n('COMPOSE/INFO_ATTACHMENTS_LOADING'));
            }, 4000);
        }
        else
        {
            if (self.bUploadStatus)
            {
                self.iUploadAttachmentsTimer = window.setTimeout(function () {
                    self.bUploadStatus = false;
                    App.Api.hideLoading();
                }, 1000);
            }
            else
            {
                App.Api.hideLoading();
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
            App.MessageSender.sendPostponedMail(this.draftUid());
            this.requiresPostponedSending(false);
        }
    }, this);
    this.requiresPostponedSending = ko.observable(false);

    // file uploader
    this.oJua = null;

    this.isDraftsCleared = ko.observable(false);
    this.autoSaveTimer = -1;

    this.shown = ko.observable(false);
    this.shown.subscribe(function () {
        if (!this.shown())
        {
            this.stopAutosaveTimer();
        }
    }, this);
    this.backToListOnSendOrSave = ko.observable(false);

    this.enableOpenPgp = AppData.User.enableOpenPgp;
    this.pgpSecured = ko.observable(false);
    this.pgpSecured.subscribe(function () {
        this.oHtmlEditor.disabled(this.pgpSecured());
    }, this);
    this.pgpEncrypted = ko.observable(false);
    this.fromDrafts = ko.observable(false);
    this.visibleDoPgpButton = ko.computed(function () {
        return this.enableOpenPgp() && (!this.pgpSecured() || this.pgpEncrypted() && this.fromDrafts());
    }, this);
    this.visibleUndoPgpButton = ko.computed(function () {
        return this.enableOpenPgp() && this.pgpSecured() && (!this.pgpEncrypted() || !this.fromDrafts());
    }, this);
    this.isEnableOpenPgpCommand = ko.computed(function () {
        return this.enableOpenPgp() && !this.pgpSecured();
    }, this);

    this.backToListCommand = Utils.createCommand(this, this.executeBackToList);
    this.sendCommand = Utils.createCommand(this, this.executeSend, this.isEnableSending);
    this.saveCommand = Utils.createCommand(this, this.executeSaveCommand, this.isEnableSaving);
    this.openPgpCommand = Utils.createCommand(this, this.confirmOpenPgp, this.isEnableOpenPgpCommand);

    this.messageFields = ko.observable(null);
    this.bottomPanel = ko.observable(null);

    this.mobileApp = bMobileApp;
    this.showHotkeysHints = !bMobileDevice && !bMobileApp;

    this.aHotkeys = [
        { value: 'Ctrl+Enter', action: Utils.i18n('COMPOSE/HOTKEY_SEND') },
        { value: 'Ctrl+S', action: Utils.i18n('COMPOSE/HOTKEY_SAVE') },
        { value: 'Ctrl+Z', action: Utils.i18n('COMPOSE/HOTKEY_UNDO') },
        { value: 'Ctrl+Y', action: Utils.i18n('COMPOSE/HOTKEY_REDO') },
        { value: 'Ctrl+K', action: Utils.i18n('COMPOSE/HOTKEY_LINK') },
        { value: 'Ctrl+B', action: Utils.i18n('COMPOSE/HOTKEY_BOLD') },
        { value: 'Ctrl+I', action: Utils.i18n('COMPOSE/HOTKEY_ITALIC') },
        { value: 'Ctrl+U', action: Utils.i18n('COMPOSE/HOTKEY_UNDERLINE') }
    ];

    this.allowFiles = ko.observable(false);

    this.closeBecauseSingleCompose = ko.observable(false);
    this.changedInPreviousWindow = ko.observable(false);

    this.minHeightAdjustTrigger = ko.observable(false).extend({'autoResetToFalse': 105});
    this.minHeightRemoveTrigger = ko.observable(false).extend({'autoResetToFalse': 105});
    this.jqContainers = $('.pSevenMain:first, .popup.compose_popup');
    ko.computed(function () {
        this.minHeightAdjustTrigger();
        this.minHeightRemoveTrigger();
        _.delay(function () {
            $('.compose_popup .panel_content .panels').trigger('resize');
        }, 200);
    }, this);

    this.hasSomethingToSave = ko.computed(function () {
        return this.isChanged() && this.isEnableSaving();
    }, this);

    this.saveAndCloseTooltip = ko.computed(function () {
        return this.hasSomethingToSave() ? Utils.i18n('COMPOSE/TOOL_SAVE_CLOSE') : Utils.i18n('COMPOSE/TOOL_CLOSE');
    }, this);

    if (window.opener)
    {
        setTimeout(function() {
            window.onbeforeunload = function(){
                if (self.hasSomethingToSave())
                {
                    self.beforeHide(window.close);
                    return '';
                }
            };
        }, 1000);
    }

    this.splitterDom = ko.observable();

    this.fromToExpandColapssed = ko.observable(false);
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CComposeViewModel.prototype.__name = 'CComposeViewModel';

/**
 * Determines if sending a message is allowed.
 */
CComposeViewModel.prototype.isEnableSending = function ()
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
CComposeViewModel.prototype.isEnableSaving = function ()
{
    var bFoldersLoaded = this.folderList() && this.folderList().iAccountId !== 0;

    return this.shown() && bFoldersLoaded && !this.sending() && !this.saving();
};

/**
 * @param {Object} koAddrDom
 * @param {Object} koAddr
 * @param {Object} koLockAddr
 * @param {string} sFocusedField
 */
CComposeViewModel.prototype.initInputosaurus = function (koAddrDom, koAddr, koLockAddr, sFocusedField)
{
    if (koAddrDom() && $(koAddrDom()).length > 0)
    {
        $(koAddrDom()).inputosaurus({
            width: 'auto',
            parseOnBlur: true,
            autoCompleteSource: _.bind(function (oData, fResponse) {
                this.autocompleteCallback(oData.term, fResponse);
            }, this),
			autoCompleteDeleteItem: _.bind(function (oContact) {
                this.autocompleteDeleteItem(oContact);
            }, this),
            autoCompleteAppendTo : $(koAddrDom()).closest('td'),
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
            mobileDevice: bMobileDevice
        });
    }
};

/**
 * Colapse from to table.
 */
CComposeViewModel.prototype.fromToExpandColaps = function ()
{
    this.fromToExpandColapssed(!this.fromToExpandColapssed());
};

/**
 * Executes after applying bindings.
 */
CComposeViewModel.prototype.onApplyBindings = function ()
{
	this.$viewModel.on('resize', '.panel_content', _.debounce(_.bind(function () {
		this.oHtmlEditor.resize();
	}, this), 1));
	
    App.registerSessionTimeoutFunction(_.bind(this.executeSave, this, false));

    this.hotKeysBind();
};

CComposeViewModel.prototype.hotKeysBind = function ()
{
    this.$viewModel.on('keydown', $.proxy(function(ev) {

        if (ev && ev.ctrlKey && !ev.altKey && !ev.shiftKey)
        {
            var
                nKey = ev.keyCode,
                bShown = this.shown() && (!this.minimized || !this.minimized()),
                bComputed = bShown && ev && ev.ctrlKey,
                oEnumsKey = Enums.Key
			;

            if (bComputed && nKey === oEnumsKey.s)
            {
                ev.preventDefault();
                ev.returnValue = false;

                if (this.isEnableSaving())
                {
                    this.saveCommand();
                }
            }
            else if (bComputed && nKey === oEnumsKey.Enter && this.toAddr() !== '')
            {
                this.sendCommand();
            }
        }

    },this));
};

CComposeViewModel.prototype.getMessageOnRoute = function ()
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

        App.MailCache.getMessage(sFolderName, sUid, this.onMessageResponse, this);
    }
};

/**
 * Executes if the view model shows. Requests a folder list from the server to know the full names
 * of the folders Drafts and Sent Items.
 */
CComposeViewModel.prototype.onShow = function ()
{
    var sFocusedField = this.focusedField();
    this.shown(true);

    $(this.splitterDom()).trigger('resize');

    this.useSaveMailInSentItems(AppData.User.getUseSaveMailInSentItems());
    this.saveMailInSentItems(AppData.User.getSaveMailInSentItems());

    this.oHtmlEditor.initCrea(this.textBody(), this.plainText(), '7');
    this.oHtmlEditor.commit();

    this.initUploader();

    this.backToListOnSendOrSave(false);
    this.startAutosaveTimer();

    this.focusedField(sFocusedField);//oHtmlEditor initialization puts focus on it and changes the variable focusedField

    $html.addClass('screen-compose');

    if (this.oJua)
    {
        this.oJua.setDragAndDropEnabledStatus(true);
    }
};

/**
 * Executes if routing changed.
 *
 * @param {Array} aParams
 */
CComposeViewModel.prototype.onRoute = function (aParams)
{
    var
        sSignature = '',
        oToAddr = {}
        ;

    this.plainText(false);
    this.pgpSecured(false);
    this.pgpEncrypted(false);
    this.fromDrafts(false);

    this.bUploadStatus = false;
    window.clearTimeout(this.iUploadAttachmentsTimer);
    this.messageUploadAttachmentsStarted(false);

    this.draftUid('');
    this.draftInfo.removeAll();
    this.setDataFromMessage(new CMessageModel());

    this.isDraftsCleared(false);

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
            sSignature = App.MessageSender.getSignatureText(this.senderAccountId(), this.selectedFetcherOrIdentity(), true);

            if (AppData.SingleMode && window.opener && window.opener.aMessagesParametersFromCompose && window.opener.aMessagesParametersFromCompose[window.name])
            {
                this.setMessageDataInSingleMode(window.opener.aMessagesParametersFromCompose[window.name]);
            }
            else if (sSignature !== '')
            {
                this.textBody('<br /><br />' + sSignature + '<br />');
            }

            if (this.routeType() === 'to' && aParams.length === 2)
            {
                oToAddr = App.Links.parseToAddr(aParams[1]);
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

            break;
    }

    this.visibleCc(this.ccAddr() !== '');
    this.visibleBcc(this.bccAddr() !== '');
    this.commit(true);

    this.allowFiles(AppData.User.IsFilesSupported && AppData.User.filesEnable());

    if (AppData.SingleMode && this.changedInPreviousWindow())
    {
        _.defer(_.bind(this.executeSave, this, true));
    }
};

CComposeViewModel.prototype.focusToAddr = function ()
{
    $(this.toAddrDom()).inputosaurus('focus');
};

CComposeViewModel.prototype.focusCcAddr = function ()
{
    $(this.ccAddrDom()).inputosaurus('focus');
};

CComposeViewModel.prototype.focusBccAddr = function ()
{
    $(this.bccAddrDom()).inputosaurus('focus');
};

CComposeViewModel.prototype.focusAfterFilling = function ()
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
CComposeViewModel.prototype.beforeHide = function (fContinueScreenChanging)
{
    var
        sConfirm = Utils.i18n('COMPOSE/CONFIRM_DISCARD_CHANGES'),
        fOnConfirm = _.bind(function (bOk) {
            if (bOk && Utils.isFunc(fContinueScreenChanging))
            {
                this.commit();
                fContinueScreenChanging();
            }
            else
            {
                App.Routing.historyBackWithoutParsing(Enums.Screens.Compose);
            }
        }, this)
        ;

    if (!this.closeBecauseSingleCompose() && this.hasSomethingToSave())
    {
        App.Screens.showPopup(ConfirmPopup, [sConfirm, fOnConfirm]);
    }
    else if (Utils.isFunc(fContinueScreenChanging))
    {
        fContinueScreenChanging();
    }
};

/**
 * Executes if view model was hidden.
 */
CComposeViewModel.prototype.onHide = function ()
{
    this.stopAutosaveTimer();

    if (!Utils.isFunc(this.closeCommand) && this.hasSomethingToSave())
    {
        this.executeSave(true);
    }

    this.fromToExpandColapssed(false);

    this.shown(false);

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

CComposeViewModel.prototype.sendingAndSavingSubscription = function ()
{
    if (this.sending() || this.saving())
    {
        this.stopAutosaveTimer();
    }
    if (!this.sending() && !this.saving())
    {
        this.startAutosaveTimer();
    }
};

/**
 * Stops autosave.
 */
CComposeViewModel.prototype.stopAutosaveTimer = function ()
{
    window.clearTimeout(this.autoSaveTimer);
};

/**
 * Starts autosave.
 */
CComposeViewModel.prototype.startAutosaveTimer = function ()
{
    if (this.shown() && !this.pgpSecured())
    {
        var fSave = _.bind(this.executeSave, this, true);
        this.stopAutosaveTimer();
        if (AppData.User.AllowAutosaveInDrafts)
        {
            this.autoSaveTimer = window.setTimeout(fSave, AppData.App.AutoSaveIntervalSeconds * 1000);
        }
    }
};

/**
 * @param {Object} koRecipient
 * @param {string} sRecipient
 */
CComposeViewModel.prototype.setRecipient = function (koRecipient, sRecipient)
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
CComposeViewModel.prototype.onMessageResponse = function (oMessage)
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
                this.oSenderSelector.setFetcherOrIdentityByReplyMessage(oMessage);

                oReplyData = App.MessageSender.getReplyDataFromMessage(oMessage, this.routeType(), this.senderAccountId(), this.selectedFetcherOrIdentity(), true);

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
                this.oSenderSelector.setFetcherOrIdentityByReplyMessage(oMessage);

                oReplyData = App.MessageSender.getReplyDataFromMessage(oMessage, this.routeType(), this.senderAccountId(), this.selectedFetcherOrIdentity(), true);

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
                this.fromDrafts(true);
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
CComposeViewModel.prototype.setDataFromMessage = function (oMessage)
{
    var
        sTextBody = '',
        bPgpEncrypted = false,
        bPgpSigned = false,
        oFetcherOrIdentity = App.MessageSender.getFirstFetcherOrIdentityByRecipientsOrDefault(oMessage.oFrom.aCollection, oMessage.accountId())
        ;

    this.oSenderSelector.changeSenderAccountId(oMessage.accountId(), oFetcherOrIdentity);

    if (oMessage.isPlain())
    {
        bPgpEncrypted = oMessage.textRaw().indexOf('-----BEGIN PGP MESSAGE-----') !== -1;
        bPgpSigned = oMessage.textRaw().indexOf('-----BEGIN PGP SIGNED MESSAGE-----') !== -1;
        if (bPgpSigned || bPgpEncrypted)
        {
            sTextBody = oMessage.textRaw();
            this.pgpSecured(true);
            this.pgpEncrypted(bPgpEncrypted);
        }
        else
        {
            sTextBody = oMessage.textRaw();
        }
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
    this.selectedSensitivity(oMessage.sensitivity());
    this.readingConfirmation(oMessage.readingConfirmation());
};

/**
 * @param {string} sData
 * @param {string} sFileName
 */
CComposeViewModel.prototype.addDataAsAttachment = function (sData, sFileName)
{
    var
        sHash = 'data-as-attachment-' + Math.random(),
        oParameters = {
            'Action': 'DataAsAttachmentUpload',
            'Data': sData,
            'FileName': sFileName,
            'Hash': sHash
        },
        oAttach = new CMailAttachmentModel()
        ;

    this.subject(sFileName.substr(0, sFileName.length - 4));

    oAttach.fileName(sFileName);
    oAttach.hash(sHash);
    oAttach.uploadStarted(true);

    this.attachments.push(oAttach);

    this.messageUploadAttachmentsStarted(true);

    App.Ajax.send(oParameters, this.onDataAsAttachmentUpload, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeViewModel.prototype.onDataAsAttachmentUpload = function (oResponse, oRequest)
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
CComposeViewModel.prototype.addFilesAsAttachment = function (aFiles)
{
    var
        oAttach = null,
        aHashes = [],
        oParameters = null
        ;

    _.each(aFiles, function (oFile) {
        oAttach = new CMailAttachmentModel();
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

        App.Ajax.send(oParameters, this.onFilesUpload, this);
    }
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeViewModel.prototype.onFilesUpload = function (oResponse, oRequest)
{
    var
        aResult = oResponse.Result,
        aHashes = oRequest.Hashes,
        sThumbSessionUid = Date.now().toString()
        ;
    this.messageUploadAttachmentsStarted(false);
    if ($.isArray(aResult))
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
CComposeViewModel.prototype.addContactAsAttachment = function (oContact)
{
    var
        oAttach = new CMailAttachmentModel(),
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
			'SharedWithAll': oContact.sharedToAll() ? '1' : '0',
        };

        this.messageUploadAttachmentsStarted(true);

        App.Ajax.send(oParameters, this.onContactVCardUpload, this);
    }
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeViewModel.prototype.onContactVCardUpload = function (oResponse, oRequest)
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

CComposeViewModel.prototype.requestAttachmentsTempName = function ()
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

        App.Ajax.send(oParameters, this.onMessageUploadAttachmentsResponse, this);
    }
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeViewModel.prototype.onMessageUploadAttachmentsResponse = function (oResponse, oRequest)
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
        App.Api.showError(Utils.i18n('COMPOSE/UPLOAD_ERROR_REPLY_ATTACHMENTS'));
    }
};

/**
 * @param {string} sHash
 * @param {string} sTempName
 */
CComposeViewModel.prototype.setAttachTepmNameByHash = function (sHash, sTempName)
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
CComposeViewModel.prototype.setMessageDataInSingleMode = function (oParameters)
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
        var oAttach = new CMailAttachmentModel();
        oAttach.parse(oRawAttach, this.senderAccountId());
        return oAttach;
    }, this));
    this.textBody(oParameters.textBody);
    this.selectedImportance(oParameters.selectedImportance);
    this.selectedSensitivity(oParameters.selectedSensitivity);
    this.readingConfirmation(oParameters.readingConfirmation);
    this.changedInPreviousWindow(oParameters.changedInPreviousWindow);

    this.oSenderSelector.changeSenderAccountId(oParameters.senderAccountId, oParameters.selectedFetcherOrIdentity);
    this.focusedField(oParameters.focusedField);
};

/**
 * @param {boolean=} bOnlyCurrentWindow = false
 */
CComposeViewModel.prototype.commit = function (bOnlyCurrentWindow)
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

CComposeViewModel.prototype.isChanged = function ()
{
    var toAddr = this.toAddr.changed(),
        ccAddr = this.ccAddr.changed(),
        bccAddr = this.bccAddr.changed(),
        subject = this.subject.changed(),
        oHtmlEditor = this.oHtmlEditor.textChanged(),
        attachmentsChanged = this.attachmentsChanged(),
        changedInPreviousWindow = this.changedInPreviousWindow();

    return toAddr || ccAddr || bccAddr ||
            subject || oHtmlEditor ||
            attachmentsChanged || changedInPreviousWindow;
};

CComposeViewModel.prototype.executeBackToList = function ()
{
    if (AppData.SingleMode)
    {
        window.close();
    }
    else if (this.shown())
    {
        App.Routing.setPreviousHash();
    }
    this.backToListOnSendOrSave(false);
};

/**
 * Creates new attachment for upload.
 *
 * @param {string} sFileUid
 * @param {Object} oFileData
 */
CComposeViewModel.prototype.onFileUploadSelect = function (sFileUid, oFileData)
{
    var oAttach;

	if (App.Api.showErrorIfAttachmentSizeLimit(oFileData.FileName, Utils.pInt(oFileData.Size)))
    {
        return false;
    }
	
    oAttach = new CMailAttachmentModel();
    oAttach.onUploadSelect(sFileUid, oFileData);
    this.attachments.push(oAttach);

    return true;
};

/**
 * Returns attachment found by uid.
 *
 * @param {string} sFileUid
 */
CComposeViewModel.prototype.getAttachmentByUid = function (sFileUid)
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
CComposeViewModel.prototype.onFileUploadStart = function (sFileUid)
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
CComposeViewModel.prototype.onFileUploadProgress = function (sFileUid, iUploadedSize, iTotalSize)
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
CComposeViewModel.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResult)
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
CComposeViewModel.prototype.onFileRemove = function (sFileUid)
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
CComposeViewModel.prototype.initUploader = function ()
{
    if (this.shown() && this.composeUploaderButton() && this.oJua === null)
    {
        this.oJua = new Jua({
            'action': '?/Upload/Attachment/',
            'name': 'jua-uploader',
            'queueSize': 2,
            'clickElement': this.composeUploaderButton(),
            'hiddenElementsPosition': Utils.isRTL() ? 'right' : 'left',
            'dragAndDropElement': this.composeUploaderDropPlace(),
            'disableAjaxUpload': false,
            'disableFolderDragAndDrop': false,
            'disableDragAndDrop': false,
            'hidden': {
                'Token': function () {
                    return AppData.Token;
                },
                'AccountID': function () {
                    return AppData.Accounts.currentId();
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
CComposeViewModel.prototype.getSendSaveParameters = function (bRemoveSignatureAnchor)
{
    var
        oAttachments = App.MessageSender.convertAttachmentsForSending(this.attachments())
        ;

    _.each(this.oHtmlEditor.uploadedImagePathes(), function (oAttach) {
        oAttachments[oAttach.TempName] = [oAttach.Name, oAttach.CID, '1', '1'];
    });

    return {
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
        'Sensitivity': this.selectedSensitivity(),
        'ReadingConfirmation': this.readingConfirmation() ? '1' : '0',
        'Attachments': oAttachments,
        'InReplyTo': this.inReplyTo(),
        'References': this.references()
    };
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CComposeViewModel.prototype.onMessageSendOrSaveResponse = function (oResponse, oRequest)
{
    var oResData = App.MessageSender.onMessageSendOrSaveResponse(oResponse, oRequest, this.requiresPostponedSending());

    this.commit();

    switch (oResData.Action)
    {
        case 'MessageSave':
            if (oResData.Result && oRequest.DraftUid === this.draftUid())
            {
                this.draftUid(Utils.pString(oResData.NewUid));
                if (AppData.SingleMode)
                {
                    App.Routing.replaceHashDirectly(App.Links.composeFromMessage('drafts', oRequest.DraftFolder, this.draftUid()));
                }
            }
            this.saving(false);
            break;
        case 'MessageSend':
            if (oResData.Result)
            {
                if (this.backToListOnSendOrSave())
                {
                    if (Utils.isFunc(this.closeCommand))
                    {
                        this.closeCommand();
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

CComposeViewModel.prototype.verifyDataForSending = function ()
{
    var
        aToIncorrect = Utils.Address.getIncorrectEmailsFromAddressString(this.toAddr()),
        aCcIncorrect = Utils.Address.getIncorrectEmailsFromAddressString(this.ccAddr()),
        aBccIncorrect = Utils.Address.getIncorrectEmailsFromAddressString(this.bccAddr()),
        aIncorrect = _.union(aToIncorrect, aCcIncorrect, aBccIncorrect),
        sWarning = Utils.i18n('COMPOSE/WARNING_INPUT_CORRECT_EMAILS') + aIncorrect.join(', ')
        ;

    if (aIncorrect.length > 0)
    {
        App.Screens.showPopup(AlertPopup, [sWarning]);
        return false;
    }

    return true;
};

/**
 * @param {mixed} mParam
 */
CComposeViewModel.prototype.executeSend = function (mParam)
{
    var bAlreadySigned = (mParam === true);

    if (this.isEnableSending() && this.verifyDataForSending())
    {
        if (!bAlreadySigned && this.enableOpenPgp() && AppData.User.AutosignOutgoingEmails && !this.pgpSecured())
        {
            this.openPgpPopup(true);
        }
        else
        {
            this.sending(true);
            this.requiresPostponedSending(!this.allowStartSending());

            App.MessageSender.send('MessageSend', this.getSendSaveParameters(true), this.saveMailInSentItems(),
                true, this.onMessageSendOrSaveResponse, this, this.requiresPostponedSending());
        }

        this.backToListOnSendOrSave(true);
    }
};

CComposeViewModel.prototype.executeSaveCommand = function ()
{
    this.executeSave(false);
};

/**
 * @param {boolean=} bAutosave = false
 * @param {boolean=} bWaitResponse = true
 */
CComposeViewModel.prototype.executeSave = function (bAutosave, bWaitResponse)
{
    bAutosave = Utils.isUnd(bAutosave) ? false : bAutosave;
    bWaitResponse = Utils.isUnd(bWaitResponse) ? true : bWaitResponse;

    if (bAutosave && App.MailCache.disableComposeAutosave())
    {
        return;
    }

    var
        sConfirm = Utils.i18n('OPENPGP/CONFIRM_SAVE_ENCRYPTED_DRAFT'),
        sOkButton = Utils.i18n('COMPOSE/TOOL_SAVE'),
        fSave = _.bind(function (bSave) {
            if (bSave)
            {
                if (bWaitResponse)
                {
                    this.saving(true);
                    App.MessageSender.send('MessageSave', this.getSendSaveParameters(false), this.saveMailInSentItems(),
                        !bAutosave, this.onMessageSendOrSaveResponse, this);
                }
                else
                {
                    App.MessageSender.send('MessageSave', this.getSendSaveParameters(false), this.saveMailInSentItems(),
                        !bAutosave, App.MessageSender.onMessageSendOrSaveResponse, App.MessageSender);
                }
            }
        }, this)
        ;

    if (this.isEnableSaving())
    {
        if (!bAutosave || this.isChanged())
        {
            if (!bAutosave && this.pgpSecured())
            {
                App.Screens.showPopup(ConfirmPopup, [sConfirm, fSave, '', sOkButton]);
            }
            else
            {
                fSave(true);
            }
        }
        else if (bAutosave)
        {
            this.startAutosaveTimer();
        }

        this.backToListOnSendOrSave(true);
    }
};

/**
 * Changes visibility of bcc field.
 */
CComposeViewModel.prototype.changeBccVisibility = function ()
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
CComposeViewModel.prototype.changeCcVisibility = function ()
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

CComposeViewModel.prototype.getMessageDataForSingleMode = function ()
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
        })
        ;

    return {
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
        selectedSensitivity: this.selectedSensitivity(),
        readingConfirmation: this.readingConfirmation(),
        changedInPreviousWindow: this.isChanged(),
        focusedField: this.focusedField()
    };
};

CComposeViewModel.prototype.openInNewWindow = function ()
{
    var
        sWinName = 'id' + Math.random().toString(),
        oMessageParametersFromCompose = {},
        oWin = null,
        sHash = '#' + Enums.Screens.SingleCompose
        ;

    this.closeBecauseSingleCompose(true);
    oMessageParametersFromCompose = this.getMessageDataForSingleMode();

    if (this.draftUid().length > 0 && !this.isChanged())
    {
        sHash = App.Routing.buildHashFromArray(App.Links.composeFromMessage('drafts', App.MailCache.folderList().draftsFolderFullName(), this.draftUid(), true));
        oWin = Utils.WindowOpener.openTab(sHash);
    }
    else if (!this.isChanged())
    {
        sHash = App.Routing.buildHashFromArray(_.union([Enums.Screens.SingleCompose], this.routeParams()));
        oWin = Utils.WindowOpener.openTab(sHash);
    }
    else
    {
        if (!window.aMessagesParametersFromCompose)
        {
            window.aMessagesParametersFromCompose = [];
        }
        window.aMessagesParametersFromCompose[sWinName] = oMessageParametersFromCompose;
        oWin = Utils.WindowOpener.openTab(sHash, sWinName);
    }

    this.commit();

    if (Utils.isFunc(this.closeCommand))
    {
        this.closeCommand();
    }
    else
    {
        this.executeBackToList();
    }
};

/**
 * @param {string} sTerm
 * @param {Function} fResponse
 */
CComposeViewModel.prototype.autocompleteCallback = function (sTerm, fResponse)
{
    var
        oParameters = {
            'Action': 'ContactSuggestions',
            'Search': sTerm
        }
	;

	App.Ajax.send(oParameters, function (oResponse) {

		var aList = [];
		if (oResponse && oResponse.Result && oResponse.Result && oResponse.Result.List)
		{
			aList = _.map(oResponse.Result.List, function (oItem) {

				var
					sLabel = '',
					sValue = oItem.Email
				;

				if (oItem.IsGroup)
				{
					if (oItem.Name && 0 < Utils.trim(oItem.Name).length)
					{
						sLabel = '"' + oItem.Name + '" (' + oItem.Email + ')';
					}
					else
					{
						sLabel = '(' + oItem.Email + ')';
					}
				}
				else
				{
					sLabel = Utils.Address.getFullEmail(oItem.Name, oItem.Email);
					sValue = sLabel;
				}

				return {
					'label': sLabel,
					'value': sValue,
					'frequency': oItem.Frequency,
					'id': oItem.Id,
					'global': oItem.Global,
					'sharedToAll': oItem.SharedToAll
				};
			});

			aList = _.sortBy(_.compact(aList), function(oItem){
				return oItem.frequency;
			}).reverse();
		}

		fResponse(aList);

	}, this);

};

CComposeViewModel.prototype.onShowFilesPopupClick = function ()
{
    var fCallBack = _.bind(this.addFilesAsAttachment, this);
    /*global FileStoragePopup:true */
    App.Screens.showPopup(FileStoragePopup, [fCallBack]);
    /*global FileStoragePopup:false */
};

CComposeViewModel.prototype.confirmOpenPgp = function ()
{
    var
        sConfirm = Utils.i18n('OPENPGP/CONFIRM_HTML_TO_PLAIN_FORMATTING'),
        fOpenPgpEncryptPopup = _.bind(function (bRes) {
            if (bRes)
            {
                this.openPgpPopup(false);
            }
        }, this)
        ;

    if (this.notInlineAttachments().length > 0)
    {
        sConfirm += '\r\n\r\n' + Utils.i18n('OPENPGP/CONFIRM_HTML_TO_PLAIN_ATTACHMENTS');
    }

    App.Screens.showPopup(ConfirmPopup, [sConfirm, fOpenPgpEncryptPopup]);
};

/**
 * @param {boolean} bSignAndSend
 */
CComposeViewModel.prototype.openPgpPopup = function (bSignAndSend)
{
    var
        sText = this.oHtmlEditor.getPlainText(),
        fOkCallback = _.bind(function (oSignedEncryptedText, bEncrypted) {
            if (!bSignAndSend)
            {
                this.stopAutosaveTimer();
                this.executeSave(true);
            }
            this.plainText(true);
            this.textBody(oSignedEncryptedText);
            this.pgpSecured(true);
            this.pgpEncrypted(bEncrypted);
            if (bSignAndSend)
            {
                this.executeSend(true);
            }
        }, this),
        fCancelCallback = _.bind(function () {
            if (bSignAndSend)
            {
                this.executeSend(true);
            }
        }, this)
        ;

    /*global COpenPgpEncryptPopup:true */
    App.Screens.showPopup(COpenPgpEncryptPopup, [sText, AppData.Accounts.getEmail(this.senderAccountId()), this.recipientEmails(), bSignAndSend, fOkCallback, fCancelCallback]);
    /*global COpenPgpEncryptPopup:true */
};

CComposeViewModel.prototype.undoPgp = function ()
{
    var
        sText = this.textBody(),
        aText = []
        ;

    if (this.pgpSecured())
    {
        this.plainText(false);
        if (this.fromDrafts() && !this.pgpEncrypted())
        {
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

            this.textBody(sText);
        }
        else
        {
            this.oHtmlEditor.undoAndClearRedo();
        }

        this.fromToExpandColapssed(false);
        this.pgpSecured(false);
        this.pgpEncrypted(false);
    }
};

CComposeViewModel.prototype.autocompleteDeleteItem = function (oContact)
{
	var
		oParameters = {
			'Action': 'ContactSuggestionDelete',
			'ContactId': oContact.id,
			'SharedToAll': oContact.sharedToAll ? '1' : '0'
		}
		;

	App.Ajax.send(oParameters, function (oData) {
		return true;
	}, this);
};