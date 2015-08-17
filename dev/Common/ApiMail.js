/**
 * These methods are not includes in js for mobile version.
 */

CApi.prototype.composeMessage = function ()
{
	App.Screens.showPopup(ComposePopup);
};

/**
 * @param {string} sFolder
 * @param {string} sUid
 */
CApi.prototype.composeMessageFromDrafts = function (sFolder, sUid)
{
	var aParams = App.Links.composeFromMessage('drafts', sFolder, sUid);
	aParams.shift();
	App.Screens.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {string} sReplyType
 * @param {string} sFolder
 * @param {string} sUid
 */
CApi.prototype.composeMessageAsReplyOrForward = function (sReplyType, sFolder, sUid)
{
	var aParams = App.Links.composeFromMessage(sReplyType, sFolder, sUid);
	if (AppData.SingleMode)
	{
		App.Routing.setHash(aParams);
	}
	else
	{
		aParams.shift();
		App.Screens.showPopup(ComposePopup, [aParams]);
	}
};

/**
 * @param {string} sToAddresses
 */
CApi.prototype.composeMessageToAddresses = function (sToAddresses)
{
	var aParams = App.Links.composeWithToField(sToAddresses);
	if (AppData.SingleMode)
	{
		App.Routing.setHash(aParams);
	}
	else
	{
		aParams.shift();
		App.Screens.showPopup(ComposePopup, [aParams]);
	}
};

/**
 * @param {Object} oVcard
 */
CApi.prototype.composeMessageWithVcard = function (oVcard)
{
	var aParams = ['vcard', oVcard];
	App.Screens.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {string} sArmor
 * @param {string} sDownloadLinkFilename
 */
CApi.prototype.composeMessageWithPgpKey = function (sArmor, sDownloadLinkFilename)
{
	var aParams = ['data-as-file', sArmor, sDownloadLinkFilename];
	App.Screens.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {Array} aFileItems
 */
CApi.prototype.composeMessageWithFiles = function (aFileItems)
{
	var aParams = ['file', aFileItems];
	App.Screens.showPopup(ComposePopup, [aParams]);
};

CApi.prototype.closeComposePopup = function ()
{
	App.Screens.showPopup(ComposePopup, [['close']]);
};

/**
 * @param {string} sEmail
 */
CApi.prototype.createMailAccount = function (sEmail)
{
	App.Screens.showPopup(AccountCreatePopup, [Enums.AccountCreationPopupType.OneStep, sEmail]);
};

CApi.prototype.showChangeDefaultAccountPasswordPopup = function ()
{
	var oDefaultAccount = AppData.Accounts.getDefault();
	
	App.Screens.showPopup(ChangePasswordPopup, [false, oDefaultAccount.passwordSpecified(), function () { 
			oDefaultAccount.passwordSpecified(true); 
			if (AfterLogicApi.runPluginHook)
			{
				AfterLogicApi.runPluginHook('api-mail-on-password-specified-success', [this.__name, this]);
			}	
	}]);
};

/**
 * @param {Function=} fAfterConfigureMail
 */
CApi.prototype.showConfigureMailPopup = function (fAfterConfigureMail)
{
	var oDefaultAccount = AppData.Accounts.getDefault();
	
	if (oDefaultAccount && !oDefaultAccount.allowMail())
	{
		App.Screens.showPopup(AccountCreatePopup, [Enums.AccountCreationPopupType.ConnectToMail, oDefaultAccount.email(), fAfterConfigureMail]);
	}
};
