/**
 * @constructor
 */
function CHelpdeskSettingsViewModel()
{
	this.name = ko.observable(AppData.User.Name);
	this.language = ko.observable(AppData.User.DefaultLanguage);
	this.timeFormat = ko.observable(AppData.User.defaultTimeFormat());
	this.aDateFormats = Utils.getDateFormatsForSelector();
	this.dateFormat = ko.observable(AppData.User.DefaultDateFormat);
	this.hasPassword = ko.observable(AppData.User.HasPassword);
}

CHelpdeskSettingsViewModel.prototype.onShow = function ()
{
	this.name(AppData.User.Name);
	this.language(AppData.User.DefaultLanguage);
	this.timeFormat(AppData.User.defaultTimeFormat());
	this.dateFormat(AppData.User.DefaultDateFormat);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskSettingsViewModel.prototype.onResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		if (oRequest.Language !== AppData.User.DefaultLanguage)
		{
			window.location.reload();
		}
		else
		{
			AppData.User.updateSettings(oRequest.Name, oRequest.Language,
				oRequest.TimeFormat, oRequest.DateFormat);

			App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
		}
	}
};

CHelpdeskSettingsViewModel.prototype.save = function ()
{
	var
		oParameters = {
			'Action': 'HelpdeskSettingsUpdate',
			'Name': this.name(),
			'Language': this.language(),
			'TimeFormat': this.timeFormat(),
			'DateFormat': this.dateFormat()
		}
	;
	
	App.Ajax.sendExt(oParameters, this.onResponse, this);
};

CHelpdeskSettingsViewModel.prototype.backToHelpdesk = function ()
{
	App.Routing.setHash([Enums.Screens.Helpdesk]);
};

CHelpdeskSettingsViewModel.prototype.onChangePasswordClick = function ()
{
	App.Screens.showPopup(ChangePasswordPopup, [true, true]);
};

