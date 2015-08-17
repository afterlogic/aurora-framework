
/**
 * @constructor
 */
function CHelpdeskSettingsViewModel()
{
	this.allowNotifications = ko.observable(AppData.User.AllowHelpdeskNotifications);

	this.loading = ko.observable(false);

	this.signature = ko.observable(AppData.User.helpdeskSignature());
	this.signatureEnable = ko.observable(AppData.User.helpdeskSignatureEnable());
	this.signatureEnable.subscribe(function (iEnable) {
		if (iEnable !== '1') {
			this.signatureFocused(false);
		}
	}, this);
	this.signatureFocused = ko.observable(false);
	this.domSignature = ko.observable(null);
}

CHelpdeskSettingsViewModel.prototype.TemplateName = 'Settings_HelpdeskSettingsViewModel';

CHelpdeskSettingsViewModel.prototype.TabName = Enums.SettingsTab.Helpdesk;

CHelpdeskSettingsViewModel.prototype.TabTitle = Utils.i18n('SETTINGS/TAB_HELPDESK');

CHelpdeskSettingsViewModel.prototype.onShow = function ()
{
	this.allowNotifications(AppData.User.AllowHelpdeskNotifications);
};

CHelpdeskSettingsViewModel.prototype.onApplyBindings = function ($viewModel)
{
	$(this.domSignature()).on('click', function() {
		this.signatureFocused(true);
	}.bind(this));
};

/**
 * Sends a request to the server to save the settings.
 */
CHelpdeskSettingsViewModel.prototype.onSaveClick = function ()
{
	var
		oParameters = {
			'Action': 'HelpdeskUserSettingsUpdate',
			'AllowHelpdeskNotifications': this.allowNotifications() ? '1' : '0',
			'HelpdeskSignature': this.signature(),
			'HelpdeskSignatureEnable': this.signatureEnable()
		}
	;

	this.loading(true);
	App.Ajax.send(oParameters, this.onUpdateResponse, this);
};

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskSettingsViewModel.prototype.onUpdateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		AppData.User.updateHelpdeskSettings(this.allowNotifications(), this.signature(), this.signatureEnable());
		App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};