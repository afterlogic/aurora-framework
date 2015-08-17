
/**
 * @constructor
 */
function CApi()
{
	this.openPgp = null;
	this.openPgpCallbacks = [];
}

CApi.prototype.composeMessage = function ()
{
	App.Routing.setHash([Enums.Screens.Compose]);
};

/**
 * @param {string} sFolder
 * @param {string} sUid
 */
CApi.prototype.composeMessageFromDrafts = function (sFolder, sUid)
{
	var aParams = App.Links.composeFromMessage('drafts', sFolder, sUid);
	App.Routing.setHash(aParams);
};

/**
 * @param {string} sReplyType
 * @param {string} sFolder
 * @param {string} sUid
 */
CApi.prototype.composeMessageAsReplyOrForward = function (sReplyType, sFolder, sUid)
{
	var aParams = App.Links.composeFromMessage(sReplyType, sFolder, sUid);
	App.Routing.setHash(aParams);
};

/**
 * @param {string} sToAddresses
 */
CApi.prototype.composeMessageToAddresses = function (sToAddresses)
{
	var aParams = App.Links.composeWithToField(sToAddresses);
	App.Routing.setHash(aParams);
};

/**
 * @param {Object} oVcard
 */
CApi.prototype.composeMessageWithVcard = function (oVcard)
{
	var aParams = ['vcard', oVcard];
	App.Routing.goDirectly(App.Links.compose(), aParams);
};

/**
 * @param {string} sArmor
 * @param {string} sDownloadLinkFilename
 */
CApi.prototype.composeMessageWithPgpKey = function (sArmor, sDownloadLinkFilename)
{
	var aParams = ['data-as-file', sArmor, sDownloadLinkFilename];
	App.Routing.goDirectly(App.Links.compose(), aParams);
};

/**
 * @param {Array} aFileItems
 */
CApi.prototype.composeMessageWithFiles = function (aFileItems)
{
	var aParams = ['file', aFileItems];
	App.Routing.goDirectly(App.Links.compose(), aParams);
};

CApi.prototype.closeComposePopup = function ()
{
	//function is overrided in mail module
};

/**
 * @param {string} sEmail
 */
CApi.prototype.createMailAccount = function (sEmail)
{
	//function is overrided in mail module
};

CApi.prototype.showChangeDefaultAccountPasswordPopup = function ()
{
	//function is overrided in mail module
};

/**
 * @param {Function=} fAfterConfigureMail
 */
CApi.prototype.showConfigureMailPopup = function (fAfterConfigureMail)
{
	//function is overrided in mail module
};

/**
 * Downloads by url through iframe or new window.
 *
 * @param {string} sUrl
 */
CApi.prototype.downloadByUrl = function (sUrl)
{
	var oIframe = null;
	
	if (bMobileDevice)
	{
		window.open(sUrl);
	}
	else
	{
		oIframe = $('<iframe style="display: none;"></iframe>').appendTo(document.body);
		
		oIframe.attr('src', sUrl);
		
		setTimeout(function () {
			oIframe.remove();
		}, 60000);
	}
};

/**
 * @return {boolean}
 */
CApi.prototype.isPgpSupported = function ()
{
	return !!(window.crypto && window.crypto.getRandomValues);
};

/**
 * @param {Function} fCallback
 * @param {mixed=} sUserUid
 */
CApi.prototype.pgp = function (fCallback, sUserUid)
{
	if (Utils.isFunc(fCallback))
	{
		if (this.openPgp)
		{
			fCallback(this.openPgp);
		}
		else if (this.isPgpSupported())
		{
			if (null !== this.openPgpCallbacks)
			{
				this.openPgpCallbacks.push(fCallback);
			}
			else
			{
				fCallback(false);
			}
			
			var self = this;
			if (!this.openPgpRequest)
			{
				this.openPgpRequest = true;
				
				$.ajax({
					'url': 'static/js/openpgp.js',
					'dataType': 'script',
					'cache': true,
					'complete': function () {
						
						self.openPgp = window.openpgp ? new OpenPgp(window.openpgp, 'user_' + (sUserUid || '0') + '_') : false;

						if (null !== self.openPgpCallbacks)
						{
							_.each(self.openPgpCallbacks, function (fItemCallback) {
								fItemCallback(self.openPgp);
							});
						}

						self.openPgpCallbacks = null;
					}
				});
			}
		}
		else
		{
			fCallback(false);
		}
	}
};

/**
 * @param {string} sLoading
 */
CApi.prototype.showLoading = function (sLoading)
{
	App.Screens.showLoading(sLoading);
};

CApi.prototype.hideLoading = function ()
{
	App.Screens.hideLoading();
};

/**
 * @param {string} sReport
 * @param {number=} iDelay if 0 comes then report will not be closed automatically
 */
CApi.prototype.showReport = function (sReport, iDelay)
{
	App.Screens.showReport(sReport, iDelay);
};

/**
 * @param {string} sError
 * @param {boolean=} bHtml = false
 * @param {boolean=} bNotHide = false
 * @param {boolean=} bGray = false
 */
CApi.prototype.showError = function (sError, bHtml, bNotHide, bGray)
{
	App.Screens.showError(sError, bHtml, bNotHide, bGray);
};

/**
 * @param {boolean=} bGray = false
 */
CApi.prototype.hideError = function (bGray)
{
	App.Screens.hideError(bGray);
};

/**
 * @param {Object} oRes
 * @param {string} sPgpAction
 * @param {string=} sDefaultError
 */
CApi.prototype.showPgpErrorByCode = function (oRes, sPgpAction, sDefaultError)
{
	var
		aErrors = Utils.isNonEmptyArray(oRes.errors) ? oRes.errors : [],
		aNotices = Utils.isNonEmptyArray(oRes.notices) ? oRes.notices : [],
		aEmailsWithoutPublicKey = [],
		aEmailsWithoutPrivateKey = [],
		sError = '',
		bNoSignDataNotice = false,
		bNotice = true
	;
	
	_.each(_.union(aErrors, aNotices), function (aError) {
		if (aError.length === 2)
		{
			switch(aError[0])
			{
				case OpenPgpResult.Enum.GenerateKeyError:
					sError = Utils.i18n('OPENPGP/ERROR_GENERATE_KEY');
					break;
				case OpenPgpResult.Enum.ImportKeyError:
					sError = Utils.i18n('OPENPGP/ERROR_IMPORT_KEY');
					break;
				case OpenPgpResult.Enum.ImportNoKeysFoundError:
					sError = Utils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_FOUND');
					break;
				case OpenPgpResult.Enum.PrivateKeyNotFoundError:
				case OpenPgpResult.Enum.PrivateKeyNotFoundNotice:
					aEmailsWithoutPrivateKey.push(aError[1]);
					break;
				case OpenPgpResult.Enum.PublicKeyNotFoundError:
					bNotice = false;
					aEmailsWithoutPublicKey.push(aError[1]);
					break;
				case OpenPgpResult.Enum.PublicKeyNotFoundNotice:
					aEmailsWithoutPublicKey.push(aError[1]);
					break;
				case OpenPgpResult.Enum.KeyIsNotDecodedError:
					if (sPgpAction === Enums.PgpAction.DecryptVerify)
					{
						sError = Utils.i18n('OPENPGP/ERROR_DECRYPT') + ' ' + Utils.i18n('OPENPGP/ERROR_KEY_NOT_DECODED', {'USER': aError[1]});
					}
					else if (sPgpAction === Enums.PgpAction.Sign || sPgpAction === Enums.PgpAction.EncryptSign)
					{
						sError = Utils.i18n('OPENPGP/ERROR_SIGN') + ' ' + Utils.i18n('OPENPGP/ERROR_KEY_NOT_DECODED', {'USER': aError[1]});
					}
					break;
				case OpenPgpResult.Enum.SignError:
					sError = Utils.i18n('OPENPGP/ERROR_SIGN');
					break;
				case OpenPgpResult.Enum.VerifyError:
					sError = Utils.i18n('OPENPGP/ERROR_VERIFY');
					break;
				case OpenPgpResult.Enum.EncryptError:
					sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT');
					break;
				case OpenPgpResult.Enum.DecryptError:
					sError = Utils.i18n('OPENPGP/ERROR_DECRYPT');
					break;
				case OpenPgpResult.Enum.SignAndEncryptError:
					sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT_OR_SIGN');
					break;
				case OpenPgpResult.Enum.VerifyAndDecryptError:
					sError = Utils.i18n('OPENPGP/ERROR_DECRYPT_OR_VERIFY');
					break;
				case OpenPgpResult.Enum.DeleteError:
					sError = Utils.i18n('OPENPGP/ERROR_DELETE_KEY');
					break;
				case OpenPgpResult.Enum.VerifyErrorNotice:
					sError = Utils.i18n('OPENPGP/ERROR_VERIFY');
					break;
				case OpenPgpResult.Enum.NoSignDataNotice:
					bNoSignDataNotice = true;
					break;
			}
		}
	});
	
	if (aEmailsWithoutPublicKey.length > 0)
	{
		aEmailsWithoutPublicKey = _.without(aEmailsWithoutPublicKey, '');
		if (aEmailsWithoutPublicKey.length > 0)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PUBLIC_KEYS_FOR_USERS_PLURAL', 
					{'USERS': aEmailsWithoutPublicKey.join(', ')}, null, aEmailsWithoutPublicKey.length);
		}
		else if (sPgpAction === Enums.PgpAction.Verify)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PUBLIC_KEY_FOUND_FOR_VERIFY');
		}
		if (bNotice && sError !== '')
		{
			sError += ' ' + Utils.i18n('OPENPGP/ERROR_MESSAGE_WAS_NOT_VERIFIED');
		}
	}
	else if (aEmailsWithoutPrivateKey.length > 0)
	{
		aEmailsWithoutPrivateKey = _.without(aEmailsWithoutPrivateKey, '');
		if (aEmailsWithoutPrivateKey.length > 0)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PRIVATE_KEYS_FOR_USERS_PLURAL', 
					{'USERS': aEmailsWithoutPrivateKey.join(', ')}, null, aEmailsWithoutPrivateKey.length);
		}
		else if (sPgpAction === Enums.PgpAction.DecryptVerify)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PRIVATE_KEY_FOUND_FOR_DECRYPT');
		}
	}
	
	if (sError === '' && !bNoSignDataNotice)
	{
		switch (sPgpAction)
		{
			case Enums.PgpAction.Generate:
				sError = Utils.i18n('OPENPGP/ERROR_GENERATE_KEY');
				break;
			case Enums.PgpAction.Import:
				sError = Utils.i18n('OPENPGP/ERROR_IMPORT_KEY');
				break;
			case Enums.PgpAction.DecryptVerify:
				sError = Utils.i18n('OPENPGP/ERROR_DECRYPT');
				break;
			case Enums.PgpAction.Verify:
				sError = Utils.i18n('OPENPGP/ERROR_VERIFY');
				break;
			case Enums.PgpAction.Encrypt:
				sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT');
				break;
			case Enums.PgpAction.EncryptSign:
				sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT_OR_SIGN');
				break;
			case Enums.PgpAction.Sign:
				sError = Utils.i18n('OPENPGP/ERROR_SIGN');
				break;
		}
		sError = sDefaultError;
	}
	
	if (sError !== '')
	{
		App.Api.showError(sError);
	}
	
	return bNoSignDataNotice;
};

/**
 * @param {Object} oResponse
 * @param {string=} sDefaultError
 * @param {boolean=} bNotHide = false
 */
CApi.prototype.showErrorByCode = function (oResponse, sDefaultError, bNotHide)
{
	var
		iErrorCode = oResponse.ErrorCode,
		sResponseError = oResponse.ErrorMessage || '',
		sResultError = ''
	;
	
	switch (iErrorCode)
	{
		default:
			sResultError = sDefaultError;
			break;
		case Enums.Errors.AuthError:
			sResultError = Utils.i18n('WARNING/LOGIN_PASS_INCORRECT');
			break;
		case Enums.Errors.DataBaseError:
			sResultError = Utils.i18n('WARNING/DATABASE_ERROR');
			break;
		case Enums.Errors.LicenseProblem:
			sResultError = Utils.i18n('WARNING/INVALID_LICENSE');
			break;
		case Enums.Errors.DemoLimitations:
			sResultError = Utils.i18n('DEMO/WARNING_THIS_FEATURE_IS_DISABLED');
			break;
		case Enums.Errors.Captcha:
			sResultError = Utils.i18n('WARNING/CAPTCHA_IS_INCORRECT');
			break;
		case Enums.Errors.CanNotGetMessage:
			sResultError = Utils.i18n('MESSAGE/ERROR_MESSAGE_DELETED');
			break;
		case Enums.Errors.NoRequestedMailbox:
			sResultError = sDefaultError + ' ' + Utils.i18n('COMPOSE/ERROR_INVALID_ADDRESS', {'ADDRESS': (oResponse.Mailbox || '')});
			break;
		case Enums.Errors.CanNotChangePassword:
			sResultError = Utils.i18n('WARNING/UNABLE_CHANGE_PASSWORD');
			break;
		case Enums.Errors.AccountOldPasswordNotCorrect:
			sResultError = Utils.i18n('WARNING/CURRENT_PASSWORD_NOT_CORRECT');
			break;
		case Enums.Errors.FetcherIncServerNotAvailable:
			sResultError = Utils.i18n('WARNING/FETCHER_SAVE_ERROR');
			break;
		case Enums.Errors.FetcherLoginNotCorrect:
			sResultError = Utils.i18n('WARNING/FETCHER_SAVE_ERROR');
			break;
		case Enums.Errors.HelpdeskUserNotExists:
			sResultError = Utils.i18n('HELPDESK/ERROR_FORGOT_NO_ACCOUNT');
			break;
		case Enums.Errors.MailServerError:
			sResultError = Utils.i18n('WARNING/CANT_CONNECT_TO_SERVER');
			break;
		case Enums.Errors.DataTransferFailed:
			sResultError = Utils.i18n('WARNING/DATA_TRANSFER_FAILED');
			break;
		case Enums.Errors.NotDisplayedError:
			sResultError = '';
			break;
	}
	
	if (sResultError !== '')
	{
		if (sResponseError !== '')
		{
			sResultError += ' (' + sResponseError + ')';
		}
		this.showError(sResultError, false, !!bNotHide);
	}
	else if (sResponseError !== '')
	{
		this.showError(sResponseError);
	}
};

/**
 * @param {string} sFileName
 * @param {number} iSize
 * @returns {Boolean}
 */
CApi.prototype.showErrorIfAttachmentSizeLimit = function (sFileName, iSize)
{
	var
		sWarning = Utils.i18n('COMPOSE/UPLOAD_ERROR_FILENAME_SIZE', {
			'FILENAME': sFileName,
			'MAXSIZE': Utils.friendlySize(AppData.App.AttachmentSizeLimit)
		})
	;
	
	if (AppData.App.AttachmentSizeLimit > 0 && iSize > AppData.App.AttachmentSizeLimit)
	{
		App.Screens.showPopup(AlertPopup, [sWarning]);
		return true;
	}
	
	return false;
};

/**
 * Moves the specified messages in the current folder to the Trash or delete permanently 
 * if the current folder is Trash or Spam.
 * 
 * @param {Array} aUids
 * @param {Object} oApp
 * @param {Function=} fAfterDelete
 */
CApi.prototype.deleteMessages = function (aUids, oApp, fAfterDelete)
{
	if (!Utils.isFunc(fAfterDelete))
	{
		fAfterDelete = function () {};
	}
	
	var
		oFolderList = App.MailCache.folderList(),
		sCurrFolder = oFolderList.currentFolderFullName(),
		oTrash = oFolderList.trashFolder(),
		bInTrash =(oTrash && sCurrFolder === oTrash.fullName()),
		oSpam = oFolderList.spamFolder(),
		bInSpam = (oSpam && sCurrFolder === oSpam.fullName()),
		fDeleteMessages = function (bResult) {
			if (bResult)
			{
				oApp.MailCache.deleteMessages(aUids);
				fAfterDelete();
			}
		}
	;
	
	if (bInSpam)
	{
		oApp.MailCache.deleteMessages(aUids);
		fAfterDelete();
	}
	else if (bInTrash)
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('MAILBOX/CONFIRM_MESSAGES_DELETE'), fDeleteMessages]);
	}
	else if (oTrash)
	{
		oApp.MailCache.moveMessagesToFolder(oTrash.fullName(), aUids);
		fAfterDelete();
	}
	else if (!oTrash)
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('MAILBOX/CONFIRM_MESSAGES_DELETE_NO_TRASH_FOLDER'), fDeleteMessages]);
	}
};

CApi.prototype.contactCreate = function (sName, sEmail, fContactCreateResponse, oContactCreateContext)
{
	//function is overrided in contacts module
};
