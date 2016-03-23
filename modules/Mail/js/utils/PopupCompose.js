'use strict';

var
	Popups = require('modules/Core/js/Popups.js'),
	
	LinksUtils = require('modules/Mail/js/utils/Links.js'),
	
	PopupComposeUtils = {}
;

function GetComposePopup()
{
	return require('modules/Mail/js/popups/ComposePopup.js');
}

PopupComposeUtils.composeMessage = function ()
{
	Popups.showPopup(GetComposePopup());
};

/**
 * @param {string} sFolder
 * @param {string} sUid
 */
PopupComposeUtils.composeMessageFromDrafts = function (sFolder, sUid)
{
	var aParams = LinksUtils.getComposeFromMessage('drafts', sFolder, sUid);
	aParams.shift();
	Popups.showPopup(GetComposePopup(), [aParams]);
};

/**
 * @param {string} sReplyType
 * @param {string} sFolder
 * @param {string} sUid
 */
PopupComposeUtils.composeMessageAsReplyOrForward = function (sReplyType, sFolder, sUid)
{
	var aParams = LinksUtils.getComposeFromMessage(sReplyType, sFolder, sUid);
	aParams.shift();
	Popups.showPopup(GetComposePopup(), [aParams]);
};

/**
 * @param {string} sToAddresses
 */
PopupComposeUtils.composeMessageToAddresses = function (sToAddresses)
{
	var aParams = LinksUtils.getComposeWithToField(sToAddresses);
	aParams.shift();
	Popups.showPopup(GetComposePopup(), [aParams]);
};

/**
 * @param {Object} oVcard
 */
PopupComposeUtils.composeMessageWithVcard = function (oVcard)
{
	var aParams = ['vcard', oVcard];
	Popups.showPopup(GetComposePopup(), [aParams]);
};

/**
 * @param {string} sArmor
 * @param {string} sDownloadLinkFilename
 */
PopupComposeUtils.composeMessageWithPgpKey = function (sArmor, sDownloadLinkFilename)
{
	var aParams = ['data-as-file', sArmor, sDownloadLinkFilename];
	Popups.showPopup(GetComposePopup(), [aParams]);
};

/**
 * @param {Array} aFileItems
 */
PopupComposeUtils.composeMessageWithFiles = function (aFileItems)
{
	var aParams = ['file', aFileItems];
	Popups.showPopup(GetComposePopup(), [aParams]);
};

PopupComposeUtils.closeComposePopup = function ()
{
	Popups.showPopup(GetComposePopup(), [['close']]);
};

module.exports = PopupComposeUtils;