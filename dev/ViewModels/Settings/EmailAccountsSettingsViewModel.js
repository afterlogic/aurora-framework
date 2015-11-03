/**
 * @constructor
 */
function CEmailAccountsSettingsViewModel()
{
	this.bShown = false;
	
	this.accounts = AppData.Accounts.collection;
	this.onlyOneAccount = ko.computed(function () {
		var bOnlyOneAccount = this.accounts().length === 1 && !AppData.App.AllowUsersAddNewAccounts;
		if (bOnlyOneAccount)
		{
			this.TabTitle = Utils.i18n('SETTINGS/TAB_EMAIL_ACCOUNT');
		}
		return bOnlyOneAccount;
	}, this);
	this.title = ko.computed(function () {
		return this.onlyOneAccount() ? Utils.i18n('SETTINGS/TITLE_EMAIL_ACCOUNT') : Utils.i18n('SETTINGS/TITLE_EMAIL_ACCOUNTS');
	}, this);
	
	this.currentAccountId = AppData.Accounts.currentId;
	this.editedAccountId = AppData.Accounts.editedId;
	this.defaultAccountId = AppData.Accounts.defaultId;
	this.defaultAccount = AppData.Accounts.getDefault();
	
	this.isAllowMail = ko.observable(true);
	this.isAllowFetcher = !!AppData.User.AllowFetcher;
	this.isAllowIdentities = !!AppData.AllowIdentities;

	this.oAccountProperties = new CAccountPropertiesViewModel(this);
	this.oAccountSignature = new CAccountSignatureViewModel(this);
	this.oAccountFilters = new CAccountFiltersViewModel(this);
	this.oAccountAutoresponder = new CAccountAutoresponderViewModel(this);
	this.oAccountForward = new CAccountForwardViewModel(this);
	this.oAccountFolders = new CAccountFoldersViewModel(this);

	this.oFetcherIncoming = new CFetcherIncomingViewModel(this);
	this.oFetcherOutgoing = new CFetcherOutgoingViewModel(this);
	this.oFetcherSignature = new CFetcherSignatureViewModel(this);
	
	this.oIdentityProperties = new CIdentityPropertiesViewModel(this, false);

	this.fetcher = ko.observable(null);
	this.fetchers = ko.observable(null);
	this.firstFetcher = ko.observable(null);
	this.editedFetcherId = ko.observable(null);
	this.editedIdentityId = ko.observable(null);
	
	this.defaultAccount.fetchers.subscribe(function(oList) {
		if (!oList)
		{
			this.onChangeAccount(this.defaultAccountId());
		}
		else
		{
			var
				oFetchers = this.defaultAccount.fetchers(),
				oFirstFetcher = oFetchers.collection()[0],
				nFirstFetcherId = oFirstFetcher.id(),
				isFetcherTAb = this.isFetcherTab(this.tab())
			;

			this.fetchers(oFetchers);
			this.firstFetcher(oFirstFetcher);
			
			if (isFetcherTAb && !oFetchers.hasFetcher(this.editedFetcherId()))
			{
				this.editedFetcherId(nFirstFetcherId);
				this.editedIdentityId(null);
				this.onChangeFetcher(nFirstFetcherId);
			}
		}
	}, this);

	this.allowProperties = ko.observable(true);

	this.tab = ko.observable(Enums.AccountSettingsTab.Properties);

	this.allowUsersAddNewAccounts = AppData.App.AllowUsersAddNewAccounts;
	
	this.allowUsersChangeInterfaceSettings = AppData.App.AllowUsersChangeInterfaceSettings;
	
	this.allowAutoresponderExtension = ko.observable(false);
	this.allowForwardExtension = ko.observable(false);
	this.allowSieveFiltersExtension = ko.observable(false);

	this.onChangeAccount(this.editedAccountId());

}

CEmailAccountsSettingsViewModel.prototype.TemplateName = 'Settings_EmailAccountsSettingsViewModel';

CEmailAccountsSettingsViewModel.prototype.TabName = Enums.SettingsTab.EmailAccounts;

CEmailAccountsSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_EMAIL_ACCOUNTS');

CEmailAccountsSettingsViewModel.prototype.isChanged = function ()
{
	return false;
};

/**
 * @param {Array} aParams
 */
CEmailAccountsSettingsViewModel.prototype.onRoute = function (aParams)
{
	this.bShown = true;
	var
		oAccount = AppData.Accounts.getEdited(),
		sNewTab = aParams[1] || this.tab()
	;

	if (oAccount)
	{
		if (sNewTab === Enums.AccountSettingsTab.Properties && !this.allowProperties())
		{
			sNewTab = Enums.AccountSettingsTab.Folders;
		}
		
		if (sNewTab !== Enums.AccountSettingsTab.Properties && !this.isAllowMail())
		{
			sNewTab = '';
		}
		
		if (sNewTab === '')
		{
			this.tab('');
		}
		else
		{
			if (sNewTab !== aParams[1])
			{
				App.Routing.replaceHash([Enums.Screens.Settings, Enums.SettingsTab.EmailAccounts, sNewTab]);
			}
			this.changeCurrentTab(sNewTab);
		}
		
		if (!_.isArray(aParams) || aParams.length <= 1)
		{
			this.onChangeAccount(this.editedAccountId());
		}
	}
};

/**
 * @param {string} sTab
 * @param {Function=} fAfterConfirm
 */

/**
 * @param {Array} aParams
 */
CEmailAccountsSettingsViewModel.prototype.onHide = function (aParams)
{
	var oCurrentViewModel = this.getCurrentViewModel();
	
	this.confirmSaving(this.tab());
	
	if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onHide))
	{
		oCurrentViewModel.onHide();
	}
	this.bShown = false;
};

/**
 * @param {string} sTab
 */
CEmailAccountsSettingsViewModel.prototype.changeCurrentTab = function (sTab)
{
	var
		oAccount = AppData.Accounts.getEdited(),
		oCurrentViewModel = null,
		bTabAllowed = this.isTabAllowed(sTab, oAccount),
		fChangeTabAfterConfirm = _.bind(function () {
			if (this.tab() !== sTab)
			{
				oCurrentViewModel = this.getCurrentViewModel();
				if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onHide))
				{
					oCurrentViewModel.onHide();
				}
			}

			this.tab(sTab);
			
			oCurrentViewModel = this.getCurrentViewModel();
			if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.onShow))
			{
				oCurrentViewModel.onShow(oAccount);
			}
		}, this)
	;
	
	if (oAccount)
	{
		if (bTabAllowed)
		{
			fChangeTabAfterConfirm();
		}
		else if (this.tab() === sTab)
		{
			this.tab(Enums.AccountSettingsTab.Properties);
			App.Routing.replaceHash([Enums.Screens.Settings, Enums.SettingsTab.EmailAccounts, Enums.AccountSettingsTab.Properties]);
			this.editedFetcherId(null);
			this.editedIdentityId(null);
		}
		else
		{
			App.Routing.replaceHash([Enums.Screens.Settings, Enums.SettingsTab.EmailAccounts, this.tab()]);
		}
	}
};

/**
 * @param {string} sTab
 * @returns {Object}
 */
CEmailAccountsSettingsViewModel.prototype.getViewModel = function (sTab)
{
	switch (sTab) 
	{
		case Enums.AccountSettingsTab.Folders:
			return this.oAccountFolders;
		case Enums.AccountSettingsTab.Filters:
			return this.oAccountFilters;
		case Enums.AccountSettingsTab.Forward:
			return this.oAccountForward;
		case Enums.AccountSettingsTab.Signature:
			return this.oAccountSignature;
		case Enums.AccountSettingsTab.Autoresponder:
			return this.oAccountAutoresponder;
		case Enums.AccountSettingsTab.FetcherInc:
			return this.oFetcherIncoming;
		case Enums.AccountSettingsTab.FetcherOut:
			return this.oFetcherOutgoing;
		case Enums.AccountSettingsTab.FetcherSig:
			return this.oFetcherSignature;
		case Enums.AccountSettingsTab.IdentityProperties:
			return this.oIdentityProperties;
		case Enums.AccountSettingsTab.IdentitySignature:
			return this.oIdentityProperties;
		default:
		case Enums.AccountSettingsTab.Properties:
			return this.oAccountProperties;
	}
};

CEmailAccountsSettingsViewModel.prototype.getCurrentViewModel = function ()
{
	return this.getViewModel(this.tab());
};

/**
 * @param {string} sTab
 * @param {Object} oAccount
 * @returns {Boolean}
 */
CEmailAccountsSettingsViewModel.prototype.isTabAllowed = function (sTab, oAccount)
{
	var
		aAllowedTabs = [
			Enums.AccountSettingsTab.Properties, Enums.AccountSettingsTab.Signature,
			Enums.AccountSettingsTab.Folders, Enums.AccountSettingsTab.FetcherInc,
			Enums.AccountSettingsTab.FetcherOut, Enums.AccountSettingsTab.FetcherSig
		]
	;
	
	if (oAccount.allowMail() && oAccount.extensionExists('AllowSieveFiltersExtension'))
	{
		aAllowedTabs.push(Enums.AccountSettingsTab.Filters);
	}
	if (oAccount.allowMail() && oAccount.extensionExists('AllowForwardExtension'))
	{
		aAllowedTabs.push(Enums.AccountSettingsTab.Forward);
	}
	if (oAccount.allowMail() && oAccount.extensionExists('AllowAutoresponderExtension'))
	{
		aAllowedTabs.push(Enums.AccountSettingsTab.Autoresponder);
	}
	if (AppData.AllowIdentities && this.editedIdentityId())
	{
		aAllowedTabs.push(Enums.AccountSettingsTab.IdentityProperties);
		aAllowedTabs.push(Enums.AccountSettingsTab.IdentitySignature);
	}
	return -1 !== Utils.inArray(sTab, aAllowedTabs);
};

/**
 * @param {string} sTab
 */
CEmailAccountsSettingsViewModel.prototype.onTabClick = function (sTab)
{
	if (this.bShown)
	{
		this.confirmSaving(
			this.tab(),
			_.bind(function () {
				App.Routing.setHash([Enums.Screens.Settings, Enums.SettingsTab.EmailAccounts, sTab]);
			}, this)
		);
	}
};

CEmailAccountsSettingsViewModel.prototype.removeEditedAccount = function ()
{
	var
		fChangeAccount = _.bind(function () {
			this.onChangeAccount(AppData.Accounts.editedId());
		}, this),
		oEditedAccount = AppData.Accounts.getEdited()
	;
	oEditedAccount.remove(fChangeAccount);
};

CEmailAccountsSettingsViewModel.prototype.onAccountAdd = function ()
{
	App.Screens.showPopup(AccountCreatePopup, [Enums.AccountCreationPopupType.TwoSteps, '', _.bind(function (iAccountId) {
		this.onChangeAccount(iAccountId);
	}, this)]);
};

/**
 * @param {number} iAccountId
 */
CEmailAccountsSettingsViewModel.prototype.fillAccountPermissions = function (iAccountId)
{
	var
		oAccount = AppData.Accounts.getAccount(iAccountId),
		bAllowMail = !!oAccount && oAccount.allowMail(),
		bDefault = !!oAccount && oAccount.isDefault(),
		bChangePass = !!oAccount && oAccount.extensionExists('AllowChangePasswordExtension'),
		bCanBeRemoved =  !!oAccount && oAccount.canBeRemoved() && !oAccount.isDefault()
	;
	
	this.isAllowMail(oAccount && oAccount.allowMail());
	this.allowProperties((!bDefault || bDefault && AppData.App.AllowUsersChangeEmailSettings) && bAllowMail || !AppData.AllowIdentities || bChangePass || bCanBeRemoved);
};

/**
 * @param {number} iAccountId
 */
CEmailAccountsSettingsViewModel.prototype.onChangeAccount = function (iAccountId)
{
	this.fillAccountPermissions(iAccountId);
	
	this.confirmSaving(
		this.tab(),
		_.bind(function () {
			var
				oAccount = AppData.Accounts.getAccount(iAccountId),
				oParameters = {
					'Action': 'AccountSettingsGet',
					'AccountID': iAccountId
				},
				bNeedToChangeTab = (this.isFetcherTab(this.tab()) || this.isIdentityTab(this.tab()) ||
						!this.allowProperties() && Enums.AccountSettingsTab.Properties === this.tab() ||
						!this.isAllowMail() && Enums.AccountSettingsTab.Properties !== this.tab())
			;
			
			if (bNeedToChangeTab)
			{
				if (this.allowProperties())
				{
					this.onTabClick(Enums.AccountSettingsTab.Properties);
				}
				else if (this.isAllowMail())
				{
					this.onTabClick(Enums.AccountSettingsTab.Folders);
				}
				else
				{
					this.tab('');
				}
			}

			this.confirmSaving(this.tab());

			if (oAccount)
			{
				this.populate(oAccount);
			}
			
			if (!oAccount || !oAccount.isExtended())
			{
				App.Ajax.send(oParameters, this.onAccountSettingsGetResponse, this);
			}

			this.editedFetcherId(null);
			this.editedIdentityId(null);
		}, this),
		iAccountId
	);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CEmailAccountsSettingsViewModel.prototype.onAccountSettingsGetResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oAccount = AppData.Accounts.getAccount(oRequest.AccountID);
		if (!Utils.isUnd(oAccount))
		{
			oAccount.updateExtended(oResponse.Result);
			if (oAccount.id() === this.editedAccountId())
			{
				this.populate(oAccount);
				this.fillAccountPermissions(oAccount.id());
			}
		}	
	}
};

/**
 * @param {Object} oAccount
 */
CEmailAccountsSettingsViewModel.prototype.populate = function (oAccount)
{
	this.allowAutoresponderExtension(oAccount.allowMail() && oAccount.extensionExists('AllowAutoresponderExtension'));
	this.allowForwardExtension(oAccount.allowMail() && oAccount.extensionExists('AllowForwardExtension'));
	this.allowSieveFiltersExtension(oAccount.allowMail() && oAccount.extensionExists('AllowSieveFiltersExtension'));

	AppData.Accounts.changeEditedAccount(oAccount.id());
	
	if (this.bShown)
	{
		this.changeCurrentTab(this.tab());
	}
};

/**
 * @param {number} iId
 * @param {Object} oEv
 */
CEmailAccountsSettingsViewModel.prototype.onIdentityAdd = function (iId, oEv)
{
	oEv.stopPropagation();
	App.Screens.showPopup(CreateIdentityPopup, [iId]);
};

/**
 * @param {number} iId
 * @param {Object} oEv
 */
CEmailAccountsSettingsViewModel.prototype.onConnectToMail = function (iId, oEv)
{
	oEv.stopPropagation();
	
	App.Api.showConfigureMailPopup(_.bind(function (iAccountId) { this.onChangeAccount(iAccountId); }, this));
};

/**
 * @param {Object} oModel
 * @param {Object} oEv
 */
CEmailAccountsSettingsViewModel.prototype.onFetcherAdd = function (oModel, oEv)
{
//	oEv.stopPropagation();
	App.Screens.showPopup(FetcherAddPopup, []);
};

/**
 * @param {number} iFetcherId
 * @param {boolean} bOkAnswer
 */
CEmailAccountsSettingsViewModel.prototype.fetcherDelete = function (iFetcherId, bOkAnswer)
{
	var oParameters = {
		'Action': 'AccountFetcherDelete',
		'FetcherID': iFetcherId
	};

	if (bOkAnswer)
	{
		App.Ajax.send(oParameters, this.onAccountFetcherDeleteResponse, this);
	}
};

/**
 * @param {Object} oData
 */
CEmailAccountsSettingsViewModel.prototype.onFetcherDeleteClick = function (oData)
{
	var
		sWarning = Utils.i18n('WARNING/FETCHER_DELETE_WARNING'),
		fCallBack = _.bind(this.fetcherDelete, this, oData.idFetcher()),
		sTitle = oData.incomingMailServer ? oData.incomingMailServer() : ''
	;

	App.Screens.showPopup(ConfirmPopup, [sWarning, fCallBack, sTitle]);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CEmailAccountsSettingsViewModel.prototype.onAccountFetcherDeleteResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse.ErrorCode, Utils.i18n('WARNING/FETCHER_DELETING_ERROR'));
	}
	else
	{
		AppData.Accounts.populateFetchers();
		this.editedFetcherId(null);
	}
};

/**
 * @param {number} iFetcherId
 */
CEmailAccountsSettingsViewModel.prototype.onChangeFetcher = function (iFetcherId)
{
	this.fillAccountPermissions(AppData.Accounts.defaultId());
	
	this.confirmSaving(
		this.tab(),
		_.bind(function () {
			var oFetcher = this.defaultAccount.fetchers().getFetcher(iFetcherId);

			this.fetcher(oFetcher);

			if (!this.isFetcherTab(this.tab()))
			{
				this.onTabClick(Enums.AccountSettingsTab.FetcherInc);
			}

			this.confirmSaving(this.tab());

			this.oFetcherIncoming.populate(oFetcher);
			this.oFetcherOutgoing.populate(oFetcher);
			this.oFetcherSignature.populate(oFetcher);

			this.editedFetcherId(oFetcher.id());
			this.editedIdentityId(null);
		}, this),
		iFetcherId);
};

/**
 * @param {string} sTab
 */
CEmailAccountsSettingsViewModel.prototype.isFetcherTab = function (sTab)
{
	return -1 !== Utils.inArray(sTab, [Enums.AccountSettingsTab.FetcherInc, 
		Enums.AccountSettingsTab.FetcherOut, Enums.AccountSettingsTab.FetcherSig]);
};

/**
 * @param {Object} oIdentity
 */
CEmailAccountsSettingsViewModel.prototype.onChangeIdentity = function (oIdentity)
{
	this.fillAccountPermissions(oIdentity.accountId());
	
	this.confirmSaving (
		this.tab(),
		_.bind(function () {
			this.onTabClick(Enums.AccountSettingsTab.IdentityProperties);

			this.oIdentityProperties.populate(oIdentity);
			this.editedIdentityId(oIdentity.id());
			this.editedFetcherId(null);
		}, this),
		oIdentity);
};

/**
 * @param {string} sTab
 */
CEmailAccountsSettingsViewModel.prototype.isIdentityTab = function (sTab)
{
	return -1 !== Utils.inArray(sTab, [Enums.AccountSettingsTab.IdentityProperties, Enums.AccountSettingsTab.IdentitySignature]);
};

CEmailAccountsSettingsViewModel.prototype.onRemoveIdentity = function ()
{
	this.editedIdentityId(null);
	this.changeCurrentTab(this.tab());
};

/**
 * @param {string} sTab
 * @param {function} fCallback
 * @param {mixed} mArgument
 */
CEmailAccountsSettingsViewModel.prototype.confirmSaving = function (sTab, fCallback, mArgument)
{
	var
		oCurrentViewModel = this.getViewModel(sTab),
		fAction = _.bind(function (bResult) {
			if (oCurrentViewModel)
			{
				setTimeout(function () {
					if (bResult)
					{
						if (Utils.isFunc(oCurrentViewModel.onSaveClick))
						{
							oCurrentViewModel.onSaveClick();
						}
					}
					else
					{
						if (Utils.isFunc(oCurrentViewModel.revert))
						{
							oCurrentViewModel.revert();
						}
						oCurrentViewModel.updateFirstState();
					}

					if (Utils.isFunc(fCallback))
					{
						if (mArgument)
						{
							fCallback(mArgument);
						}
						else
						{
							fCallback();
						}
					}
				}.bind(this), 1);
			}
		}, this)
	;

	if (oCurrentViewModel && Utils.isFunc(oCurrentViewModel.isChanged) && oCurrentViewModel.isChanged())
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('SETTINGS/CONFIRM_SETTINGS_SAVE'), fAction, '', Utils.i18n('SETTINGS/BUTTON_SAVE'), Utils.i18n('SETTINGS/BUTTON_DISCARD')]);
	}
	else if (Utils.isFunc(fCallback))
	{
		if (mArgument)
		{
			fCallback(mArgument);
		}
		else
		{
			fCallback();
		}
	}
};