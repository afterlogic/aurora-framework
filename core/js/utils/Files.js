'use strict';

var FilesUtils = {};

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
	return sHash.length > 0 ? '?/Download/' + sModuleName + '/DownloadFile/' + sHash + '/' : '';
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
	return sHash.length > 0 ? '?/Download/' + sModuleName + '/ViewFile/' + sHash + '/' : '';
};

/**
 * Gets link for view by hash in iframe.
 *
 * @param {number} iAccountId
 * @param {string} sUrl
 *
 * @return {string}
 */
FilesUtils.getIframeWrappwer = function (iAccountId, sUrl)
{
	return '?/Raw/Iframe/' + iAccountId + '/' + window.encodeURIComponent(sUrl) + '/';
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
	return sHash.length > 0 ? '?/Download/' + sModuleName + '/GetFileThumbnail/' + sHash + '/' : '';
};

FilesUtils.thumbQueue = (function () {

	var
		oImages = {},
		oImagesIncrements = {},
		iNumberOfImages = 2
	;

	return function (sSessionUid, sImageSrc, fImageSrcObserver)
	{
		if(sImageSrc && fImageSrcObserver)
		{
			if(!(sSessionUid in oImagesIncrements) || oImagesIncrements[sSessionUid] > 0) //load first images
			{
				if(!(sSessionUid in oImagesIncrements)) //on first image
				{
					oImagesIncrements[sSessionUid] = iNumberOfImages;
					oImages[sSessionUid] = [];
				}
				oImagesIncrements[sSessionUid]--;

				fImageSrcObserver(sImageSrc); //load image
			}
			else //create queue
			{
				oImages[sSessionUid].push({
					imageSrc: sImageSrc,
					imageSrcObserver: fImageSrcObserver,
					messageUid: sSessionUid
				});
			}
		}
		else //load images from queue (fires load event)
		{
			if(oImages[sSessionUid] && oImages[sSessionUid].length)
			{
				oImages[sSessionUid][0].imageSrcObserver(oImages[sSessionUid][0].imageSrc);
				oImages[sSessionUid].shift();
			}
		}
	};
}());

module.exports = FilesUtils;