'use strict';

var
	Popups = require('core/js/Popups.js'),
	ComposePopup = require('modules/Mail/js/popups/ComposePopup.js'),
	
	LinksUtils = require('modules/Mail/js/utils/Links.js'),
	
	PopupComposeUtils = {}
;

PopupComposeUtils.composeMessage = function ()
{
	Popups.showPopup(ComposePopup);
};

/**
 * @param {string} sFolder
 * @param {string} sUid
 */
PopupComposeUtils.composeMessageFromDrafts = function (sFolder, sUid)
{
	var aParams = LinksUtils.getComposeFromMessage('drafts', sFolder, sUid);
	aParams.shift();
	Popups.showPopup(ComposePopup, [aParams]);
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
	Popups.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {string} sToAddresses
 */
PopupComposeUtils.composeMessageToAddresses = function (sToAddresses)
{
	var aParams = LinksUtils.getComposeWithToField(sToAddresses);
	aParams.shift();
	Popups.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {Object} oVcard
 */
PopupComposeUtils.composeMessageWithVcard = function (oVcard)
{
	var aParams = ['vcard', oVcard];
	Popups.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {string} sArmor
 * @param {string} sDownloadLinkFilename
 */
PopupComposeUtils.composeMessageWithPgpKey = function (sArmor, sDownloadLinkFilename)
{
	var aParams = ['data-as-file', sArmor, sDownloadLinkFilename];
	Popups.showPopup(ComposePopup, [aParams]);
};

/**
 * @param {Array} aFileItems
 */
PopupComposeUtils.composeMessageWithFiles = function (aFileItems)
{
	var aParams = ['file', aFileItems];
	Popups.showPopup(ComposePopup, [aParams]);
};

PopupComposeUtils.closeComposePopup = function ()
{
	Popups.showPopup(ComposePopup, [['close']]);
};

module.exports = PopupComposeUtils;