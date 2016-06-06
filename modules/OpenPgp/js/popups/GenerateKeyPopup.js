'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Screens = require('modules/Core/js/Screens.js'),
	
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js'),
	
	ErrorsUtils = require('modules/%ModuleName%/js/utils/Errors.js'),
	
	Enums = require('modules/%ModuleName%/js/Enums.js'),
	OpenPgp = require('modules/%ModuleName%/js/OpenPgp.js')
;

/**
 * @constructor
 */
function CGenerateKeyPopup()
{
	CAbstractPopup.call(this);
	
	this.emails = ko.observableArray([]);
	this.selectedEmail = ko.observable('');
	this.password = ko.observable('');
	this.keyLengthOptions = [1024, 2048];
	this.selectedKeyLength = ko.observable(1024);
	this.process = ko.observable(false);
}

_.extendOwn(CGenerateKeyPopup.prototype, CAbstractPopup.prototype);

CGenerateKeyPopup.prototype.PopupTemplate = 'OpenPgp_GenerateKeyPopup';

CGenerateKeyPopup.prototype.onShow = function ()
{
	this.emails(ModulesManager.run('Mail', 'getAllAccountsFullEmails'));
	this.selectedEmail('');
	this.password('');
	this.selectedKeyLength(2048);
	this.process(false);
};

CGenerateKeyPopup.prototype.generate = function ()
{
	this.process(true);
	_.delay(_.bind(function () {
		var oRes = OpenPgp.generateKey(this.selectedEmail(), this.password(), this.selectedKeyLength());

		if (oRes && oRes.result)
		{
			Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_KEY_SUCCESSFULLY_GENERATED'));
		}

		if (oRes && !oRes.result)
		{
			this.process(false);
			ErrorsUtils.showPgpErrorByCode(oRes, Enums.PgpAction.Generate);
		}
		else
		{
			this.closePopup();
		}
	}, this), 50);
};

module.exports = new CGenerateKeyPopup();
