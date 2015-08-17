/**
 * @constructor
 */
function CShowOpenPgpKeyArmorPopup()
{
	this.allowSendEmails = ko.computed(function () {
		return AppData.App.AllowWebMail && AppData.Accounts.isCurrentAllowsMail();
	}, this);
	
	this.armor = ko.observable('');
	this.htmlArmor = ko.computed(function () {
		return Utils.encodeHtml(this.armor().replace(/\r/g, ''));
	}, this);
	this.user = ko.observable('');
	this.private = ko.observable(false);
	this.titleText = ko.computed(function () {
		return this.private() ?
			Utils.i18n('OPENPGP/POPUP_TITLE_VIEW_PRIVATE_KEY', {'USER': this.user()}) :
			Utils.i18n('OPENPGP/POPUP_TITLE_VIEW_PUBLIC_KEY', {'USER': this.user()});
	}, this);
	
	this.downloadLinkHref = ko.computed(function() {
		var
			sHref = '#',
			oBlob = null
		;
		window.URL = window.webkitURL || window.URL;
		if (Blob && window.URL && Utils.isFunc(window.URL.createObjectURL))
		{
			oBlob = new Blob([this.armor()], {type: 'text/plain'});
			sHref = window.URL.createObjectURL(oBlob);
		}
		return sHref;
	}, this);
	
	this.downloadLinkFilename = ko.computed(function () {
		var
			sConvertedUser = this.user().replace(/</g, '').replace(/>/g, ''),
			sLangKey = this.private() ? 'OPENPGP/PRIVATE_KEY_FILENAME' : 'OPENPGP/PUBLIC_KEY_FILENAME'
		;
		return Utils.i18n(sLangKey, {'USER': sConvertedUser}) + '.asc';
	}, this);
	
	this.domKey = ko.observable(null);
}

/**
 * @param {Object} oKey
 */
CShowOpenPgpKeyArmorPopup.prototype.onShow = function (oKey)
{
	this.armor(oKey.getArmor());
	this.user(oKey.getUser());
	this.private(oKey.isPrivate());
};

/**
 * @return {string}
 */
CShowOpenPgpKeyArmorPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ShowOpenPgpKeyArmorPopupViewModel';
};

CShowOpenPgpKeyArmorPopup.prototype.send = function ()
{
	if (this.armor() !== '' && this.downloadLinkFilename() !== '')
	{
		App.Api.composeMessageWithPgpKey(this.armor(), this.downloadLinkFilename());
		this.closeCommand();
	}
};

CShowOpenPgpKeyArmorPopup.prototype.select = function ()
{
	var
		oDomKey = (this.domKey() && this.domKey().length === 1) ? this.domKey()[0] : null,
		oSel = null,
		oRange = null
	;
	
	if (oDomKey && window.getSelection && document.createRange)
	{
		oRange = document.createRange();
		oRange.setStart(oDomKey, 0);
		oRange.setEnd(oDomKey, 1);
		oSel = window.getSelection();
		oSel.removeAllRanges();
		oSel.addRange(oRange);
	}
};