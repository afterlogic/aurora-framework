'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Types = require('core/js/utils/Types.js'),
	
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	AccountList = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	
	СFilterModel = require('modules/Mail/js/models/СFilterModel.js'),
	СFiltersModel = require('modules/Mail/js/models/СFiltersModel.js')
;

/**
 * @constructor
 */
function CAccountFiltersPaneView()
{
	CAbstractSettingsFormView.call(this, 'Mail');
	
	this.bShown = false;
	
	this.foldersOptions = ko.observableArray([]);
	
	MailCache.editedFolderList.subscribe(function () {
		if (this.bShown)
		{
			this.populate();
		}
	}, this);
	
	this.loading = ko.observable(true);
	this.collection = ko.observableArray([]);

	this.fieldOptions = [
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_FIELD_FROM'), 'value': 0},
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_FIELD_TO'), 'value': 1},
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_FIELD_SUBJECT'), 'value': 2}
	];

	this.conditionOptions = [
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_COND_CONTAIN_SUBSTR'), 'value': 0},
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_COND_EQUAL_TO'), 'value': 1},
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_COND_NOT_CONTAIN_SUBSTR'), 'value': 2}
	];

	this.actionOptions = [
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_ACTION_MOVE'), 'value': 3},
		{'text': TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_ACTION_DELETE'), 'value': 1}
	];
	
	this.phaseArray = [''];
	
	_.each(TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_PHRASE').split(/\s/), function (sItem) {
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

_.extendOwn(CAccountFiltersPaneView.prototype, CAbstractSettingsFormView.prototype);

CAccountFiltersPaneView.prototype.ViewTemplate = 'Mail_Settings_AccountFiltersPaneView';

/**
 * @param {Object} oAccount
 */
CAccountFiltersPaneView.prototype.show = function (oAccount)
{
	this.bShown = true;
	this.populate();
};

CAccountFiltersPaneView.prototype.onHide = function ()
{
	this.bShown = false;
};

CAccountFiltersPaneView.prototype.populate = function ()
{
	var
		oFolderList = MailCache.editedFolderList(),
		aOptionList = []
	;

	if (oFolderList.iAccountId === AccountList.editedId())
	{
		aOptionList = oFolderList.getOptions(TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_NOT_SELECTED'), true, true, false, true);
		this.foldersOptions(aOptionList);
		this.populateFilters();
	}
	else
	{
		this.loading(true);
		this.collection([]);
	}
};

CAccountFiltersPaneView.prototype.revert = function ()
{
	_.each(this.collection(), function (oFilter) {
		oFilter.revert();
	});
};

CAccountFiltersPaneView.prototype.commit = function ()
{
	_.each(this.collection(), function (oFilter) {
		oFilter.commit();
	});
};

CAccountFiltersPaneView.prototype.getCurrentValues = function ()
{
	return _.map(this.collection(), function (oFilter) {
		return oFilter.toString();
	}, this);
};

CAccountFiltersPaneView.prototype.getParametersForSave = function ()
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
		})
	;
	
	return {
		'AccountID': AccountList.editedId(),
		'Filters': aFilters
	};
};

CAccountFiltersPaneView.prototype.save = function ()
{
	var bCantSave =_.some(this.collection(), function (oFilter) {
		return oFilter.filter() === '' || (Types.pString(oFilter.action()) === '3' /* Move */ && oFilter.folder() === '');
	});

	if (bCantSave)
	{
		Screens.showError(TextUtils.i18n('SETTINGS/ERROR_FILTERS_FIELDS_FILL'));
	}
	else
	{
		this.isSaving(true);
		this.commit();
		this.updateSavedState();
		Ajax.send('UpdateFilters', this.getParametersForSave(), this.onAccountSieveFiltersUpdateResponse, this);
	}
};

CAccountFiltersPaneView.prototype.populateFilters = function ()
{
	var oAccount = AccountList.getEdited();
	
	if (oAccount)
	{
		if (oAccount.filters() !== null)
		{
			this.loading(false);
			this.collection(oAccount.filters().collection());
			this.updateSavedState();
		}
		else
		{
			this.loading(true);
			this.collection([]);
			Ajax.send('GetFilters', { 'AccountID': oAccount.id() }, this.onGetFiltersResponse, this);
		}
	}
};

/**
 * @param {Object} oFilterToDelete
 */
CAccountFiltersPaneView.prototype.deleteFilter = function (oFilterToDelete)
{
	this.collection.remove(oFilterToDelete);
};

CAccountFiltersPaneView.prototype.addFilter = function ()
{
	var oSieveFilter =  new СFilterModel(AccountList.editedId());
	this.collection.push(oSieveFilter);
};

/**
 * @param {string} sPart
 * @param {string} sPrefix
 * 
 * @return {string}
 */
CAccountFiltersPaneView.prototype.displayFilterPart = function (sPart, sPrefix)
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
CAccountFiltersPaneView.prototype.getDependedText = function (sText)
{	
	sText = Types.pString(sText);
	
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
CAccountFiltersPaneView.prototype.getDependedField = function (sText, oParent)
{	
	sText = Types.pString(sText);
	
	if (sText)
	{
		sText = sText.replace(/[=](.*)/g, '').split('-')[1] || '';
		sText = sText.toLowerCase();
	}

	return oParent[sText] ? oParent[sText]() : false;
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountFiltersPaneView.prototype.onGetFiltersResponse = function (oResponse, oRequest)
{
	var
		oParameters = JSON.parse(oRequest.Parameters),
		iAccountId = Types.pInt(oParameters.AccountID),
		oAccount = AccountList.getAccount(iAccountId),
		oSieveFilters = new СFiltersModel()
	;
	
	this.loading(false);

	if (oResponse && oResponse.Result && oAccount)
	{
		oSieveFilters.parse(iAccountId, oResponse.Result);
		oAccount.filters(oSieveFilters);

		if (iAccountId === AccountList.editedId())
		{
			this.populateFilters();
		}
	}
	else
	{
		Screens.showError(TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAccountFiltersPaneView.prototype.onAccountSieveFiltersUpdateResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oRequest && oRequest.Action)
	{
		if (oResponse && oResponse.Result)
		{
			Screens.showReport(TextUtils.i18n('SETTINGS/ACCOUNT_FILTERS_SUCCESS_REPORT'));
		}
		else
		{
			Screens.showError(TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
		}
	}
	else
	{
		Screens.showError(TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
};

module.exports = new CAccountFiltersPaneView();
