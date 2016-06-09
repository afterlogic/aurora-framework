'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Screens = require('modules/Core/js/Screens.js'),
	UserSettings = require('modules/Core/js/Settings.js'),
	
	CAbstractSettingsFormView = ModulesManager.run('SettingsClient', 'getAbstractSettingsFormViewClass'),
	
	Popups = require('modules/Core/js/Popups.js'),
	ConfirmPopup = require('modules/Core/js/popups/ConfirmPopup.js'),
	GenerateKeyPopup = require('modules/%ModuleName%/js/popups/GenerateKeyPopup.js'),
	ImportKeyPopup = require('modules/%ModuleName%/js/popups/ImportKeyPopup.js'),
	ShowKeyArmorPopup = require('modules/%ModuleName%/js/popups/ShowKeyArmorPopup.js'),
	
	OpenPgp = require('modules/%ModuleName%/js/OpenPgp.js'),
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function COpenPgpSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, Settings.ServerModuleName);
	
	this.bAllowAutoSave = UserSettings.AutoSave;
	
	this.enableOpenPgp = ko.observable(Settings.enableOpenPgp());
	this.allowAutosaveInDrafts = ko.observable(UserSettings.AllowAutosaveInDrafts);
	
	this.keys = ko.observableArray(OpenPgp.getKeys());
	OpenPgp.getKeysObservable().subscribe(function () {
		this.keys(OpenPgp.getKeys());
	}, this);
	
	this.publicKeys = ko.computed(function () {
		var
			aPublicKeys = _.filter(this.keys(), function (oKey) {
				return oKey.isPublic();
			})
		;
		return _.map(aPublicKeys, function (oKey) {
			return {'user': oKey.getUser(), 'armor': oKey.getArmor(), 'key': oKey, 'private': false};
		});
	}, this);
	this.privateKeys = ko.computed(function () {
		var
			aPrivateKeys = _.filter(this.keys(), function (oKey) {
				return oKey.isPrivate();
			})
		;
		return  _.map(aPrivateKeys, function (oKey) {
			return {'user': oKey.getUser(), 'armor': oKey.getArmor(), 'key': oKey};
		});
	}, this);
}

_.extendOwn(COpenPgpSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

COpenPgpSettingsPaneView.prototype.ViewTemplate = '%ModuleName%_OpenPgpSettingsPaneView';

COpenPgpSettingsPaneView.prototype.importKey = function ()
{
	Popups.showPopup(ImportKeyPopup);
};

COpenPgpSettingsPaneView.prototype.generateNewKey = function ()
{
	Popups.showPopup(GenerateKeyPopup);
};

/**
 * @param {Object} oKey
 */
COpenPgpSettingsPaneView.prototype.removeOpenPgpKey = function (oKey)
{
	var
		sConfirm = '',
		fRemove = _.bind(function (bRemove) {
			if (bRemove)
			{
				var oRes = OpenPgp.deleteKey(oKey);
				if (!oRes.result)
				{
					Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_DELETE_KEY'));
				}
			}
		}, this)
	;
	
	if (oKey)
	{
		sConfirm = TextUtils.i18n('%MODULENAME%/CONFIRM_DELETE_KEY', {'KEYEMAIL': oKey.getEmail()});
		Popups.showPopup(ConfirmPopup, [sConfirm, fRemove]);
	}
};

/**
 * @param {Object} oKey
 */
COpenPgpSettingsPaneView.prototype.showArmor = function (oKey)
{
	Popups.showPopup(ShowKeyArmorPopup, [oKey]);
};

COpenPgpSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.enableOpenPgp(),
		this.allowAutosaveInDrafts()
	];
};

COpenPgpSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.enableOpenPgp(Settings.enableOpenPgp());
	this.allowAutosaveInDrafts(UserSettings.AllowAutosaveInDrafts);
};

COpenPgpSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'EnableOpenPgp': this.enableOpenPgp(),
		'AllowAutosaveInDrafts': this.allowAutosaveInDrafts()
	};
};

COpenPgpSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.EnableOpenPgp, oParameters.AllowAutosaveInDrafts);
};

module.exports = new COpenPgpSettingsPaneView();
