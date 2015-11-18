'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	Popups = require('core/js/Popups.js'),
	App = require('core/js/App.js'),
	
	iDefaultRatio = 0.8,
	aOpenedWins = []
;

/**
 * @return string
 */
function GetSizeParameters()
{
	var
		iScreenWidth = window.screen.width,
		iWidth = Math.ceil(iScreenWidth * iDefaultRatio),
		iLeft = Math.ceil((iScreenWidth - iWidth) / 2),

		iScreenHeight = window.screen.height,
		iHeight = Math.ceil(iScreenHeight * iDefaultRatio),
		iTop = Math.ceil((iScreenHeight - iHeight) / 2)
	;

	return ',width=' + iWidth + ',height=' + iHeight + ',top=' + iTop + ',left=' + iLeft;
}

module.exports = {
	/**
	 * @param {string} sUrl
	 * @param {string=} sWinName
	 * 
	 * @return Object
	 */
	openTab: function (sUrl, sWinName)
	{
		$.cookie('aft-cache-ctrl', '1');
		var oWin = window.open(sUrl, '_blank');
		
		if (oWin)
		{
			oWin.focus();
			oWin.name = sWinName ? sWinName : (App.currentAccountId ? App.currentAccountId() : 0);
			aOpenedWins.push(oWin);
		}
		
		return oWin;
	},
	
	/**
	 * @param {string} sUrl
	 * @param {string} sPopupName
	 * @param {boolean=} bMenubar = false
	 * 
	 * @return Object
	 */
	open: function (sUrl, sPopupName, bMenubar)
	{
		var
			sMenubar = (bMenubar) ? ',menubar=yes' : ',menubar=no',
			sParams = 'location=no,toolbar=no,status=no,scrollbars=yes,resizable=yes' + sMenubar,
			oWin = null
		;

		sPopupName = sPopupName.replace(/\W/g, ''); // forbidden characters in the name of the window for ie
		sParams += GetSizeParameters();

		oWin = window.open(sUrl, sPopupName, sParams);
		oWin.focus();
		oWin.name = App.currentAccountId ? App.currentAccountId() : 0;

		aOpenedWins.push(oWin);
		
		return oWin;
	},
	
	/**
	 * @returns {Array}
	 */
	getOpenedDraftUids: function ()
	{
		aOpenedWins = _.filter(aOpenedWins, function (oWin) {
			return !oWin.closed;
		});
		
		var aDraftUids = _.map(aOpenedWins, function (oWin) {
			return oWin.App ? oWin.App.MailCache.editedDraftUid() : '';
		});
		
		if (Popups.hasOpenedMinimizedPopups())
		{
			aDraftUids.push(App.MailCache.editedDraftUid());
		}
		
		return _.uniq(_.compact(aDraftUids));
	},
	
	/**
	 * @param {string} aUids
	 */
	closeComposesWithDraftUids: function (aUids)
	{
		_.each(aOpenedWins, function (oWin) {
			if (oWin.App && -1 !== Utils.inArray(oWin.App.MailCache.editedDraftUid(), aUids))
			{
				oWin.close();
			}
		});
		
		if (-1 !== Utils.inArray(App.MailCache.editedDraftUid(), aUids))
		{
			App.Api.closeComposePopup();
		}
	},

	closeAll: function ()
	{
		_.each(aOpenedWins, function (oWin) {
			if (!oWin.closed)
			{
				oWin.close();
			}
		});

		aOpenedWins = [];
	}
};
