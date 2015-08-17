
/**
 * @constructor
 */
function CPgpSettingsViewModel()
{
	this.pgp = null;
	this.pgpLoaded = ko.observable(false);
	
	this.keys = ko.observableArray([]);
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
	
	this.loading = ko.observable(false);
	
	this.enableOpenPgp = ko.observable(AppData.User.enableOpenPgp());
	this.allowAutosaveInDrafts = ko.observable(AppData.User.AllowAutosaveInDrafts);
	this.autosignOutgoingEmails = ko.observable(AppData.User.AutosignOutgoingEmails);
	
	this.bAllowAutoSave = AppData.App.AutoSave;
	
	this.firstState = this.getState();
}

CPgpSettingsViewModel.prototype.TemplateName = 'Settings_PgpSettingsViewModel';

CPgpSettingsViewModel.prototype.TabName = Enums.SettingsTab.Pgp;

CPgpSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_OPENPGP');

CPgpSettingsViewModel.prototype.init = function ()
{
	this.enableOpenPgp(AppData.User.enableOpenPgp());
	this.allowAutosaveInDrafts(AppData.User.AllowAutosaveInDrafts);
	this.autosignOutgoingEmails(AppData.User.AutosignOutgoingEmails);
};

CPgpSettingsViewModel.prototype.getState = function ()
{
	var aState = [
		this.enableOpenPgp(),
		this.allowAutosaveInDrafts(),
		this.autosignOutgoingEmails()
	];
	
	return aState.join(':');
};

CPgpSettingsViewModel.prototype.updateFirstState = function ()
{
	this.firstState = this.getState();
};

CPgpSettingsViewModel.prototype.isChanged = function ()
{
	if (this.firstState && this.getState() !== this.firstState)
	{
		return true;
	}
	else
	{
		return false;
	}
};

CPgpSettingsViewModel.prototype.onRoute = function ()
{
	var fPgpCallback = _.bind(function (oPgp) {
		if (oPgp)
		{
			this.pgp = oPgp;
			this.keys(this.pgp.getKeys());
			this.pgp.getKeysObservable().subscribe(function () {
				this.keys(this.pgp.getKeys());
			}, this);
		}
		this.pgpLoaded(true);
	}, this);
	
	App.Api.pgp(fPgpCallback, AppData.User.IdUser);
};

CPgpSettingsViewModel.prototype.importKey = function ()
{
	if (this.pgp)
	{
		App.Screens.showPopup(CImportOpenPgpKeyPopup, [this.pgp]);
	}
};

CPgpSettingsViewModel.prototype.generateNewKey = function ()
{
	if (this.pgp)
	{
		App.Screens.showPopup(CGenerateOpenPgpKeyPopup, [this.pgp]);
	}
};

/**
 * @param {Object} oKey
 */
CPgpSettingsViewModel.prototype.removeOpenPgpKey = function (oKey)
{
	var
		sConfirm = '',
		fRemove = _.bind(function (bRemove) {
			if (bRemove)
			{
				var oRes = this.pgp.deleteKey(oKey);
				if (!oRes.result)
				{
					App.Api.showError(Utils.i18n('OPENPGP/ERROR_DELETE_KEY'));
				}
			}
		}, this)
	;
	
	if (this.pgp && oKey)
	{
		sConfirm = Utils.i18n('OPENPGP/CONFIRM_DELETE_KEY', {'KEYEMAIL': oKey.getEmail()});
		App.Screens.showPopup(ConfirmPopup, [sConfirm, fRemove]);
	}
};

/**
 * @param {Object} oKey
 */
CPgpSettingsViewModel.prototype.showArmor = function (oKey)
{
	if (this.pgp)
	{
		App.Screens.showPopup(CShowOpenPgpKeyArmorPopup, [oKey]);
	}
};

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CPgpSettingsViewModel.prototype.onResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		AppData.User.updateOpenPgpSettings(oRequest.EnableOpenPgp, oRequest.AllowAutosaveInDrafts, oRequest.AutosignOutgoingEmails);

		App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

/**
 * Sends a request to the server to save the settings.
 */
CPgpSettingsViewModel.prototype.onSaveClick = function ()
{
	var
		oParameters = {
			'Action': 'UserSettingsUpdate',
			'EnableOpenPgp': this.enableOpenPgp() ? '1' : '0',
			'AllowAutosaveInDrafts': this.allowAutosaveInDrafts() ? '1' : '0',
			'AutosignOutgoingEmails': this.autosignOutgoingEmails() ? '1' : '0'
		}
	;

	this.loading(true);
	this.updateFirstState();
	App.Ajax.send(oParameters, this.onResponse, this);
};
