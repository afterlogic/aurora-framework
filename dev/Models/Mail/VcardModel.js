
/**
 * @constructor
 */
function CVcardModel()
{
	this.uid = ko.observable('');
	this.file = ko.observable('');
	this.name = ko.observable('');
	this.email = ko.observable('');
	this.exists = ko.observable(false);
	this.isJustSaved = ko.observable(false);
}

/**
 * @param {AjaxVCardResponse} oData
 */
CVcardModel.prototype.parse = function (oData)
{
	if (oData && oData['@Object'] === 'Object/CApiMailVcard')
	{
		this.uid(Utils.pString(oData.Uid));
		this.file(Utils.pString(oData.File));
		this.name(Utils.pString(oData.Name));
		this.email(Utils.pString(oData.Email));
		this.exists(!!oData.Exists);
		
		App.ContactsCache.addVcard(this);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CVcardModel.prototype.onContactsSaveVcfResponse = function (oData, oParameters)
{
	if (oData && oData.Result && oData.Result.Uid)
	{
		this.uid(oData.Result.Uid);
	}
};

CVcardModel.prototype.addContact = function ()
{
	var
		oParameters = {
			'Action': 'ContactsSaveVcf',
			'File': this.file()
		}
	;
	
	App.Ajax.send(oParameters, this.onContactsSaveVcfResponse, this);
	
	this.isJustSaved(true);
	this.exists(true);
	
	setTimeout(_.bind(function () {
		this.isJustSaved(false);
	}, this), 20000);
	
	App.ContactsCache.recivedAnim(true);
	
	if (AppData.SingleMode && window.opener)
	{
		window.opener.App.ContactsCache.markVcardExistentByFile(this.file());
	}
	else
	{
		App.ContactsCache.markVcardExistentByFile(this.file());
	}
};
