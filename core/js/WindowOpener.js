'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Routing = require('core/js/Routing.js'),
	Popups = require('core/js/Popups.js'),
	
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
	 * @param {{folder:Function, uid:Function}} oMessage
	 * @param {boolean=} bDrafts
	 */
	openMessage: function (oMessage, bDrafts)
	{
		if (oMessage)
		{
			var
				sFolder = oMessage.folder(),
				sUid = oMessage.uid(),
				sHash = ''
			;
			
			if (bDrafts)
			{
				sHash = Routing.buildHashFromArray([Enums.Screens.SingleCompose, 'drafts', sFolder, sUid]);
			}
			else
			{
				sHash = Routing.buildHashFromArray([Enums.Screens.SingleMessageView, sFolder, 'msg' + sUid]);
			}

			this.openTab(sHash);
		}
	},

	/**
	 * @param {string} sUrl
	 * @param {string=} sWinName
	 * 
	 * @return Object
	 */
	openTab: function (sUrl, sWinName)
	{
		$.cookie('aft-cache-ctrl', '1');
		var oWin = null;

		oWin = window.open(sUrl, '_blank');
		
		if (oWin)
		{
			oWin.focus();
			oWin.name = sWinName ? sWinName : (AppData.Accounts ? AppData.Accounts.currentId() : 0);
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
		oWin.name = 98; // todo: AppData.Accounts ? AppData.Accounts.currentId() : 0; //no Accounts in client helpdesk

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
		var
			iLen = aOpenedWins.length,
			iIndex = 0,
			oWin = null
		;

		for (; iIndex < iLen; iIndex++)
		{
			oWin = aOpenedWins[iIndex];
			if (!oWin.closed)
			{
				oWin.close();
			}
		}

		aOpenedWins = [];
	}
};
