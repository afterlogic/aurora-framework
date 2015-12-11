'use strict';

var
//	Utils = require('core/js/utils/Common.js'),
	
	FilesUtils = {}
;

///**
// * Gets link for download by hash.
// *
// * @param {number} iAccountId
// * @param {string} sHash
// * @param {boolean=} bIsExt = false
// * @param {string=} sTenatHash = ''
// * 
// * @return {string}
// */
//FilesUtils.getDownloadLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
//{
//	bIsExt = Utils.isUnd(bIsExt) ? false : !!bIsExt;
//	sTenatHash = Utils.isUnd(sTenatHash) ? '' : sTenatHash;
//
//	return 'index.php?/Raw/Download/' + iAccountId + '/' + sHash + '/' + (bIsExt ? '1' : '0') + ('' === sTenatHash ? '' : '/' + sTenatHash);
//};
//
///**
// * Gets link for view by hash.
// *
// * @param {number} iAccountId
// * @param {string} sHash
// * @param {boolean=} bIsExt = false
// * @param {string=} sTenatHash = ''
// * 
// * @return {string}
// */
//FilesUtils.getViewLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
//{
//	var
//		sViewLink = '?/Raw/View/' + iAccountId + '/' + sHash,
//		sExtPart = (bIsExt === true) ? '/1' : '/0',
//		sTenantPart = (typeof sTenatHash === 'string' && sTenatHash !== '') ? '/' + sTenatHash : ''
//	;
//		
//	return sViewLink + sExtPart + sTenantPart;
//};
//
///**
// * Gets link for thumbnail by hash.
// *
// * @param {number} iAccountId
// * @param {string} sHash
// * @param {boolean=} bIsExt = false
// * @param {string=} sTenatHash = ''
// *
// * @return {string}
// */
//FilesUtils.getViewThumbnailLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
//{
//	bIsExt = Utils.isUnd(bIsExt) ? false : !!bIsExt;
//	sTenatHash = Utils.isUnd(sTenatHash) ? '' : sTenatHash;
//	
//	return '?/Raw/Thumbnail/' + iAccountId + '/' + sHash + '/' + (bIsExt ? '1' : '0') + ('' === sTenatHash ? '' : '/' + sTenatHash);
//};
//
///**
// * Gets link for view by hash in iframe.
// *
// * @param {number} iAccountId
// * @param {string} sUrl
// *
// * @return {string}
// */
//FilesUtils.getIframeWrappwer = function (iAccountId, sUrl)
//{
//	return '?/Raw/Iframe/' + iAccountId + '/' + window.encodeURIComponent(sUrl) + '/';
//};

/**
 * Gets link for download by hash.
 *
 * @param {string} sModuleName
 * @param {string} sHash
 * 
 * @return {string}
 */
FilesUtils.getDownloadLink = function (sModuleName, sHash)
{
	return '?/Download/' + sModuleName + '/DownloadFile/' + sHash + '/';
};

/**
 * Gets link for download by hash.
 *
 * @param {string} sModuleName
 * @param {string} sHash
 * 
 * @return {string}
 */
FilesUtils.getViewLink = function (sModuleName, sHash)
{
	return '?/Download/' + sModuleName + '/ViewFile/' + sHash + '/';
};

/**
 * Gets link for thumbnail by hash.
 *
 * @param {string} sModuleName
 * @param {string} sHash
 *
 * @return {string}
 */
FilesUtils.getThumbnailLink = function (sModuleName, sHash)
{
	return '?/Download/' + sModuleName + '/GetFileThumbnail/' + sHash + '/';
};

module.exports = FilesUtils;