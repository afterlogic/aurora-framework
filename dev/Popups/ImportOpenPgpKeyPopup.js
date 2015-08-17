/**
 * @constructor
 */
function CImportOpenPgpKeyPopup()
{
	this.pgp = null;
	this.keyArmor = ko.observable('');
	this.keyArmorFocused = ko.observable(false);
	this.keys = ko.observableArray([]);
	this.hasExistingKeys = ko.observable(false);
	this.headlineText = ko.computed(function () {
		return Utils.i18n('OPENPGP/INFO_TEXT_INCLUDES_KEYS_PLURAL', {}, null, this.keys().length);
	}, this);
}

/**
 * @param {Object} oPgp
 */
CImportOpenPgpKeyPopup.prototype.onShow = function (oPgp, sArmor)
{
	this.pgp = oPgp;
	this.keyArmor(sArmor || '');
	this.keyArmorFocused(true);
	this.keys([]);
	this.hasExistingKeys(false);
	if (this.keyArmor() !== '')
	{
		this.checkArmor();
	}
};

/**
 * @return {string}
 */
CImportOpenPgpKeyPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ImportOpenPgpKeyPopupViewModel';
};

CImportOpenPgpKeyPopup.prototype.checkArmor = function ()
{
	var
		aRes = null,
		aKeys = [],
		oPgp = this.pgp,
		bHasExistingKeys = false
	;
	
	if (this.keyArmor() === '')
	{
		this.keyArmorFocused(true);
	}
	else if (oPgp)
	{
		aRes = oPgp.getArmorInfo(this.keyArmor());
		
		if (Utils.isNonEmptyArray(aRes))
		{
			_.each(aRes, function (oKey) {
				if (oKey)
				{
					var
						oSameKey = oPgp.findKeyByID(oKey.getId(), oKey.isPublic()),
						bHasSameKey = (oSameKey !== null),
						sAddInfoLangKey = oKey.isPublic() ? 'OPENPGP/PUBLIC_KEY_ADD_INFO' : 'OPENPGP/PRIVATE_KEY_ADD_INFO'
					;
					bHasExistingKeys = bHasExistingKeys || bHasSameKey;
					aKeys.push({
						'armor': oKey.getArmor(),
						'email': oKey.user,
						'id': oKey.getId(),
						'addInfo': Utils.i18n(sAddInfoLangKey, {'LENGTH': oKey.getBitSize()}),
						'needToImport': ko.observable(!bHasSameKey),
						'disabled': bHasSameKey
					});
				}
			});
		}
		
		if (aKeys.length === 0)
		{
			App.Api.showError(Utils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_FOUND'));
		}
		
		this.keys(aKeys);
		this.hasExistingKeys(bHasExistingKeys);
	}
};

CImportOpenPgpKeyPopup.prototype.importKey = function ()
{
	var
		oRes = null,
		aArmors = []
	;
	if (this.pgp)
	{
		
		_.each(this.keys(), function (oSimpleKey) {
			if (oSimpleKey.needToImport())
			{
				aArmors.push(oSimpleKey.armor);
			}
		});
		
		if (aArmors.length > 0)
		{
			oRes = this.pgp.importKeys(aArmors.join(''));

			if (oRes && oRes.result)
			{
				App.Api.showReport(Utils.i18n('OPENPGP/REPORT_KEY_SUCCESSFULLY_IMPORTED_PLURAL', {}, null, aArmors.length));
			}

			if (oRes && !oRes.result)
			{
				App.Api.showPgpErrorByCode(oRes, Enums.PgpAction.Import, Utils.i18n('OPENPGP/ERROR_IMPORT_KEY'));
			}

			this.closeCommand();
		}
		else
		{
			App.Api.showError(Utils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_SELECTED'));
		}
	}
};

