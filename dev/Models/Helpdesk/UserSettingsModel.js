
/**
 * @constructor
 */
function CUserSettingsModel()
{
	this.Name = '';
	this.Email = '';
	this.DefaultLanguage = 'English';
	this.DefaultDateFormat = 'MM/DD/YYYY';
	this.defaultTimeFormat = ko.observable(Enums.TimeFormat.F24);
	this.IsHelpdeskAgent = true;
	this.HelpdeskIframeUrl = '';
	this.enableOpenPgp = ko.observable(false);
	this.helpdeskSignature = ko.observable('');
	this.helpdeskSignatureEnable = ko.observable(false);
	this.HasPassword = false;
}

/**
 * @param {Object} oData
 */
CUserSettingsModel.prototype.parse = function (oData)
{
	if (oData !== null)
	{
		this.Name = Utils.pString(oData.Name);
		this.Email = Utils.pString(oData.Email);

		if (oData.Language)
		{
			this.DefaultLanguage = Utils.pString(oData.Language);
		}

		if (oData.DateFormat)
		{
			this.DefaultDateFormat = Utils.pString(oData.DateFormat);
		}
		
		this.defaultTimeFormat(Utils.pString(oData.TimeFormat));
		this.IsHelpdeskAgent = !!oData.IsHelpdeskAgent;
		this.HelpdeskIframeUrl = Utils.pString(oData.HelpdeskIframeUrl);
		this.HasPassword = oData.HasPassword;
	}
};

/**
 * @param {string} sName
 * @param {string} sLanguage
 * @param {string} sTimeFormat
 * @param {string} sDateFormat
 */
CUserSettingsModel.prototype.updateSettings = function (sName, sLanguage, sTimeFormat, sDateFormat)
{
	this.Name = sName;
	this.DefaultLanguage = sLanguage;
	this.DefaultDateFormat = sDateFormat;
	this.defaultTimeFormat(sTimeFormat);
};