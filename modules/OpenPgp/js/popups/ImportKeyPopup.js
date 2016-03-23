'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
	Screens = require('modules/Core/js/Screens.js'),
	
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js'),
	
	ErrorsUtils = require('modules/OpenPgp/js/utils/Errors.js'),
	
	Enums = require('modules/OpenPgp/js/Enums.js'),
	OpenPgp = require('modules/OpenPgp/js/OpenPgp.js')
;

/**
 * @constructor
 */
function CImportKeyPopup()
{
	CAbstractPopup.call(this);
	
	this.keyArmor = ko.observable('');
	this.keyArmorFocused = ko.observable(false);
	this.keys = ko.observableArray([]);
	this.hasExistingKeys = ko.observable(false);
	this.headlineText = ko.computed(function () {
		return TextUtils.i18n('OPENPGP/INFO_TEXT_INCLUDES_KEYS_PLURAL', {}, null, this.keys().length);
	}, this);
}

_.extendOwn(CImportKeyPopup.prototype, CAbstractPopup.prototype);

CImportKeyPopup.prototype.PopupTemplate = 'OpenPgp_ImportKeyPopup';

/**
 * @param {string} sArmor
 */
CImportKeyPopup.prototype.onShow = function (sArmor)
{
	this.keyArmor(sArmor || '');
	this.keyArmorFocused(true);
	this.keys([]);
	this.hasExistingKeys(false);
	
	if (this.keyArmor() !== '')
	{
		this.checkArmor();
	}
};

CImportKeyPopup.prototype.checkArmor = function ()
{
	var
		aRes = null,
		aKeys = [],
		bHasExistingKeys = false
	;
	
	if (this.keyArmor() === '')
	{
		this.keyArmorFocused(true);
	}
	else
	{
		aRes = OpenPgp.getArmorInfo(this.keyArmor());
		
		if (Types.isNonEmptyArray(aRes))
		{
			_.each(aRes, function (oKey) {
				if (oKey)
				{
					var
						oSameKey = OpenPgp.findKeyByID(oKey.getId(), oKey.isPublic()),
						bHasSameKey = (oSameKey !== null),
						sAddInfoLangKey = oKey.isPublic() ? 'OPENPGP/INFO_PUBLIC_KEY_LENGTH' : 'OPENPGP/INFO_PRIVATE_KEY_LENGTH'
					;
					bHasExistingKeys = bHasExistingKeys || bHasSameKey;
					aKeys.push({
						'armor': oKey.getArmor(),
						'email': oKey.user,
						'id': oKey.getId(),
						'addInfo': TextUtils.i18n(sAddInfoLangKey, {'LENGTH': oKey.getBitSize()}),
						'needToImport': ko.observable(!bHasSameKey),
						'disabled': bHasSameKey
					});
				}
			});
		}
		
		if (aKeys.length === 0)
		{
			Screens.showError(TextUtils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_FOUND'));
		}
		
		this.keys(aKeys);
		this.hasExistingKeys(bHasExistingKeys);
	}
};

CImportKeyPopup.prototype.importKey = function ()
{
	var
		oRes = null,
		aArmors = []
	;
	
	_.each(this.keys(), function (oSimpleKey) {
		if (oSimpleKey.needToImport())
		{
			aArmors.push(oSimpleKey.armor);
		}
	});

	if (aArmors.length > 0)
	{
		oRes = OpenPgp.importKeys(aArmors.join(''));

		if (oRes && oRes.result)
		{
			Screens.showReport(TextUtils.i18n('OPENPGP/REPORT_KEY_SUCCESSFULLY_IMPORTED_PLURAL', {}, null, aArmors.length));
		}

		if (oRes && !oRes.result)
		{
			ErrorsUtils.showPgpErrorByCode(oRes, Enums.PgpAction.Import, TextUtils.i18n('OPENPGP/ERROR_IMPORT_KEY'));
		}

		this.closePopup();
	}
	else
	{
		Screens.showError(TextUtils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_SELECTED'));
	}
};

module.exports = new CImportKeyPopup();
