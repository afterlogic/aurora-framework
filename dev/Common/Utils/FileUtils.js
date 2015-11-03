/**
 * Can be connected to external applications.
 */

Utils.File = {};

/**
 * Gets link for view by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {boolean=} bIsExt = false
 * @param {string=} sTenatHash = ''
 * 
 * @return {string}
 */
Utils.File.getViewLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
{
	var
		sViewLink = '?/Raw/View/' + iAccountId + '/' + sHash,
		sExtPart = (bIsExt === true) ? '/1' : '/0',
		sTenantPart = (typeof sTenatHash === 'string' && sTenatHash !== '') ? '/' + sTenatHash : ''
	;
	return sViewLink + sExtPart + sTenantPart;
};
