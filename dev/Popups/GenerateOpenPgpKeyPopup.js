/**
 * @constructor
 */
function CGenerateOpenPgpKeyPopup()
{
	this.pgp = null;
	this.emails = ko.observableArray([]);
	this.selectedEmail = ko.observable('');
	this.password = ko.observable('');
	this.keyLengthOptions = [1024, 2048];
	this.selectedKeyLength = ko.observable(1024);
	this.process = ko.observable(false);
}

/**
 * @param {Object} oPgp
 */
CGenerateOpenPgpKeyPopup.prototype.onShow = function (oPgp)
{
	this.pgp = oPgp;
	this.emails(AppData.Accounts.getAllFullEmails());
	this.selectedEmail('');
	this.password('');
	this.selectedKeyLength(2048);
	this.process(false);
};

/**
 * @return {string}
 */
CGenerateOpenPgpKeyPopup.prototype.popupTemplate = function ()
{
	return 'Popups_GenerateOpenPgpKeyPopupViewModel';
};

CGenerateOpenPgpKeyPopup.prototype.generate = function ()
{
	if (this.pgp)
	{
		this.process(true);
		_.delay(_.bind(function () {
			var oRes = this.pgp.generateKey(this.selectedEmail(), this.password(), this.selectedKeyLength());
			
			if (oRes && oRes.result)
			{
				App.Api.showReport(Utils.i18n('OPENPGP/REPORT_KEY_SUCCESSFULLY_GENERATED'));
			}
			
			if (oRes && !oRes.result)
			{
				this.process(false);
				App.Api.showPgpErrorByCode(oRes, Enums.PgpAction.Generate);
			}
			else
			{
				this.closeCommand();
			}
		}, this), 50);
	}
};
