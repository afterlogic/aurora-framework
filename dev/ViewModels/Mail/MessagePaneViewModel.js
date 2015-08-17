
/**
 * @constructor
 * 
 * @param {Function} fOpenMessageInNewWindowBinded
 */
function CMessagePaneViewModel(fOpenMessageInNewWindowBinded)
{
	this.openMessageInNewWindowBinded = fOpenMessageInNewWindowBinded;
	
	this.singleMode = ko.observable(AppData.SingleMode);
	this.isLoading = ko.observable(false);

	App.MailCache.folderList.subscribe(this.onFolderListSubscribe, this);
	this.messages = App.MailCache.messages;
	this.messages.subscribe(this.onMessagesSubscribe, this);
	this.currentMessage = App.MailCache.currentMessage;
	this.currentMessage.subscribe(this.onCurrentMessageSubscribe, this);
	AppData.User.defaultTimeFormat.subscribe(this.onCurrentMessageSubscribe, this);
	this.displayedMessageUid = ko.observable('');
	
	this.isCurrentMessage = ko.computed(function () {
		return !!this.currentMessage();
	}, this);
	
	this.isCurrentMessageLoaded = ko.computed(function () {
		return this.isCurrentMessage() && !this.isLoading();
	}, this);
	
	this.visibleNoMessageSelectedText = ko.computed(function () {
		return this.messages().length > 0 && !this.isCurrentMessage();
	}, this);
	
	this.prevMessageUid = App.MailCache.prevMessageUid;
	this.nextMessageUid = App.MailCache.nextMessageUid;

	this.isEnablePrevMessage = ko.computed(function () {
		return typeof this.prevMessageUid() === 'string' && this.prevMessageUid() !== '';
	}, this);
	this.isEnableNextMessage = ko.computed(function () {
		return typeof this.nextMessageUid() === 'string' && this.nextMessageUid() !== '';
	}, this);
	
	this.isEnableDelete = this.isCurrentMessage;
	this.isEnableReply = this.isCurrentMessageLoaded;
	this.isEnableReplyAll = this.isCurrentMessageLoaded;
	this.isEnableResend = this.isCurrentMessageLoaded;
	this.isEnableForward = this.isCurrentMessageLoaded;
	this.isEnablePrint = this.isCurrentMessageLoaded;
	this.isEnableSave = this.isCurrentMessage;
	
	this.allowSaveAsPdf =  ko.observable(!!AppData.AllowSaveAsPdf);
	
	this.isEnableSaveAsPdf = ko.computed(function () {
		return this.isCurrentMessageLoaded() && this.allowSaveAsPdf();
	}, this);

	this.deleteCommand = Utils.createCommand(this, this.executeDeleteMessage, this.isEnableDelete);
	this.prevMessageCommand = Utils.createCommand(this, this.executePrevMessage, this.isEnablePrevMessage);
	this.nextMessageCommand = Utils.createCommand(this, this.executeNextMessage, this.isEnableNextMessage);
	this.replyCommand = Utils.createCommand(this, this.executeReply, this.isEnableReply);
	this.replyAllCommand = Utils.createCommand(this, this.executeReplyAll, this.isEnableReplyAll);
	this.resendCommand = Utils.createCommand(this, this.executeResend, this.isEnableResend);
	this.forwardCommand = Utils.createCommand(this, this.executeForward, this.isEnableForward);
	this.printCommand = Utils.createCommand(this, this.executePrint, this.isEnablePrint);
	this.saveCommand = Utils.createCommand(this, this.executeSave, this.isEnableSave);
	this.saveAsPdfCommand = Utils.createCommand(this, this.executeSaveAsPdf, this.isEnableSaveAsPdf);
	this.moreCommand = Utils.createCommand(this, null, this.isCurrentMessageLoaded);

	this.ical = ko.observable(null);
	this.icalSubscription = this.ical.subscribe(function () {
		if (this.ical() !== null)
		{
			App.CalendarCache.firstRequestCalendarList();
			this.icalSubscription.dispose();
		}
	}, this);
	this.vcard = ko.observable(null);

	this.visiblePicturesControl = ko.observable(false);
	this.visibleShowPicturesLink = ko.observable(false);
	this.visibleAppointmentInfo = ko.computed(function () {
		return this.ical() !== null;
	}, this);
	this.visibleVcardInfo = ko.computed(function () {
		return this.vcard() !== null;
	}, this);
	
	this.sensitivityText = ko.computed(function () {
		var sText = '';
		
		if (this.currentMessage())
		{
			switch (this.currentMessage().sensitivity())
			{
				case Enums.Sensitivity.Confidential:
					sText = Utils.i18n('MESSAGE/SENSITIVITY_CONFIDENTIAL');
					break;
				case Enums.Sensitivity.Personal:
					sText = Utils.i18n('MESSAGE/SENSITIVITY_PERSONAL');
					break;
				case Enums.Sensitivity.Private:
					sText = Utils.i18n('MESSAGE/SENSITIVITY_PRIVATE');
					break;
			}
		}
		
		return sText;
	}, this);

	this.visibleConfirmationControl = ko.computed(function () {
		return (this.currentMessage() && this.currentMessage().readingConfirmation() !== '');
	}, this);
	
	this.isCurrentNotDraftOrSent = ko.computed(function () {
		var oCurrFolder = App.MailCache.folderList().currentFolder();
		return (oCurrFolder && oCurrFolder.fullName().length > 0 &&
			oCurrFolder.type() !== Enums.FolderTypes.Drafts &&
			oCurrFolder.type() !== Enums.FolderTypes.Sent);
	}, this);

	this.isCurrentSentFolder = ko.computed(function () {
		var oCurrFolder = App.MailCache.folderList().currentFolder();
		return oCurrFolder && oCurrFolder.fullName().length > 0 && oCurrFolder.type() === Enums.FolderTypes.Sent;
	}, this);

	this.isCurrentNotDraftFolder = ko.computed(function () {
		var oCurrFolder = App.MailCache.folderList().currentFolder();
		return (oCurrFolder && oCurrFolder.fullName().length > 0 &&
			oCurrFolder.type() !== Enums.FolderTypes.Drafts);
	}, this);

	this.isVisibleReplyTool = this.isCurrentNotDraftOrSent;
	this.isVisibleResendTool = this.isCurrentSentFolder;
	this.isVisibleForwardTool = this.isCurrentNotDraftFolder;

	this.uid = ko.observable('');
	this.folder = ko.observable('');
	this.folder.subscribe(function () {
		if (this.jqPanelHelper) {
			this.jqPanelHelper.trigger('resize', [null, 'min', null, true]);
		}
	}, this);
	this.subject = ko.observable('');
	this.emptySubject = ko.computed(function () {
		return (Utils.trim(this.subject()) === '');
	}, this);
	this.subjectForDisplay = ko.computed(function () {
		return this.emptySubject() ? Utils.i18n('MAILBOX/EMPTY_SUBJECT') : this.subject();
	}, this);
	this.importance = ko.observable(Enums.Importance.Normal);
	this.oFromAddr = ko.observable(null);
	this.from = ko.observable('');
	this.fromEmail = ko.observable('');
	this.fullFrom = ko.observable('');
	this.to = ko.observable('');
	this.aToAddr = ko.observableArray([]);
	this.cc = ko.observable('');
	this.aCcAddr = ko.observableArray([]);
	this.bcc = ko.observable('');
	this.aBccAddr = ko.observableArray([]);
	this.allRecipients = ko.observable('');
	this.aAllRecipients = ko.observableArray([]);
	this.recipientsContacts = ko.observableArray([]);
	this.currentAccountEmail = ko.observable();
	this.meSender = Utils.i18n('MESSAGE/ME_SENDER');
	this.meRecipient = Utils.i18n('MESSAGE/ME_RECIPIENT');
	
	this.fullDate = ko.observable('');
	this.midDate = ko.observable('');

	this.textBody = ko.observable('');
	this.textBodyForNewWindow = ko.observable('');
	this.domTextBody = ko.observable(null);
	this.rtlMessage = ko.observable(false);
	
	this.contentHasFocus = ko.observable(false);

	this.decryptPassword = ko.observable('');
	this.visibleDecryptControl = ko.observable(false);
	this.visibleVerifyControl = ko.observable(false);

	this.fakeHeader = ko.computed(function () {
		return !(this.visiblePicturesControl() || this.visibleConfirmationControl() || 
				this.sensitivityText() !== '' || this.visibleDecryptControl() || this.visibleVerifyControl());
	}, this);

	this.mobileApp = bMobileApp;
	
	this.attachments = ko.observableArray([]);
	this.usesAttachmentString = true;
	this.attachmentsInString = ko.computed(function () {
		return _.map(this.attachments(), function (oAttachment) {
			return oAttachment.fileName();
		}, this).join(', ');
	}, this);
	this.notInlineAttachments = ko.computed(function () {
		return _.filter(this.attachments(), function (oAttach) {
			return !oAttach.linked();
		});
	}, this);
	this.visibleDownloadAllAttachments = ko.computed(function () {
		return AppData.ZipAttachments && this.notInlineAttachments().length > 1;
	}, this);
	this.visibleSaveAttachmentsToFiles = AppData.User.IsFilesSupported;
	this.visibleDownloadAllAttachmentsSeparately = ko.computed(function () {
		return this.notInlineAttachments().length > 1;
	}, this);
	this.visibleExtendedDownload = ko.computed(function () {
		return !this.mobileApp && (this.visibleDownloadAllAttachments() || this.visibleDownloadAllAttachmentsSeparately() || this.visibleSaveAttachmentsToFiles);
	}, this);
	
	this.detailsVisible = ko.observable(App.Storage.getData('MessageDetailsVisible') === '1');
	this.detailsTooltip = ko.computed(function () {
		return this.detailsVisible() ? Utils.i18n('MESSAGE/ACTION_HIDE_DETAILS') : Utils.i18n('MESSAGE/ACTION_SHOW_DETAILS');
	}, this);

	this.hasNotInlineAttachments = ko.computed(function () {
		return this.notInlineAttachments().length > 0;
	}, this);
	
	this.hasBodyText = ko.computed(function () {
		return this.textBody().length > 0;
	}, this);

	this.visibleAddMenu = ko.observable(false);
	
	// Quick Reply Part
	
	this.replyText = ko.observable('');
	this.replyTextFocus = ko.observable(false);
	this.replyPaneVisible = ko.computed(function () {
		return this.currentMessage() && this.currentMessage().completelyFilled();
	}, this);
	this.replySendingStarted = ko.observable(false);
	this.replySavingStarted = ko.observable(false);
	this.replyAutoSavingStarted = ko.observable(false);
	this.requiresPostponedSending = ko.observable(false);
	this.replyAutoSavingStarted.subscribe(function () {
		if (!this.replyAutoSavingStarted() && this.requiresPostponedSending())
		{
			App.MessageSender.sendPostponedMail(this.replyDraftUid());
			this.requiresPostponedSending(false);
		}
	}, this);
	
	ko.computed(function () {
		if (!this.replyTextFocus() || this.replyAutoSavingStarted() || this.replySavingStarted() || this.replySendingStarted())
		{
			this.stopAutosaveTimer();
		}
		if (this.replyTextFocus() && !this.replyAutoSavingStarted() && !this.replySavingStarted() && !this.replySendingStarted())
		{
			this.startAutosaveTimer();
		}
	}, this);
	
	this.saveButtonText = ko.computed(function () {
		return this.replyAutoSavingStarted() ? Utils.i18n('COMPOSE/TOOL_SAVING') : Utils.i18n('COMPOSE/TOOL_SAVE');
	}, this);
	this.replyDraftUid = ko.observable('');
	this.replyLoadingText = ko.computed(function () {
		if (this.replySendingStarted())
		{
			return Utils.i18n('COMPOSE/INFO_SENDING');
		}
		else if (this.replySavingStarted())
		{
			return Utils.i18n('COMPOSE/INFO_SAVING');
		}
		return '';
	}, this);
	
	this.isEnableSendQuickReply = ko.computed(function () {
		return this.isCurrentMessageLoaded() && this.replyText() !== '' && !this.replySendingStarted();
	}, this);
	this.isEnableSaveQuickReply = ko.computed(function () {
		return this.isEnableSendQuickReply() && !this.replySavingStarted() && !this.replyAutoSavingStarted();
	}, this);
	
	this.saveQuickReplyCommand = Utils.createCommand(this, this.executeSaveQuickReply, this.isEnableSaveQuickReply);
	this.sendQuickReplyCommand = Utils.createCommand(this, this.executeSendQuickReplyCommand, this.isEnableSendQuickReply);

	this.domMessageHeader = ko.observable(null);
	this.domQuickReply = ko.observable(null);
	
	this.domMessageForPrint = ko.observable(null);
	
	// to have time to take action "Open full reply form" before the animation starts
	this.replyTextFocusThrottled = ko.observable(false).extend({'throttle': 50});
	
	this.replyTextFocus.subscribe(function () {
		this.replyTextFocusThrottled(this.replyTextFocus());
	}, this);
	
	this.isQuickReplyActive = ko.computed(function () {
		return this.replyText().length > 0 || this.replyTextFocusThrottled();
	}, this);

	//*** Quick Reply Part

	this.jqPanelHelper = null;
	
	this.visibleAttachments = ko.observable(false);
	this.showMessage = function () {
		this.visibleAttachments(false);
	};
	this.showAttachments = function () {
		this.visibleAttachments(true);
	};
	
	this.defaultFontName = AppData.User.DefaultFontName;
	
	if (App.nowDateNumber)
	{
		App.nowDateNumber.subscribe(function () {
			this.updateMomentDate();
		}, this);
	}
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CMessagePaneViewModel.prototype.__name = 'CMessagePaneViewModel';

CMessagePaneViewModel.prototype.resizeDblClick = function (oData, oEvent)
{
	if (oEvent.target.className !== '' && !!oEvent.target.className.search(/add_contact|icon|link|title|subject|link|date|from/))
	{
		Utils.calmEvent(oEvent);
		Utils.removeSelection();
		if (!this.jqPanelHelper)
		{
			this.jqPanelHelper = $('.MailLayout .panel_helper');
		}
		this.jqPanelHelper.trigger('resize', [5, 'min', true]);
	}
};

CMessagePaneViewModel.prototype.notifySender = function ()
{
	if (this.currentMessage() && this.currentMessage().readingConfirmation() !== '')
	{
		App.Ajax.send({
			'Action': 'MessageSendConfirmation',
			'Confirmation': this.currentMessage().readingConfirmation(),
			'Subject': Utils.i18n('MESSAGE/RETURN_RECEIPT_MAIL_SUBJECT'),
			'Text': Utils.i18n('MESSAGE/RETURN_RECEIPT_MAIL_TEXT', {
				'EMAIL': AppData.Accounts.getEmail(),
				'SUBJECT': this.subject()
			}),
			'ConfirmFolder': this.currentMessage().folder(),
			'ConfirmUid': this.currentMessage().uid()
		});
		this.currentMessage().readingConfirmation('');
	}
};

CMessagePaneViewModel.prototype.onFolderListSubscribe = function ()
{
	if (AppData.SingleMode)
	{
		this.onMessagesSubscribe();
	}
};

CMessagePaneViewModel.prototype.onMessagesSubscribe = function ()
{
	if (!this.currentMessage() && this.uid().length > 0)
	{
		App.MailCache.setCurrentMessage(this.uid(), this.folder());
	}
};

CMessagePaneViewModel.prototype.onCurrentMessageSubscribe = function ()
{
	var
		oIcal = null,
		oVcard = null,
		oMessage = this.currentMessage(),
		oAccount = oMessage ? AppData.Accounts.getAccount(oMessage.accountId()) : null
	;
	
	if (this.singleMode() && window.opener && window.opener.oReplyDataFromViewPane)
	{
		this.replyText(window.opener.oReplyDataFromViewPane.ReplyText);
		this.replyDraftUid(window.opener.oReplyDataFromViewPane.ReplyDraftUid);
		window.opener.oReplyDataFromViewPane = null;
	}
	else if (!oMessage || oMessage.uid() !== this.displayedMessageUid())
	{
		this.replyText('');
		this.replyDraftUid('');
	}
	
	if (oMessage && this.uid() === oMessage.uid())
	{
		this.subject(oMessage.subject());
		this.importance(oMessage.importance());
		this.from(oMessage.oFrom.getDisplay());
		this.fromEmail(oMessage.oFrom.getFirstEmail());

		this.fullFrom(oMessage.oFrom.getFull());
		if (oMessage.oFrom.aCollection.length > 0)
		{
			this.oFromAddr(oMessage.oFrom.aCollection[0]);
		}
		else
		{
			this.oFromAddr(null);
		}
		
		this.to(oMessage.oTo.getFull());
		this.aToAddr(oMessage.oTo.aCollection);
		this.cc(oMessage.oCc.getFull());
		this.aCcAddr(oMessage.oCc.aCollection);
		this.bcc(oMessage.oBcc.getFull());
		this.aBccAddr(oMessage.oBcc.aCollection);

		this.currentAccountEmail(oAccount.email());
		this.aAllRecipients(_.uniq(_.union(this.aToAddr(), this.aCcAddr(), this.aBccAddr())));
		if (!this.mobileApp)
		{
			this.requestContactsFromCache(_.union(oMessage.oFrom.aCollection, this.aAllRecipients()));
		}

		this.midDate(oMessage.oDateModel.getMidDate());
		this.fullDate(oMessage.oDateModel.getFullDate());

		this.isLoading(oMessage.uid() !== '' && !oMessage.completelyFilled());
		
		this.setMessageBody();
		this.rtlMessage(oMessage.rtl());

		if (this.singleMode())
		{
			/*jshint onevar: false*/
			var
				aAtachments = [],
				sThumbSessionUid = Date.now().toString()
			;
			/*jshint onevar: true*/

			_.each(oMessage.attachments(), _.bind(function (oAttach) {
				var oCopy = new CMailAttachmentModel();
				oCopy.copyProperties(oAttach);
				oCopy.getInThumbQueue(sThumbSessionUid);
				aAtachments.push(oCopy);
			}, this));
			
			this.attachments(aAtachments);
		}
		else
		{
			this.attachments(oMessage.attachments());
		}

		// animation of buttons turns on with delay
		// so it does not trigger when placing initial values
		if (this.ical() !== null)
		{
			this.ical().animation(false);
		}
		oIcal = oMessage.ical();
		if (oIcal && this.singleMode())
		{
			oIcal = this.getIcalCopy(oIcal);
		}
		this.ical(oIcal);
		if (this.ical() !== null)
		{
			_.defer(_.bind(function () {
				if (this.ical() !== null)
				{
					this.ical().animation(true);
				}
			}, this));
			this.ical().updateAttendeeStatus(this.fromEmail());
		}
		oVcard = oMessage.vcard();
		if (oVcard && this.singleMode())
		{
			oVcard = this.getVcardCopy(oVcard);
		}
		this.vcard(oVcard);
		
		if (!oMessage.completelyFilled() || oMessage.trimmed())
		{
			/*jshint onevar: false*/
			var oSubscribedField = !oMessage.completelyFilled() ? oMessage.completelyFilled : oMessage.trimmed;
			/*jshint onevar: true*/
			if (this.singleMode())
			{
				oMessage.completelyFilledSingleModeSubscription = oSubscribedField.subscribe(this.onCurrentMessageSubscribe, this);
			}
			else
			{
				oMessage.completelyFilledSubscription = oSubscribedField.subscribe(this.onCurrentMessageSubscribe, this);
			}
		}
		else if (oMessage.completelyFilledSubscription)
		{
			oMessage.completelyFilledSubscription.dispose();
			oMessage.completelyFilledSubscription = undefined;
		}
		else if (oMessage.completelyFilledSingleModeSubscription)
		{
			oMessage.completelyFilledSingleModeSubscription.dispose();
			oMessage.completelyFilledSingleModeSubscription = undefined;
		}
	}
	else
	{
		this.isLoading(false);
		$(this.domTextBody()).empty().data('displayed-message-uid', '');
		this.displayedMessageUid('');
		this.rtlMessage(false);
		
		// cannot use removeAll, because the attachments of messages are passed by reference 
		// and the call to removeAll removes attachments from message in the cache too.
		this.attachments([]);
		this.visiblePicturesControl(false);
		this.visibleShowPicturesLink(false);
		this.ical(null);
		this.vcard(null);
		this.decryptPassword('');
		this.visibleDecryptControl(false);
		this.visibleVerifyControl(false);
	}
};

CMessagePaneViewModel.prototype.updateMomentDate = function ()
{
	var oMessage = this.currentMessage();
	if (oMessage && oMessage.oDateModel)
	{
		this.midDate(oMessage.oDateModel.getMidDate());
		this.fullDate(oMessage.oDateModel.getFullDate());
	}
};

/**
 * Requests the contact information from the cache.
 * 
 * @param {Array} aRecipients List of CAddressModel objects.
 */
CMessagePaneViewModel.prototype.requestContactsFromCache = function (aRecipients)
{
	var aEmails = _.map(aRecipients, function (oAddress) {
		return oAddress.sEmail;
	});
	
	this.recipientsContacts([]);
	App.ContactsCache.getContactsByEmails(aEmails, this.onContactResponse, this);
};

/**
 * Gets the contact information from the cache and uses it in hint-popups.
 * 
 * @param {Object} oContact CContactModel object.
 * @param {string} sEmail Email at which the contact was found.
 */
CMessagePaneViewModel.prototype.onContactResponse = function (oContact, sEmail)
{
	if (oContact)
	{
		this.recipientsContacts.push(oContact);
		this.recipientsContacts(_.uniq(this.recipientsContacts()));
	}
	_.each(_.union([this.oFromAddr()], this.aAllRecipients()), function (oAddress) {
		if (oAddress && oAddress.sEmail === sEmail)
		{
			oAddress.loaded(true);
			oAddress.found(!!oContact);
		}
	});
};

CMessagePaneViewModel.prototype.getVcardCopy = function (oVcard)
{
	var oNewVcard = new CVcardModel();
	oNewVcard.uid(oVcard.uid());
	oNewVcard.file(oVcard.file());
	oNewVcard.name(oVcard.name());
	oNewVcard.email(oVcard.email());
	oNewVcard.exists(oVcard.exists());
	oNewVcard.isJustSaved(oVcard.isJustSaved());
	return oNewVcard;
};

CMessagePaneViewModel.prototype.getIcalCopy = function (oIcal)
{
	var oNewIcal = new CIcalModel();
	oNewIcal.uid(oIcal.uid());
	oNewIcal.file(oIcal.file());
	oNewIcal.attendee(oIcal.attendee());
	oNewIcal.type(oIcal.type());
	oNewIcal.cancelDecision(oIcal.cancelDecision());
	oNewIcal.replyDecision(oIcal.replyDecision());
	oNewIcal.isJustSaved(oIcal.isJustSaved());
	oNewIcal.location(oIcal.location());
	oNewIcal.description(oIcal.description());
	oNewIcal.when(oIcal.when());
	oNewIcal.calendarId(oIcal.calendarId());
	oNewIcal.selectedCalendarId(oIcal.selectedCalendarId());
	oNewIcal.calendars(oIcal.calendars());
	oNewIcal.animation(oIcal.animation());
	return oNewIcal;
};

CMessagePaneViewModel.prototype.setMessageBody = function ()
{
	if (this.currentMessage())
	{
		var
			oMessage = this.currentMessage(),
			sText = oMessage.text(),
			$body = $(this.domTextBody())
		;
		
		$body.empty();
		
		this.textBody(sText);
		
		_.defer(_.bind(function () {
			if (this.currentMessage())
			{
				var
					oMessage = this.currentMessage(),
					sText = oMessage.text(),
					oDom = null,
					sHtml = '',
					sLen = sText.length,
					sMaxLen = 5000000,
					aCollapsedStatuses = []
				;
				
				if ($body.data('displayed-message-uid') === oMessage.uid())
				{
					aCollapsedStatuses = this.getBlockquotesStatus();
				}

				$body.empty();
				
				if (oMessage.isPlain() || sLen > sMaxLen)
				{
					$body.html(sText);
					
					this.visiblePicturesControl(false);
				}
				else
				{
					oDom = oMessage.getDomText();
					sHtml = oDom.length > 0 ? oDom.html() : '';
					
					$body.append(sHtml);

					this.visiblePicturesControl(oMessage.hasExternals() && !oMessage.isExternalsAlwaysShown());
					this.visibleShowPicturesLink(!oMessage.isExternalsShown());

					if (!Utils.htmlStartsWithBlockquote(sHtml))
					{
						this.doHidingBlockquotes(aCollapsedStatuses);
					}
				}
				
				this.decryptPassword('');
				this.visibleDecryptControl(AppData.User.enableOpenPgp() && oMessage.encryptedMessage());
				this.visibleVerifyControl(AppData.User.enableOpenPgp() && oMessage.signedMessage());
				$body.data('displayed-message-uid', oMessage.uid());
				this.displayedMessageUid(oMessage.uid());
			}
		}, this));
	}
};

CMessagePaneViewModel.prototype.getBlockquotesStatus = function ()
{
	var aCollapsedStatuses = [];
	
	$($('blockquote', $(this.domTextBody())).get()).each(function () {
		var
			$blockquote = $(this)
		;
		
		if ($blockquote.hasClass('blockquote_before_toggle'))
		{
			aCollapsedStatuses.push($blockquote.hasClass('collapsed'));
		}
	});
	
	return aCollapsedStatuses;
};

CMessagePaneViewModel.prototype.doHidingBlockquotes = function (aCollapsedStatuses)
{
	var
		iMinHeightForHide = 120,
		iHiddenHeight = 80,
		iStatusIndex = 0
	;
	
	$($('blockquote', $(this.domTextBody())).get()).each(function () {
		var
			$blockquote = $(this),
			$parentBlockquotes = $blockquote.parents('blockquote'),
			$switchButton = $('<span class="blockquote_toggle"></span>').html(Utils.i18n('MESSAGE/SHOW_QUOTED_TEXT')),
			bHidden = true
		;
		if ($parentBlockquotes.length === 0)
		{
			if ($blockquote.height() > iMinHeightForHide)
			{
				$blockquote
					.addClass('blockquote_before_toggle')
					.after($switchButton)
					.wrapInner('<div class="blockquote_content"></div>')
				;
				$switchButton.bind('click', function () {
					if (bHidden)
					{
						$blockquote.height('auto');
						$switchButton.html(Utils.i18n('MESSAGE/HIDE_QUOTED_TEXT'));
						bHidden = false;
					}
					else
					{
						$blockquote.height(iHiddenHeight);
						$switchButton.html(Utils.i18n('MESSAGE/SHOW_QUOTED_TEXT'));
						bHidden = true;
					}
					
					$blockquote.toggleClass('collapsed', bHidden);
				});
				if (iStatusIndex < aCollapsedStatuses.length)
				{
					bHidden = aCollapsedStatuses[iStatusIndex];
					iStatusIndex++;
				}
				$blockquote.height(bHidden ? iHiddenHeight : 'auto').toggleClass('collapsed', bHidden);
			}
		}
	});
};

/**
 * @param {Array} aParams
 */
CMessagePaneViewModel.prototype.onRoute = function (aParams)
{
	var oParams = App.Links.parseMailbox(aParams);

	if (this.replyText() !== '' && this.uid() !== oParams.Uid)
	{
		this.saveReplyMessage(false);
	}

	this.uid(oParams.Uid);
	this.folder(oParams.Folder);
	App.MailCache.setCurrentMessage(this.uid(), this.folder());
	
	this.contentHasFocus(true);
};

CMessagePaneViewModel.prototype.showPictures = function ()
{
	App.MailCache.showExternalPictures(false);
	this.visibleShowPicturesLink(false);
	this.setMessageBody();
};

CMessagePaneViewModel.prototype.alwaysShowPictures = function ()
{
	var
		sEmail = this.currentMessage() ? this.currentMessage().oFrom.getFirstEmail() : ''
	;

	if (sEmail.length > 0)
	{
		App.Ajax.send({
			'Action': 'EmailSetSafety',
			'Email': sEmail
		});
	}

	App.MailCache.showExternalPictures(true);
	this.visiblePicturesControl(false);
	this.setMessageBody();
};

CMessagePaneViewModel.prototype.openInNewWindow = function ()
{
	this.openMessageInNewWindowBinded(this.currentMessage());
};

CMessagePaneViewModel.prototype.addToContacts = function (sEmail, sName)
{
	App.Api.contactCreate(sName, sEmail, this.onContactResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMessagePaneViewModel.prototype.onAddToContactsResponse = function (oResponse, oRequest)
{
	if (oResponse.Result && oRequest.HomeEmail !== '')
	{
		App.Api.showReport(Utils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED'));
		App.ContactsCache.clearInfoAboutEmail(oRequest.HomeEmail);
		App.ContactsCache.getContactsByEmails([oRequest.HomeEmail], this.onContactResponse, this);
	}
};

CMessagePaneViewModel.prototype.getReplyHtmlText = function ()
{
	return '<div style="font-family: ' + this.defaultFontName + '; font-size: 16px">' + App.MessageSender.getHtmlFromText(this.replyText()) + '</div>';
};

/**
 * @param {string} sReplyType
 */
CMessagePaneViewModel.prototype.executeReplyOrForward = function (sReplyType)
{
	if (this.currentMessage())
	{
		App.MessageSender.setReplyData(this.getReplyHtmlText(), this.replyDraftUid());
		
		this.replyText('');
		this.replyDraftUid('');
		
		App.Api.composeMessageAsReplyOrForward(sReplyType, this.currentMessage().folder(), this.currentMessage().uid());
	}
};

CMessagePaneViewModel.prototype.executeDeleteMessage = function ()
{
	if (this.currentMessage())
	{
		if (this.singleMode() && window.opener && window.opener.App && window.opener.App.MailCache)
		{
			App.Api.deleteMessages([this.currentMessage().uid()], window.opener.App, function () {window.close();});
		}
		else if (this.mobileApp)
		{
			App.Api.deleteMessages([this.currentMessage().uid()], App);
		}
	}
};

CMessagePaneViewModel.prototype.executePrevMessage = function ()
{
	if (this.isEnablePrevMessage())
	{
		this.moveToSingleMessageView(this.prevMessageUid());
	}
};

CMessagePaneViewModel.prototype.executeNextMessage = function ()
{
	if (this.isEnableNextMessage())
	{
		this.moveToSingleMessageView(this.nextMessageUid());
	}
};

/**
 * @param {string} sUid
 */
CMessagePaneViewModel.prototype.moveToSingleMessageView = function (sUid)
{
	var
		sFolder = App.MailCache.folderList().currentFolderFullName(),
		aHash = [Enums.Screens.SingleMessageView, sFolder, 'msg' + sUid]
	;

	App.Routing.setHash(aHash);
};

CMessagePaneViewModel.prototype.executeReply = function ()
{
	this.executeReplyOrForward(Enums.ReplyType.Reply);
};

CMessagePaneViewModel.prototype.executeReplyAll = function ()
{
	this.executeReplyOrForward(Enums.ReplyType.ReplyAll);
};

CMessagePaneViewModel.prototype.executeResend = function ()
{
	this.executeReplyOrForward(Enums.ReplyType.Resend);
};

CMessagePaneViewModel.prototype.executeForward = function ()
{
	this.executeReplyOrForward(Enums.ReplyType.Forward);
};

CMessagePaneViewModel.prototype.executePrint = function ()
{
	var
		oMessage = this.currentMessage(),
		oWin = oMessage ? Utils.WindowOpener.open('', this.subject() + '-print') : null,
		sHtml = ''
	;

	if (oMessage && oWin)
	{
		this.textBodyForNewWindow(oMessage.getConvertedHtml(Utils.Common.getAppPath(), true));
		sHtml = $(this.domMessageForPrint()).html();

		$(oWin.document.body).html(sHtml);
		oWin.print();
	}
};

CMessagePaneViewModel.prototype.executeSave = function ()
{
	if (this.currentMessage())
	{
		App.Api.downloadByUrl(this.currentMessage().downloadLink());
	}
};

CMessagePaneViewModel.prototype.executeSaveAsPdf = function ()
{
	if (this.currentMessage())
	{
		var
			oBody = this.currentMessage().getDomText(),
			iAccountId = this.currentMessage().accountId(),
			fReplaceWithBase64 = function (oImg) {

				try
				{
					var
						oCanvas = document.createElement('canvas'),
						oCtx = null
					;

					oCanvas.width = oImg.width;
					oCanvas.height = oImg.height;

					oCtx = oCanvas.getContext('2d');
					oCtx.drawImage(oImg, 0, 0);

					oImg.src = oCanvas.toDataURL('image/png');
				}
				catch (e) {}
			}
		;

		$('img[data-x-src-cid]', oBody).each(function () {
			fReplaceWithBase64(this);
		});

		App.Ajax.send({
			'Action': 'MessageGetPdfFromHtml',
			'Subject': this.subject(),
			'Html': oBody.html()
		}, function (oData) {
			if (oData && oData.Result && oData.Result['Hash'])
			{
				App.Api.downloadByUrl(Utils.getDownloadLinkByHash(iAccountId, oData.Result['Hash']));
			}
			else
			{
				App.Api.showError(Utils.i18n('WARNING/CREATING_PDF_ERROR'));
			}
		}, this);
	}
};

CMessagePaneViewModel.prototype.changeAddMenuVisibility = function ()
{
	var bVisibility = !this.visibleAddMenu();
	this.visibleAddMenu(bVisibility);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMessagePaneViewModel.prototype.onMessageSendOrSaveResponse = function (oResponse, oRequest)
{
	var oResData = App.MessageSender.onMessageSendOrSaveResponse(oResponse, oRequest, this.requiresPostponedSending());
	switch (oResData.Action)
	{
		case 'MessageSend':
			this.replySendingStarted(false);
			if (oResData.Result)
			{
				this.replyText('');
			}
			break;
		case 'MessageSave':
			if (oResData.Result)
			{
				this.replyDraftUid(oResData.NewUid);
			}
			this.replySavingStarted(false);
			this.replyAutoSavingStarted(false);
			break;
	}
};

CMessagePaneViewModel.prototype.executeSendQuickReplyCommand = function ()
{
	if (this.isEnableSendQuickReply())
	{
		this.replySendingStarted(true);
		this.requiresPostponedSending(this.replyAutoSavingStarted());
		App.MessageSender.sendReplyMessage('MessageSend', this.getReplyHtmlText(), this.replyDraftUid(), 
			this.onMessageSendOrSaveResponse, this, this.requiresPostponedSending());

		this.replyTextFocus(false);
	}
};

CMessagePaneViewModel.prototype.executeSaveQuickReply = function ()
{
	this.saveReplyMessage(false);
};

CMessagePaneViewModel.prototype.saveReplyMessage = function (bAutosave)
{
	if (this.isEnableSaveQuickReply())
	{
		if (bAutosave)
		{
			this.replyAutoSavingStarted(true);
		}
		else
		{
			this.replySavingStarted(true);
		}
		App.MessageSender.sendReplyMessage('MessageSave', this.getReplyHtmlText(), this.replyDraftUid(), 
			this.onMessageSendOrSaveResponse, this);
	}
};

/**
 * Stops autosave.
 */
CMessagePaneViewModel.prototype.stopAutosaveTimer = function ()
{
	window.clearTimeout(this.autoSaveTimer);
};

/**
 * Starts autosave.
 */
CMessagePaneViewModel.prototype.startAutosaveTimer = function ()
{
	if (this.isEnableSaveQuickReply())
	{
		var fSave = _.bind(this.saveReplyMessage, this, true);
		this.stopAutosaveTimer();
		if (AppData.User.AllowAutosaveInDrafts)
		{
			this.autoSaveTimer = window.setTimeout(fSave, AppData.App.AutoSaveIntervalSeconds * 1000);
		}
	}
};

CMessagePaneViewModel.prototype.downloadAllAttachments = function ()
{
	if (this.currentMessage())
	{
		this.currentMessage().downloadAllAttachments();
	}
};

CMessagePaneViewModel.prototype.saveAttachmentsToFiles = function ()
{
	if (this.currentMessage())
	{
		this.currentMessage().saveAttachmentsToFiles();
	}
};

CMessagePaneViewModel.prototype.downloadAllAttachmentsSeparately = function ()
{
	if (this.currentMessage())
	{
		this.currentMessage().downloadAllAttachmentsSeparately();
	}
};

CMessagePaneViewModel.prototype.onApplyBindings = function (oMailViewModel)
{
	App.registerSessionTimeoutFunction(_.bind(function () {
		if (this.replyText() !== '')
		{
			this.saveReplyMessage(false);
		}
	}, this));
	
	$(oMailViewModel).on('mousedown', 'a', function (oEvent) {
		if (oEvent && 3 !== oEvent['which'])
		{
			var sHref = $(this).attr('href');
			if (sHref && 'mailto:' === sHref.toString().toLowerCase().substr(0, 7))
			{
				App.Api.composeMessageToAddresses(sHref.toString().substr(7));
				return false;
			}
		}

		return true;
	});

	this.hotKeysBind();
};

CMessagePaneViewModel.prototype.hotKeysBind = function ()
{
	$(document).on('keydown', $.proxy(function(ev) {

		var	bComputed = App.Screens.currentScreen() === Enums.Screens.Mailbox && ev && !ev.ctrlKey && !ev.shiftKey &&
			!Utils.isTextFieldFocused() && this.isEnableReply();

		if (bComputed && ev.keyCode === Enums.Key.q)
		{
			ev.preventDefault();
			this.replyTextFocus(true);
		}
		else if (bComputed && ev.keyCode === Enums.Key.r)
		{
			this.executeReply();
		}
	}, this));
};

CMessagePaneViewModel.prototype.showSourceHeaders = function ()
{
	var
		oMessage = this.currentMessage(),
		oWin = oMessage && oMessage.completelyFilled() ? Utils.WindowOpener.open('', this.subject() + '-headers') : null
	;

	if (oWin)
	{
		$(oWin.document.body).html('<pre>' + Utils.encodeHtml(oMessage.sourceHeaders()) + '</pre>');
	}
};

CMessagePaneViewModel.prototype.onDecryptMessageClick = function ()
{
	if (this.currentMessage() && this.currentMessage().encryptedMessage())
	{
		this.decryptVerifyMessage(false);
	}
};

CMessagePaneViewModel.prototype.onVerifyMessageClick = function ()
{
	if (this.currentMessage() && this.currentMessage().signedMessage())
	{
		this.decryptVerifyMessage(true);
	}
};

/**
 * @param {boolean} bVerifyOnly
 */
CMessagePaneViewModel.prototype.decryptVerifyMessage = function (bVerifyOnly)
{
	var fPgpCallback = _.bind(function (oPgp) {
		var oMessage = this.currentMessage();
		if (oPgp && oMessage)
		{
			if (bVerifyOnly && oMessage.signedMessage())
			{
				this.verifyMessage(oPgp);
			}
			else if (oMessage.encryptedMessage())
			{
				this.decryptMessage(oPgp);
			}
		}
	}, this);
	
	App.Api.pgp(fPgpCallback, AppData.User.IdUser);
};

/**
 * @param {Object} oPgp
 */
CMessagePaneViewModel.prototype.decryptMessage = function (oPgp)
{
	var
		oMessage = this.currentMessage(),
		sData = oMessage.textRaw(),
		sAccountEmail = AppData.Accounts.getEmail(),
		sFromEmail = oMessage.oFrom.getFirstEmail(),
		sPrivateKeyPassword = this.decryptPassword(),
		oRes = oPgp.decryptAndVerify(sData, sAccountEmail, sFromEmail, sPrivateKeyPassword),
		bNoSignDataNotice = false
	;
	
	if (oRes && oRes.result && !oRes.errors)
	{
		oMessage.text('<pre>' + Utils.encodeHtml(oRes.result) + '</pre>');
		oMessage.$text = null;
		oMessage.encryptedMessage(false);
		this.decryptPassword('');
		this.visibleDecryptControl(false);
		this.setMessageBody();
		if (!oRes.notices)
		{
			App.Api.showReport(Utils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_DECRYPTED_AND_VERIFIED'));
		}
		else
		{
			App.Api.showReport(Utils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_DECRYPTED'));
		}
	}
	
	if (oRes && (oRes.errors || oRes.notices))
	{
		bNoSignDataNotice = App.Api.showPgpErrorByCode(oRes, Enums.PgpAction.DecryptVerify);
		if (bNoSignDataNotice)
		{
			App.Api.showReport(Utils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_DECRYPTED_AND_NOT_SIGNED'));
		}
	}
};

/**
 * @param {Object} oPgp
 */
CMessagePaneViewModel.prototype.verifyMessage = function (oPgp)
{
	var
		oMessage = this.currentMessage(),
		sData = oMessage.textRaw(),
		sFromEmail = oMessage.oFrom.getFirstEmail(),
		oRes = oPgp.verify(sData, sFromEmail)
	;
	
	if (oRes && oRes.result && !(oRes.errors || oRes.notices))
	{
		oMessage.text('<pre>' + Utils.encodeHtml(oRes.result) + '</pre>');
		oMessage.$text = null;
		oMessage.signedMessage(false);
		this.visibleVerifyControl(false);
		this.setMessageBody();
		App.Api.showReport(Utils.i18n('OPENPGP/REPORT_MESSAGE_SUCCESSFULLY_VERIFIED'));
	}
	
	if (oRes && (oRes.errors || oRes.notices))
	{
		App.Api.showPgpErrorByCode(oRes, Enums.PgpAction.Verify);
	}
};

CMessagePaneViewModel.prototype.switchDetailsVisibility = function ()
{
	this.detailsVisible(!this.detailsVisible());
	App.Storage.setData('MessageDetailsVisible', this.detailsVisible() ? '1' : '0');
};
