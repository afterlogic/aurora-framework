'use strict';

var
	$ = require('jquery'),
	Utils = require('core/js/utils/Common.js'),
	MessageUtils = {}
;

/**
 * Displays embedded images, which have cid on the list.
 *
 * @param {object} $html JQuery element containing message body html
 * @param {array} aAttachments Array of objects having fields
 *		- CID
 *		- ContentLocation
 *		- ViewLink
 * @param {array} aFoundCids Array of string cids
 * @param {string=} sAppPath = '' Path to be connected to the ViewLink of every attachment
 */
MessageUtils.showInlinePictures = function ($html, aAttachments, aFoundCids, sAppPath)
{
	var
		fFindAttachmentByCid = function (sCid) {
			return _.find(aAttachments, function (oAttachment) {
				return oAttachment.CID === sCid;
			});
		},
		fFindAttachmentByContentLocation = function (sContentLocation) {
			return _.find(aAttachments, function (oAttachment) {
				return oAttachment.ContentLocation === sContentLocation;
			});
		}
	;

	if (typeof sAppPath !== 'string')
	{
		sAppPath = '';
	}

	if (aFoundCids.length > 0)
	{
		$('[data-x-src-cid]', $html).each(function () {
			var
				sCid = $(this).attr('data-x-src-cid'),
				oAttachment = fFindAttachmentByCid(sCid)
			;
			if (oAttachment && oAttachment.ViewLink.length > 0)
			{
				$(this).attr('src', sAppPath + oAttachment.ViewLink);
			}
		});

		$('[data-x-style-cid]', $html).each(function () {
			var
				sStyle = '',
				sName = $(this).attr('data-x-style-cid-name'),
				sCid = $(this).attr('data-x-style-cid'),
				oAttachment = fFindAttachmentByCid(sCid)
			;

			if (oAttachment && oAttachment.ViewLink.length > 0 && '' !== sName)
			{
				sStyle = $.trim($(this).attr('style'));
				sStyle = '' === sStyle ? '' : (';' === sStyle.substr(-1) ? sStyle + ' ' : sStyle + '; ');
				$(this).attr('style', sStyle + sName + ': url(\'' + oAttachment.ViewLink + '\')');
			}
		});
	}

	$('[data-x-src-location]', $html).each(function () {

		var
			sLocation = $(this).attr('data-x-src-location'),
			oAttachment = fFindAttachmentByContentLocation(sLocation)
		;

		if (!oAttachment)
		{
			oAttachment = fFindAttachmentByCid(sLocation);
		}

		if (oAttachment && oAttachment.ViewLink.length > 0)
		{
			$(this).attr('src', sAppPath + oAttachment.ViewLink);
		}
	});
};

/**
 * Displays external images.
 *
 * @param {object} $html JQuery element containing message body html
 */
MessageUtils.showExternalPictures = function ($html)
{
	$('[data-x-src]', $html).each(function () {
		$(this).attr('src', $(this).attr('data-x-src')).removeAttr('data-x-src');
	});

	$('[data-x-style-url]', $html).each(function () {
		var sStyle = $.trim($(this).attr('style'));
		sStyle = '' === sStyle ? '' : (';' === sStyle.substr(-1) ? sStyle + ' ' : sStyle + '; ');
		$(this).attr('style', sStyle + $(this).attr('data-x-style-url')).removeAttr('data-x-style-url');
	});
};

/**
 * Joins "Re" and "Fwd" prefixes in the message subject.
 * 
 * @param {string} sSubject The message subject.
 * @param {string} sRePrefix "Re" prefix translated into the language of the application.
 * @param {string} sFwdPrefix "Fwd" prefix translated into the language of the application.
 */
MessageUtils.joinReplyPrefixesInSubject = function (sSubject, sRePrefix, sFwdPrefix)
{
	var
		aRePrefixes = [sRePrefix.toUpperCase()],
		aFwdPrefixes = [sFwdPrefix.toUpperCase()],
		sPrefixes = _.union(aRePrefixes, aFwdPrefixes).join('|'),
		sReSubject = '',
		aParts = sSubject.split(':'),
		aResParts = [],
		sSubjectEnd = ''
	;

	_.each(aParts, function (sPart) {
		if (sSubjectEnd.length === 0)
		{
			var
				sPartUpper = $.trim(sPart.toUpperCase()),
				bRe = _.indexOf(aRePrefixes, sPartUpper) !== -1,
				bFwd = _.indexOf(aFwdPrefixes, sPartUpper) !== -1,
				iCount = 1,
				oLastResPart = (aResParts.length > 0) ? aResParts[aResParts.length - 1] : null
			;

			if (!bRe && !bFwd)
			{
				var oMatch = (new window.RegExp('^\\s?(' + sPrefixes + ')\\s?[\\[\\(]([\\d]+)[\\]\\)]$', 'gi')).exec(sPartUpper);
				if (oMatch && oMatch.length === 3)
				{
					bRe = _.indexOf(aRePrefixes, oMatch[1].toUpperCase()) !== -1;
					bFwd = _.indexOf(aFwdPrefixes, oMatch[1].toUpperCase()) !== -1;
					iCount = Utils.pInt(oMatch[2]);
				}
			}

			if (bRe)
			{
				if (oLastResPart && oLastResPart.prefix === sRePrefix)
				{
					oLastResPart.count += iCount;
				}
				else
				{
					aResParts.push({prefix: sRePrefix, count: iCount});
				}
			}
			else if (bFwd)
			{
				if (oLastResPart && oLastResPart.prefix === sFwdPrefix)
				{
					oLastResPart.count += iCount;
				}
				else
				{
					aResParts.push({prefix: sFwdPrefix, count: iCount});
				}
			}
			else
			{
				sSubjectEnd = sPart;
			}
		}
		else
		{
			sSubjectEnd += ':' + sPart;
		}
	});

	_.each(aResParts, function (sResPart) {
		if (sResPart.count === 1)
		{
			sReSubject += sResPart.prefix + ': ';
		}
		else
		{
			sReSubject += sResPart.prefix + '[' + sResPart.count + ']: ';
		}
	});
	sReSubject += $.trim(sSubjectEnd);
	
	return sReSubject;
};

module.exports = MessageUtils;