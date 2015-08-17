
/**
 * @constructor
 */
function CLinkBuilder()
{
}

/**
 * @param {string=} sFolder = 'INBOX'
 * @param {number=} iPage = 1
 * @param {string=} sUid = ''
 * @param {string=} sSearch = ''
 * @param {string=} sFilters = ''
 * @return {Array}
 */
CLinkBuilder.prototype.mailbox = function (sFolder, iPage, sUid, sSearch, sFilters)
{
	var	aResult = [Enums.Screens.Mailbox];
	
	iPage = Utils.isNormal(iPage) ? Utils.pInt(iPage) : 1;
	sUid = Utils.isNormal(sUid) ? Utils.pString(sUid) : '';
	sSearch = Utils.isNormal(sSearch) ? Utils.pString(sSearch) : '';
	sFilters = Utils.isNormal(sFilters) ? Utils.pString(sFilters) : '';

	if (sFolder && '' !== sFolder)
	{
		aResult.push(sFolder);
	}
	
	if (sFilters && '' !== sFilters)
	{
		aResult.push('filter:' + sFilters);
	}
	
	if (1 < iPage)
	{
		aResult.push('p' + iPage);
	}

	if (sUid && '' !== sUid)
	{
		aResult.push('msg' + sUid);
	}

	if (sSearch && '' !== sSearch)
	{
		aResult.push(sSearch);
	}
	
	return aResult;
};

/**
 * @return {Array}
 */
CLinkBuilder.prototype.inbox = function ()
{
	return this.mailbox();
};

/**
 * @param {Array} aParams
 * 
 * @return {Object}
 */
CLinkBuilder.prototype.parseMailbox = function (aParams)
{
	var
		sFolder = 'INBOX',
		iPage = 1,
		sUid = '',
		sSearch = '',
		sFilters = '',
		sTemp = '',
		iIndex = 0
	;
	
	if (Utils.isNonEmptyArray(aParams))
	{
		sFolder = Utils.pString(aParams[iIndex]);
		iIndex++;

		if (aParams.length > iIndex)
		{
			sTemp = Utils.pString(aParams[iIndex]);
			if (sTemp === 'filter:' + Enums.FolderFilter.Flagged)
			{
				sFilters = Enums.FolderFilter.Flagged;
				iIndex++;
			}
			if (sTemp === 'filter:' + Enums.FolderFilter.Unseen)
			{
				sFilters = Enums.FolderFilter.Unseen;
				iIndex++;
			}
		}

		if (aParams.length > iIndex)
		{
			sTemp = Utils.pString(aParams[iIndex]);
			if (this.isPageParam(sTemp))
			{
				iPage = Utils.pInt(sTemp.substr(1));
				if (iPage <= 0)
				{
					iPage = 1;
				}
				iIndex++;
			}
		}
		
		if (aParams.length > iIndex)
		{
			sTemp = Utils.pString(aParams[iIndex]);
			if (this.isMsgParam(sTemp))
			{
				sUid = sTemp.substr(3);
				iIndex++;
			}
		}

		if (aParams.length > iIndex)
		{
			sSearch = Utils.pString(aParams[iIndex]);
		}
	}
	
	return {
		'Folder': sFolder,
		'Page': iPage,
		'Uid': sUid,
		'Search': sSearch,
		'Filters': sFilters
	};
};

/**
 * @param {number=} iType
 * @param {string=} sGroupId
 * @param {string=} sSearch
 * @param {number=} iPage
 * @param {string=} sUid
 * @returns {Array}
 */
CLinkBuilder.prototype.contacts = function (iType, sGroupId, sSearch, iPage, sUid)
{
	var
		aParams = [Enums.Screens.Contacts]
	;
	
	if (typeof iType === 'number')
	{
		aParams.push(iType);
	}
	
	if (sGroupId && sGroupId !== '')
	{
		aParams.push(sGroupId);
	}
	
	if (sSearch && sSearch !== '')
	{
		aParams.push(sSearch);
	}
	
	if (Utils.isNumeric(iPage))
	{
		aParams.push('p' + iPage);
	}
	
	if (sUid && sUid !== '')
	{
		aParams.push('cnt' + sUid);
	}
	
	return aParams;
};

/**
 * @param {Array} aParam
 * 
 * @return {Object}
 */
CLinkBuilder.prototype.parseContacts = function (aParam)
{
	var
		iIndex = 0,
		aGroupTypes = [Enums.ContactsGroupListType.Personal, Enums.ContactsGroupListType.SharedToAll, Enums.ContactsGroupListType.Global, Enums.ContactsGroupListType.All],
		iType = Enums.ContactsGroupListType.All,
		sGroupId = '',
		sSearch = '',
		iPage = 1,
		sUid = ''
	;

	if (Utils.isNonEmptyArray(aParam))
	{
		iType = Utils.pInt(aParam[iIndex]);
		iIndex++;
		if (-1 === Utils.inArray(iType, aGroupTypes))
		{
			iType = Enums.ContactsGroupListType.SubGroup;
		}
		if (iType === Enums.ContactsGroupListType.SubGroup)
		{
			if (aParam.length > iIndex)
			{
				sGroupId = Utils.pString(aParam[iIndex]);
				iIndex++;
			}
			else
			{
				iType = Enums.ContactsGroupListType.Personal;
			}
		}
		
		if (aParam.length > iIndex && !this.isPageParam(aParam[iIndex]) && !this.isContactParam(aParam[iIndex]))
		{
			sSearch = Utils.pString(aParam[iIndex]);
			iIndex++;
		}
		
		if (aParam.length > iIndex && this.isPageParam(aParam[iIndex]))
		{
			iPage = Utils.pInt(aParam[iIndex].substr(1));
			iIndex++;
			if (iPage <= 0)
			{
				iPage = 1;
			}
		}
		
		if (aParam.length > iIndex && this.isContactParam(aParam[iIndex]))
		{
			sUid = Utils.pString(aParam[iIndex].substr(3));
			iIndex++;
		}
	}
	
	return {
		'Type': iType,
		'GroupId': sGroupId,
		'Search': sSearch,
		'Page': iPage,
		'Uid': sUid
	};
};

/**
 * @param {string} sTemp
 * 
 * @return {boolean}
 */
CLinkBuilder.prototype.isPageParam = function (sTemp)
{
	return ('p' === sTemp.substr(0, 1) && (/^[1-9][\d]*$/).test(sTemp.substr(1)));
};

/**
 * @param {string} sTemp
 * 
 * @return {boolean}
 */
CLinkBuilder.prototype.isContactParam = function (sTemp)
{
	return ('cnt' === sTemp.substr(0, 3) && (/^[1-9][\d]*$/).test(sTemp.substr(3)));
};

/**
 * @param {string} sTemp
 * 
 * @return {boolean}
 */
CLinkBuilder.prototype.isMsgParam = function (sTemp)
{
	return ('msg' === sTemp.substr(0, 3) && (/^[1-9][\d]*$/).test(sTemp.substr(3)));
};

/**
 * @return {Array}
 */
CLinkBuilder.prototype.compose = function ()
{
	var sScreen = (AppData.SingleMode) ? Enums.Screens.SingleCompose : Enums.Screens.Compose;
	
	return [sScreen];
};

/**
 * @param {string} sType
 * @param {string} sFolder
 * @param {string} sUid
 * @param {boolean} bSingleMode
 * 
 * @return {Array}
 */
CLinkBuilder.prototype.composeFromMessage = function (sType, sFolder, sUid, bSingleMode)
{
	var sScreen = (bSingleMode || AppData.SingleMode) ? Enums.Screens.SingleCompose : Enums.Screens.Compose;
	
	return [sScreen, sType, sFolder, sUid];
};

/**
 * @param {string} sTo
 * 
 * @return {Array}
 */
CLinkBuilder.prototype.composeWithToField = function (sTo)
{
	var sScreen = (AppData.SingleMode) ? Enums.Screens.SingleCompose : Enums.Screens.Compose;
	
	return [sScreen, 'to', sTo];
};

/**
 * @param {?} mToAddr
 * @returns {Object}
 */
CLinkBuilder.prototype.parseToAddr = function (mToAddr)
{
	var
		sToAddr = decodeURI(Utils.pString(mToAddr)),
		bHasMailTo = sToAddr.indexOf('mailto:') !== -1,
		aMailto = [],
		aMessageParts = [],
		sSubject = '',
		sCcAddr = '',
		sBccAddr = '',
		sBody = ''
	;
	
	if (bHasMailTo)
	{
		aMailto = sToAddr.replace(/^mailto:/, '').split('?');
		sToAddr = aMailto[0];
		if (aMailto.length === 2)
		{
			aMessageParts = aMailto[1].split('&');
			_.each(aMessageParts, function (sPart) {
				var
					aParts = sPart.split('=')
				;
				if (aParts.length === 2)
				{
					switch (aParts[0])
					{
						case 'subject': sSubject = aParts[1]; break;
						case 'cc': sCcAddr = aParts[1]; break;
						case 'bcc': sBccAddr = aParts[1]; break;
						case 'body': sBody = aParts[1]; break;
	}
				}
			});
		}
	}
	
	return {
		'to': sToAddr,
		'hasMailto': bHasMailTo,
		'subject': sSubject,
		'cc': sCcAddr,
		'bcc': sBccAddr,
		'body': sBody
	};
};
