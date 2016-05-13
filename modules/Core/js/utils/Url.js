'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	UrlUtils = {}
;

/**
 * Obtains application path from location object.
 * 
 * @return {string}
 */
UrlUtils.getAppPath = function ()
{
	var sAppOrigin = window.location.origin || window.location.protocol + '//' + window.location.host;
	
	return sAppOrigin + window.location.pathname;
};

/**
 * Downloads by url through iframe or new window.
 *
 * @param {string} sUrl
 */
UrlUtils.downloadByUrl = function (sUrl)
{
	var
		Browser = require('modules/Core/js/Browser.js'),
		oIframe = null
	;
	
	if (Browser.mobileDevice)
	{
		window.open(sUrl);
	}
	else
	{
		oIframe = $('<iframe style="display: none;"></iframe>').appendTo(document.body);
		oIframe.attr('src', sUrl);
		
		setTimeout(function () {
			oIframe.remove();
		}, 60000);
	}
};

/**
 * Obtains parameters from browser get-string.
 * **aGetParams** - static variable wich includes all get parameters.
 * 
 * @param {string} sParamName Name of parameter wich is obtained from get-string
 * 
 * @return {string|null}
 */
UrlUtils.getRequestParam = function (sParamName)
{
	var
		aParams = [],
		aGetParams = [],
		sResult = null
	;
	
	if (this.aGetParams === undefined)
	{
		aParams = (location.search !== '') ? (location.search.substr(1)).split('&') : [];

		if (aParams.length > 0)
		{
			_.each(aParams, function (sParam) {
				var aKeyValues = sParam.split('=');
				aGetParams[aKeyValues[0]] = aKeyValues.length > 1 ? aKeyValues[1] : '';
			});
		}
		
		this.aGetParams = aGetParams;
	}
	
	if (this.aGetParams[sParamName] !== undefined)
	{
		sResult = this.aGetParams[sParamName];
	}

	return sResult;
};

/**
 * Clears search and hash strings and reloads page.
 * 
 * @param {boolean} bOnlyReload If **true** doesn't clear search and hash in location.
 * @param {boolean} bClearSearch If **true** clears search string in location.
 */
UrlUtils.clearAndReloadLocation = function (bOnlyReload, bClearSearch)
{
	if (!bOnlyReload && (window.location.search !== '' || window.location.hash !== ''))
	{
		var sNewHref = Utils.getAppPath();

		if (!bClearSearch && window.location.search !== '')
		{
			sNewHref += window.location.search;
		}

		if ('replaceState' in history)
		{
			history.replaceState('', document.title, sNewHref);
			window.location.reload(true);
		}
		else
		{
			window.location.href = sNewHref;
		}
	}
	else
	{
		window.location.reload();
	}
};

module.exports = UrlUtils;
