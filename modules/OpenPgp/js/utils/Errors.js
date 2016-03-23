'use strict';

var
	_ = require('underscore'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	Screens = require('modules/Core/js/Screens.js'),
	
	Enums = require('modules/OpenPgp/js/Enums.js'),
	
	ErrorsUtils = {}
;
/**
 * @param {Object} oRes
 * @param {string} sPgpAction
 * @param {string=} sDefaultError
 */
ErrorsUtils.showPgpErrorByCode = function (oRes, sPgpAction, sDefaultError)
{
	var
		aErrors = _.isArray(oRes.errors) ? oRes.errors : [],
		aNotices = _.isArray(oRes.notices) ? oRes.notices : [],
		aEmailsWithoutPublicKey = [],
		aEmailsWithoutPrivateKey = [],
		sError = '',
		bNoSignDataNotice = false,
		bNotice = true
	;
	
	_.each(_.union(aErrors, aNotices), function (aError) {
		if (aError.length === 2)
		{
			switch(aError[0])
			{
				case Enums.OpenPgpErrors.GenerateKeyError:
					sError = TextUtils.i18n('OPENPGP/ERROR_GENERATE_KEY');
					break;
				case Enums.OpenPgpErrors.ImportKeyError:
					sError = TextUtils.i18n('OPENPGP/ERROR_IMPORT_KEY');
					break;
				case Enums.OpenPgpErrors.ImportNoKeysFoundError:
					sError = TextUtils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_FOUND');
					break;
				case Enums.OpenPgpErrors.PrivateKeyNotFoundError:
				case Enums.OpenPgpErrors.PrivateKeyNotFoundNotice:
					aEmailsWithoutPrivateKey.push(aError[1]);
					break;
				case Enums.OpenPgpErrors.PublicKeyNotFoundError:
					bNotice = false;
					aEmailsWithoutPublicKey.push(aError[1]);
					break;
				case Enums.OpenPgpErrors.PublicKeyNotFoundNotice:
					aEmailsWithoutPublicKey.push(aError[1]);
					break;
				case Enums.OpenPgpErrors.KeyIsNotDecodedError:
					if (sPgpAction === Enums.PgpAction.DecryptVerify)
					{
						sError = TextUtils.i18n('OPENPGP/ERROR_DECRYPT') + ' ' + TextUtils.i18n('OPENPGP/ERROR_KEY_NOT_DECODED', {'USER': aError[1]});
					}
					else if (sPgpAction === Enums.PgpAction.Sign || sPgpAction === Enums.PgpAction.EncryptSign)
					{
						sError = TextUtils.i18n('OPENPGP/ERROR_SIGN') + ' ' + TextUtils.i18n('OPENPGP/ERROR_KEY_NOT_DECODED', {'USER': aError[1]});
					}
					break;
				case Enums.OpenPgpErrors.SignError:
					sError = TextUtils.i18n('OPENPGP/ERROR_SIGN');
					break;
				case Enums.OpenPgpErrors.VerifyError:
					sError = TextUtils.i18n('OPENPGP/ERROR_VERIFY');
					break;
				case Enums.OpenPgpErrors.EncryptError:
					sError = TextUtils.i18n('OPENPGP/ERROR_ENCRYPT');
					break;
				case Enums.OpenPgpErrors.DecryptError:
					sError = TextUtils.i18n('OPENPGP/ERROR_DECRYPT');
					break;
				case Enums.OpenPgpErrors.SignAndEncryptError:
					sError = TextUtils.i18n('OPENPGP/ERROR_ENCRYPT_OR_SIGN');
					break;
				case Enums.OpenPgpErrors.VerifyAndDecryptError:
					sError = TextUtils.i18n('OPENPGP/ERROR_DECRYPT_OR_VERIFY');
					break;
				case Enums.OpenPgpErrors.DeleteError:
					sError = TextUtils.i18n('OPENPGP/ERROR_DELETE_KEY');
					break;
				case Enums.OpenPgpErrors.VerifyErrorNotice:
					sError = TextUtils.i18n('OPENPGP/ERROR_VERIFY');
					break;
				case Enums.OpenPgpErrors.NoSignDataNotice:
					bNoSignDataNotice = true;
					break;
			}
		}
	});
	
	if (aEmailsWithoutPublicKey.length > 0)
	{
		aEmailsWithoutPublicKey = _.without(aEmailsWithoutPublicKey, '');
		if (aEmailsWithoutPublicKey.length > 0)
		{
			sError = TextUtils.i18n('OPENPGP/ERROR_NO_PUBLIC_KEYS_FOR_USERS_PLURAL', 
					{'USERS': aEmailsWithoutPublicKey.join(', ')}, null, aEmailsWithoutPublicKey.length);
		}
		else if (sPgpAction === Enums.PgpAction.Verify)
		{
			sError = TextUtils.i18n('OPENPGP/ERROR_NO_PUBLIC_KEY_FOUND_FOR_VERIFY');
		}
		if (bNotice && sError !== '')
		{
			sError += ' ' + TextUtils.i18n('OPENPGP/ERROR_MESSAGE_WAS_NOT_VERIFIED');
		}
	}
	else if (aEmailsWithoutPrivateKey.length > 0)
	{
		aEmailsWithoutPrivateKey = _.without(aEmailsWithoutPrivateKey, '');
		if (aEmailsWithoutPrivateKey.length > 0)
		{
			sError = TextUtils.i18n('OPENPGP/ERROR_NO_PRIVATE_KEYS_FOR_USERS_PLURAL', 
					{'USERS': aEmailsWithoutPrivateKey.join(', ')}, null, aEmailsWithoutPrivateKey.length);
		}
		else if (sPgpAction === Enums.PgpAction.DecryptVerify)
		{
			sError = TextUtils.i18n('OPENPGP/ERROR_NO_PRIVATE_KEY_FOUND_FOR_DECRYPT');
		}
	}
	
	if (sError === '' && !bNoSignDataNotice)
	{
		switch (sPgpAction)
		{
			case Enums.PgpAction.Generate:
				sError = TextUtils.i18n('OPENPGP/ERROR_GENERATE_KEY');
				break;
			case Enums.PgpAction.Import:
				sError = TextUtils.i18n('OPENPGP/ERROR_IMPORT_KEY');
				break;
			case Enums.PgpAction.DecryptVerify:
				sError = TextUtils.i18n('OPENPGP/ERROR_DECRYPT');
				break;
			case Enums.PgpAction.Verify:
				sError = TextUtils.i18n('OPENPGP/ERROR_VERIFY');
				break;
			case Enums.PgpAction.Encrypt:
				sError = TextUtils.i18n('OPENPGP/ERROR_ENCRYPT');
				break;
			case Enums.PgpAction.EncryptSign:
				sError = TextUtils.i18n('OPENPGP/ERROR_ENCRYPT_OR_SIGN');
				break;
			case Enums.PgpAction.Sign:
				sError = TextUtils.i18n('OPENPGP/ERROR_SIGN');
				break;
		}
		sError = sDefaultError;
	}
	
	if (sError !== '')
	{
		Screens.showError(sError);
	}
	
	return bNoSignDataNotice;
};

module.exports = ErrorsUtils;
