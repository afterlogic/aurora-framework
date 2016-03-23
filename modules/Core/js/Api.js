'use strict';

var
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	Screens = require('modules/Core/js/Screens.js'),
			
	Api = {}
;

/**
 * @param {Object} oResponse
 * @param {string=} sDefaultError
 * @param {boolean=} bNotHide = false
 */
Api.showErrorByCode = function (oResponse, sDefaultError, bNotHide)
{
	var
		iErrorCode = oResponse.ErrorCode,
		sResponseError = oResponse.ErrorMessage || '',
		sResultError = ''
	;
	
	switch (iErrorCode)
	{
		default:
			sResultError = sDefaultError || TextUtils.i18n('CORE/ERROR_UNKNOWN');
			break;
		case Enums.Errors.AuthError:
			sResultError = TextUtils.i18n('CORE/ERROR_PASS_INCORRECT');
			break;
		case Enums.Errors.DataBaseError:
			sResultError = TextUtils.i18n('CORE/ERROR_DATABASE');
			break;
		case Enums.Errors.LicenseProblem:
			sResultError = TextUtils.i18n('CORE/ERROR_INVALID_LICENSE');
			break;
		case Enums.Errors.DemoLimitations:
			sResultError = TextUtils.i18n('CORE/INFO_DEMO_THIS_FEATURE_IS_DISABLED');
			break;
		case Enums.Errors.Captcha:
			sResultError = TextUtils.i18n('CORE/ERROR_CAPTCHA_IS_INCORRECT');
			break;
		case Enums.Errors.CanNotGetMessage:
			sResultError = TextUtils.i18n('CORE/ERROR_MESSAGE_DELETED');
			break;
		case Enums.Errors.NoRequestedMailbox:
			sResultError = sDefaultError + ' ' + TextUtils.i18n('CORE/ERROR_INVALID_ADDRESS', {'ADDRESS': (oResponse.Mailbox || '')});
			break;
		case Enums.Errors.CanNotChangePassword:
			sResultError = TextUtils.i18n('CORE/ERROR_UNABLE_CHANGE_PASSWORD');
			break;
		case Enums.Errors.AccountOldPasswordNotCorrect:
			sResultError = TextUtils.i18n('CORE/ERROR_CURRENT_PASSWORD_NOT_CORRECT');
			break;
		case Enums.Errors.FetcherIncServerNotAvailable:
		case Enums.Errors.FetcherLoginNotCorrect:
			sResultError = TextUtils.i18n('CORE/ERROR_FETCHER_NOT_SAVED');
			break;
		case Enums.Errors.HelpdeskUserNotExists:
			sResultError = TextUtils.i18n('CORE/ERROR_FORGOT_NO_HELPDESK_ACCOUNT');
			break;
		case Enums.Errors.MailServerError:
			sResultError = TextUtils.i18n('CORE/ERROR_CANT_CONNECT_TO_SERVER');
			break;
		case Enums.Errors.DataTransferFailed:
			sResultError = TextUtils.i18n('CORE/ERROR_DATA_TRANSFER_FAILED');
			break;
		case Enums.Errors.NotDisplayedError:
			sResultError = '';
			break;
	}
	
	if (sResultError !== '')
	{
		if (sResponseError !== '')
		{
			sResultError += ' (' + sResponseError + ')';
		}
		Screens.showError(sResultError, false, !!bNotHide);
	}
	else if (sResponseError !== '')
	{
		Screens.showError(sResponseError);
	}
};

module.exports = Api;
