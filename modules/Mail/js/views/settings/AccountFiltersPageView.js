
/**
 * @constructor
 */
function CAccountFiltersViewModel()
{
	this.bShown = false;
	
	this.oEditedAccount = null;
	
	this.foldersOptions = ko.observableArray([]);
	
	App.MailCache.editedFolderList.subscribe(function () {
		if (this.bShown)
		{
			this.populate();
		}
	}, this);
	
	this.loading = ko.observable(true);
	this.saving = ko.observable(false);
	this.collection = ko.observableArray([]);

	this.fieldOptions = [
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_FIELD_FROM'), 'value': 0},
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_FIELD_TO'), 'value': 1},
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_FIELD_SUBJECT'), 'value': 2}
	];

	this.conditionOptions = [
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_COND_CONTAIN_SUBSTR'), 'value': 0},
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_COND_EQUAL_TO'), 'value': 1},
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_COND_NOT_CONTAIN_SUBSTR'), 'value': 2}
	];

	this.actionOptions = [
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_ACTION_MOVE'), 'value': 3},
		{'text': Utils.i18n('SETTINGS/ACCOUNT_FILTERS_ACTION_DELETE'), 'value': 1}
	];
	
	this.phaseArray = [''];
	
	_.each(Utils.i18n('SETTINGS/ACCOUNT_FILTERS_PHRASE').split(/\s/), function (sItem) {
		var iIndex = this.phaseArray.length - 1;
		if (sItem.substr(0,1) === '%' || this.phaseArray[iIndex].substr(-1,1) === '%')
		{
			this.phaseArray.push(sItem);
		}
		else
		{
			this.phaseArray[iIndex] += ' ' + sItem;
		}
	}, this);
	
	this.firstState = null;
}

/**
 * @param {Object} oAccount
 */
CAccountFiltersViewModel.prototype.onShow = function (oAccount)
{
	this.bShown = true;
	this.oEditedAccount = oAccount;
	this.populate();
};

CAccountFiltersViewModel.prototype.onHide = function ()
{
	this.bShown = false;
	this.collection([]);
	this.updateFirstState();
};

CAccountFiltersViewModel.prototype.populate = function ()
{
	var
		oFolderList = App.MailCache.editedFolderList(),
		aOptionList = []
	;

	if (oFolderList.iAccountId === this.oEditedAccount.id())
	{
		aOptionList = oFolderList.getOptions(Utils.i18n('SETTINGS/ACCOUNT_FOLDERS_NOT_SELECTED'), true, true, false, true);
		this.foldersOptions(aOptionList);
		this.getFilters();
	}
	else
	{
		this.loading(true);
		this.collection([]);
	}
};

CAccountFiltersViewModel.prototype.revert = function ()
{
	_.each(this.collection(), function (oFilter) {
		oFilter.revert();
	});
};

CAccountFiltersViewModel.prototype.commit = function ()
{
	_.each(this.collection(), function (oFilter) {
		oFilter.commit();
	});
};

CAccountFiltersViewModel.prototype.getState = function ()
{
	var
		sResult = ':',		
		aState = _.map(this.collection(), function (oFilter) {
			return oFilter.toString();
		}, this)
	;
	if (aState.length > 0)
	{
		sResult = aState.join(':');
	}
	return sResult;
};

CAccountFiltersViewModel.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CAccountFiltersViewModel.prototype.isChanged = function()
{
	return this.firstState && (this.getState() !== this.firstState);
};

CAccountFiltersViewModel.prototype.prepareParameters = function ()
{
	var
		aFilters =_.map(this.collection(), function (oItem) {
			return {
				'Enable': oItem.enable() ? '1' : '0',
				'Field': oItem.field(),
				'Filter': oItem.filter(),
				'Condition': oItem.condition(),
				'Action': oItem.action(),
				'FolderFullName': oItem.folder()
			};
		}),
		oParameters = {
			'Action': 'AccountSieveFiltersUpdate',
			'AccountID': this.oEditedAccount.id(),
			'Filters': aFilters
		}
	;
	
	return oParameters;
};

/**
 * @param {Object} oParameters
 */
CAccountFiltersViewModel.prototype.saveData = function (oParameters)
{
	var bCantSave =_.some(this.collection(), function (oFilter) {
		return oFilter.filter() === '' || (Utils.pString(oFilter.action()) === '3' /* Move */ && oFilter.folder() === '');
	});

	if (bCantSave)
	{
		App.Api.showError(Utils.i18n('SETTINGS/ERROR_FILTERS_FIELDS_FILL'));
	}
	else
	{
		this.saving(true);
		this.commit();
		this.updateFirstState();
		App.Ajax.send(oParameters, this.onAccountSieveFiltersUpdateResponse, this);
	}
};

CAccountFiltersViewModel.prototype.onSaveClick = function ()
{
	if (this.oEditedAccount)
	{
		this.saveData(this.prepareParameters());
	}
};

CAccountFiltersViewModel.prototype.getFilters = function()
{
	if (this.oEditedAccount)
	{
		if (this.oEditedAccount.filters() !== null)
		{
			this.loading(false);
			this.collection(this.oEditedAccount.filters().collection());
			this.updateFirstState();
		}
		else
		{
			var
				oParameters = {
					'Action': 'AccountSieveFiltersGet',
					'AccountID': this.oEditedAccount.id()
				}
			;

			this.loading(true);
			this.collection([]);
			App.Ajax.send(oParameters, this.onAccountSieveFiltersGetResponse, this);
		}
	}
};

/**
 * @param {Object} oFilterToDelete
 */
CAccountFiltersViewModel.prototype.deleteFilter = function (oFilterToDelete)
{
	this.collection.remove(oFilterToDelete);
};

CAccountFiltersViewModel.prototype.addFilter = function ()
{
	if (this.oEditedAccount)
	{
		var oSieveFilter =  new CSieveFilterModel(this.oEditedAccount.id());
		this.collection.push(oSieveFilter);
	}
};

/**
 * @param {string} sPart
 * @param {string} sPrefix
 * 
 * @return {string}
 */
CAccountFiltersViewModel.prototype.displayFilterPart = function (sPart, sPrefix)
{
	var sTemplate = '';
	if (sPart === '%FIELD%')
	{
		sTemplate = 'Field';
	}
	else if (sPart === '%CONDITION%')
	{
		sTemplate = 'Condition';
	}
	else if (sPart === '%STRING%')
	{
		sTemplate = 'String';
	}
	else if (sPart === '%ACTION%')
	{
		sTemplate = 'Action';
	}
	else if (sPart === '%FOLDER%')
	{
		sTemplate = 'Folder';
	}
	else if (sPart.substr(0, 9) === '%DEPENDED')
	{
		sTemplate = 'DependedText';
	}
	else
	{
		sTemplate = 'Text';
	}

	return sPrefix + sTemplate;
};

/**
 * @param {string} sText
 */
CAccountFiltersViewModel.prototype.getDependedText = function (sText)
{	
	sText = Utils.pString(sText);
	
	if (sText)
	{
		sText = sText.replace(/%/g, '').split('=')[1] || '';
	}
	
	return sText;
};

/**
 * @param {string} sText
 * @param {Object} oParent
 */
CAccountFiltersViewModel.prototype.getDependedField = function (sText, oParent)
{	
	sText = Utils.pString(sText);
	
	if (sText)
	{
		sText = sText.replace(/[=](.*)/g, '').split('-')[1] || '';
		sText = sText.toLowerCase();
	}

	return Utils.isUnd(oParent[sText]) ? false : oParent[sText]();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountFiltersViewModel.prototype.onAccountSieveFiltersGetResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result && oResponse.AccountID && this.oEditedAccount)
		{
			var
				oAccount = null,
				oSieveFilters = new CSieveFiltersModel(),
				iAccountId = Utils.pInt(oResponse.AccountID)
			;

			if (iAccountId)
			{
				oAccount = AppData.Accounts.getAccount(iAccountId);
				if (oAccount)
				{
					oSieveFilters.parse(iAccountId, oResponse.Result);
					oAccount.filters(oSieveFilters);

					if (iAccountId === this.oEditedAccount.id())
					{
						this.getFilters();
					}
				}
			}
		}
	}
	else
	{
		App.Api.showError(Utils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountFiltersViewModel.prototype.onAccountSieveFiltersUpdateResponse = function (oResponse, oRequest)
{
	this.saving(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result)
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_FILTERS_SUCCESS_REPORT'));
		}
		else
		{
			App.Api.showError(Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
		}
	}
	else
	{
		App.Api.showError(Utils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};
