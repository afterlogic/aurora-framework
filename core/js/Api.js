'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
	Screens = require('core/js/Screens.js'),
			
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
			sResultError = sDefaultError;
			break;
		case Enums.Errors.AuthError:
			sResultError = TextUtils.i18n('WARNING/LOGIN_PASS_INCORRECT');
			break;
		case Enums.Errors.DataBaseError:
			sResultError = TextUtils.i18n('WARNING/DATABASE_ERROR');
			break;
		case Enums.Errors.LicenseProblem:
			sResultError = TextUtils.i18n('WARNING/INVALID_LICENSE');
			break;
		case Enums.Errors.DemoLimitations:
			sResultError = TextUtils.i18n('DEMO/WARNING_THIS_FEATURE_IS_DISABLED');
			break;
		case Enums.Errors.Captcha:
			sResultError = TextUtils.i18n('WARNING/CAPTCHA_IS_INCORRECT');
			break;
		case Enums.Errors.CanNotGetMessage:
			sResultError = TextUtils.i18n('MESSAGE/ERROR_MESSAGE_DELETED');
			break;
		case Enums.Errors.NoRequestedMailbox:
			sResultError = sDefaultError + ' ' + TextUtils.i18n('COMPOSE/ERROR_INVALID_ADDRESS', {'ADDRESS': (oResponse.Mailbox || '')});
			break;
		case Enums.Errors.CanNotChangePassword:
			sResultError = TextUtils.i18n('WARNING/UNABLE_CHANGE_PASSWORD');
			break;
		case Enums.Errors.AccountOldPasswordNotCorrect:
			sResultError = TextUtils.i18n('WARNING/CURRENT_PASSWORD_NOT_CORRECT');
			break;
		case Enums.Errors.FetcherIncServerNotAvailable:
			sResultError = TextUtils.i18n('WARNING/FETCHER_SAVE_ERROR');
			break;
		case Enums.Errors.FetcherLoginNotCorrect:
			sResultError = TextUtils.i18n('WARNING/FETCHER_SAVE_ERROR');
			break;
		case Enums.Errors.HelpdeskUserNotExists:
			sResultError = TextUtils.i18n('HELPDESK/ERROR_FORGOT_NO_ACCOUNT');
			break;
		case Enums.Errors.MailServerError:
			sResultError = TextUtils.i18n('WARNING/CANT_CONNECT_TO_SERVER');
			break;
		case Enums.Errors.DataTransferFailed:
			sResultError = TextUtils.i18n('WARNING/DATA_TRANSFER_FAILED');
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