'use strict';

var
	_ = require('underscore'),
	Enums = {
		has: function (sEnumName, mFoundValue) {
			return !!_.find(window.Enums[sEnumName], function (mValue) {
				return mFoundValue === mValue;
			});
		}
	}
;

/**
 * @enum {number}
 */
Enums.Key = {
	'Tab': 9,
	'Enter': 13,
	'Shift': 16,
	'Ctrl': 17,
	'Esc': 27,
	'Space': 32,
	'PageUp': 33,
	'PageDown': 34,
	'End': 35,
	'Home': 36,
	'Up': 38,
	'Down': 40,
	'Left': 37,
	'Right': 39,
	'Del': 46,
	'Six': 54,
	'a': 65,
	'b': 66,
	'c': 67,
	'f': 70,
	'i': 73,
	'k': 75,
	'n': 78,
	'p': 80,
	'q': 81,
	'r': 82,
	's': 83,
	'u': 85,
	'v': 86,
	'y': 89,
	'z': 90,
	'F5': 116,
	'Comma': 188,
	'Dot': 190,
	'Dash': 192,
	'Apostrophe': 222
};

/**
 * @enum {number}
 */
Enums.Errors = {
	'InvalidToken': 101,
	'AuthError': 102,
	'DataBaseError': 104,
	'LicenseProblem': 105,
	'DemoLimitations': 106,
	'Captcha': 107,
	'AccessDenied': 108,
	'CanNotGetMessage': 202,
	'ImapQuota': 205,
	'NotSavedInSentItems': 304,
	'NoRequestedMailbox': 305,
	'CanNotChangePassword': 502,
	'AccountOldPasswordNotCorrect': 503,
	'FetcherIncServerNotAvailable': 702,
	'FetcherLoginNotCorrect': 703,
	'HelpdeskThrowInWebmail': 805,
	'HelpdeskUserNotExists': 807,
	'HelpdeskUserNotActivated': 808,
	'IncorrectFileExtension': 811,
	'MailServerError': 901,
	'DataTransferFailed': 1100,
	'NotDisplayedError': 1155
};

Enums.MobilePanel = {
	'Groups': 1,
	'Items': 2,
	'View': 3
};

/**
 * @enum {number}
 */
Enums.TimeFormat = {
	'F24': '0',
	'F12': '1'
};

if (typeof window.Enums === 'undefined')
{
	window.Enums = {};
}

_.extendOwn(window.Enums, Enums);
