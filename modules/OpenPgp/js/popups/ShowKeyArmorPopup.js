'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CShowKeyArmorPopup()
{
	CAbstractPopup.call(this);
	
	this.allowSendEmails = true;
	
	this.armor = ko.observable('');
	this.htmlArmor = ko.computed(function () {
		return TextUtils.encodeHtml(this.armor().replace(/\r/g, ''));
	}, this);
	this.user = ko.observable('');
	this.private = ko.observable(false);
	this.titleText = ko.computed(function () {
		return this.private() ?
			TextUtils.i18n('OPENPGP/HEADING_VIEW_PRIVATE_KEY', {'USER': this.user()}) :
			TextUtils.i18n('OPENPGP/HEADING_VIEW_PUBLIC_KEY', {'USER': this.user()});
	}, this);
	
	this.downloadLinkHref = ko.computed(function() {
		var
			sHref = '#',
			oBlob = null
		;
		
		if (Blob && window.URL && $.isFunction(window.URL.createObjectURL))
		{
			oBlob = new Blob([this.armor()], {type: 'text/plain'});
			sHref = window.URL.createObjectURL(oBlob);
		}
		
		return sHref;
	}, this);
	
	this.downloadLinkFilename = ko.computed(function () {
		var
			sConvertedUser = this.user().replace(/</g, '').replace(/>/g, ''),
			sLangKey = this.private() ? 'OPENPGP/TEXT_PRIVATE_KEY_FILENAME' : 'OPENPGP/TEXT_PUBLIC_KEY_FILENAME'
		;
		return TextUtils.i18n(sLangKey, {'USER': sConvertedUser}) + '.asc';
	}, this);
	
	this.domKey = ko.observable(null);
}

_.extendOwn(CShowKeyArmorPopup.prototype, CAbstractPopup.prototype);

CShowKeyArmorPopup.prototype.PopupTemplate = 'OpenPgp_ShowKeyArmorPopup';

/**
 * @param {Object} oKey
 */
CShowKeyArmorPopup.prototype.onShow = function (oKey)
{
	this.armor(oKey.getArmor());
	this.user(oKey.getUser());
	this.private(oKey.isPrivate());
};

CShowKeyArmorPopup.prototype.send = function ()
{
//	if (this.armor() !== '' && this.downloadLinkFilename() !== '')
//	{
//		App.Api.composeMessageWithPgpKey(this.armor(), this.downloadLinkFilename());
//		this.closePopup();
//	}
};

CShowKeyArmorPopup.prototype.select = function ()
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

module.exports = new CShowKeyArmorPopup();
