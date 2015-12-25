'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	Screens = require('core/js/Screens.js'),
	UserSettings = require('core/js/Settings.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
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
function OpenPgpSettingsTabView()
{
	CAbstractSettingsTabView.call(this);
	
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

_.extendOwn(OpenPgpSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

OpenPgpSettingsTabView.prototype.ViewTemplate = 'OpenPgp_OpenPgpSettingsTabView';

OpenPgpSettingsTabView.prototype.importKey = function ()
{
	Popups.showPopup(ImportKeyPopup);
};

OpenPgpSettingsTabView.prototype.generateNewKey = function ()
{
	Popups.showPopup(GenerateKeyPopup);
};

/**
 * @param {Object} oKey
 */
OpenPgpSettingsTabView.prototype.removeOpenPgpKey = function (oKey)
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
OpenPgpSettingsTabView.prototype.showArmor = function (oKey)
{
	Popups.showPopup(ShowKeyArmorPopup, [oKey]);
};

OpenPgpSettingsTabView.prototype.getCurrentValues = function ()
{
	return [
		this.enableOpenPgp(),
		this.allowAutosaveInDrafts()
	];
};

OpenPgpSettingsTabView.prototype.revertGlobalValues = function ()
{
	this.enableOpenPgp(Settings.enableOpenPgp());
	this.allowAutosaveInDrafts(UserSettings.AllowAutosaveInDrafts);
};

OpenPgpSettingsTabView.prototype.getParametersForSave = function ()
{
	return {
		'EnableOpenPgp': this.enableOpenPgp(),
		'AllowAutosaveInDrafts': this.allowAutosaveInDrafts()
	};
};

OpenPgpSettingsTabView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.EnableOpenPgp, oParameters.AllowAutosaveInDrafts);
};

module.exports = new OpenPgpSettingsTabView();
