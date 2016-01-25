'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	Screens = require('core/js/Screens.js'),
	UserSettings = require('core/js/Settings.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js'),
	ImportKeyPopup = require('modules/OpenPgp/js/popups/ImportKeyPopup.js'),
	GenerateKeyPopup = require('modules/OpenPgp/js/popups/GenerateKeyPopup.js'),
	ShowKeyArmorPopup = require('modules/OpenPgp/js/popups/ShowKeyArmorPopup.js'),
	
	OpenPgp = require('modules/OpenPgp/js/OpenPgp.js'),
	Settings = require('modules/OpenPgp/js/Settings.js')
;

/**
 * @constructor
 */
function COpenPgpSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'OpenPgp');
	
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

COpenPgpSettingsPaneView.prototype.ViewTemplate = 'OpenPgp_OpenPgpSettingsPaneView';

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
					Screens.showError(TextUtils.i18n('OPENPGP/ERROR_DELETE_KEY'));
				}
			}
		}, this)
	;
	
	if (oKey)
	{
		sConfirm = TextUtils.i18n('OPENPGP/CONFIRM_DELETE_KEY', {'KEYEMAIL': oKey.getEmail()});
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
