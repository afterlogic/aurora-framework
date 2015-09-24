'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	Settings = require('core/js/Settings.js'),
	
	bSingleMode = false
;

/**
 * @constructor
 */
function CScreens()
{
	var $win = $(window);
	this.resizeAll = _.debounce(function () {
		$win.resize();
	}, 100);
	
	this.oItems = {};

	this.currentScreen = ko.observable('');
	this.sDefaultScreen = '';

	this.informationScreen = ko.observable(null);
}

CScreens.prototype.init = function (bAuth)
{
	var oModulesScreens = ModulesManager.getModulesScreens(bAuth);
	
	_.each(oModulesScreens, _.bind(function (oScreens, sModuleName) {
		this.addToScreenList(sModuleName, oScreens);
	}, this));
	
	this.addToScreenList('', require('core/js/screenList.js'));
	
	if (this.oItems[Settings.EntryModule.toLowerCase()])
	{
		this.sDefaultScreen = Settings.EntryModule.toLowerCase();
	}
	
	if (!bSingleMode && bAuth)
	{
		this.showNormalScreen('header');
	}
	
	this.initInformation();
};

CScreens.prototype.addToScreenList = function (sPrefix, oScreenList)
{
	_.each(oScreenList, _.bind(function (oScreen, sKey) {
		var sNewKey = sKey.toLowerCase();
		if (sPrefix !== '')
		{
			if (sKey === 'main')
			{
				sNewKey = sPrefix.toLowerCase();
			}
			else
			{
				sNewKey = sPrefix.toLowerCase() + '-' + sKey;
			}
		}
		this.oItems[sNewKey] = oScreen;
	}, this));
};

CScreens.prototype.route = function (aParams)
{
	var
		sCurrentScreen = this.currentScreen(),
		oCurrentScreen = this.oItems[sCurrentScreen],
		sScreen = aParams.shift()
	;
	
	if ((sScreen === '' || !this.oItems[sScreen]) && sCurrentScreen === '')
	{
		sScreen = this.sDefaultScreen;
	}
	
	if (this.oItems[sScreen])
	{
		if (sCurrentScreen === sScreen)
		{
			if (oCurrentScreen && oCurrentScreen.bInitialized && $.isFunction(oCurrentScreen.onRoute))
			{
				oCurrentScreen.onRoute(aParams);
			}
		}
		else
		{
			if (oCurrentScreen && oCurrentScreen.bInitialized)
			{
				oCurrentScreen.hideViewModel();
			}
			
			this.showNormalScreen(sScreen, aParams);
			
			oCurrentScreen = this.oItems[sScreen];
			if (oCurrentScreen && oCurrentScreen.bInitialized)
			{
				this.currentScreen(sScreen);
				if ($.isFunction(oCurrentScreen.onRoute))
				{
					oCurrentScreen.onRoute(aParams);
				}
			}
		}
	}
};

//CScreens.prototype.init = function ()
//{
//	$('#pSevenContent').addClass('single_mode');
//	
//	_.defer(function () {
//		if (!AppData.SingleMode)
//		{
//			$('#pSevenContent').removeClass('single_mode');
//		}
//	});
//};

/**
 * @param {string} sScreen
 * @param {?=} mParams
 */
CScreens.prototype.showCurrentScreen = function (sScreen, mParams)
{
	var
		oCurrentScreen = this.oItems[this.currentScreen()]
	;
	
	if (this.currentScreen() !== sScreen)
	{
		if (oCurrentScreen && oCurrentScreen.bInitialized)
		{
			oCurrentScreen.hideViewModel();
		}
		this.currentScreen(sScreen);
	}
	
	this.showNormalScreen(sScreen, mParams);
	this.resizeAll();
};

/**
 * @param {string} sScreen
 * @param {?=} mParams
 * 
 * @return Object
 */
CScreens.prototype.showNormalScreen = function (sScreen, mParams)
{
	var
		sScreenId = sScreen,
		oScreen = this.oItems[sScreenId]
	;
	
	if (oScreen)
	{
		oScreen.bInitialized = (typeof oScreen.bInitialized !== 'boolean') ? false : oScreen.bInitialized;
		if (!oScreen.bInitialized)
		{
			oScreen = this.initViewModel(oScreen);
			this.oItems[sScreenId] = oScreen;
			oScreen.bInitialized = true;
		}

		oScreen.showViewModel(mParams);
	}
	
	return oScreen || null;
};

/**
 * @param {?} CScreenView
 * 
 * @return {Object}
 */
CScreens.prototype.initViewModel = function (CScreenView)
{
	var
		oScreen = new CScreenView(),
		$templatePlace = $('<!-- ko template: { name: \'' + oScreen.ViewTemplate + '\' } --><!-- /ko -->').appendTo($('#pSevenContent .screens'))
	;

	ko.applyBindings(oScreen, $templatePlace[0]);
	
	oScreen.$viewModel = $templatePlace.next();
	oScreen.bShown = false;
	oScreen.showViewModel = function (mParams)
	{
		this.$viewModel.show();
		if (!this.bShown)
		{
			if (typeof this.onShow === 'function')
			{
				this.onShow(mParams);
			}
			
//			if (('undefined' !== typeof AfterLogicApi) && AfterLogicApi.runPluginHook)
//			{
//				if (this.__name)
//				{
//					AfterLogicApi.runPluginHook('view-model-on-show', [this.__name, this]);
//				}
//			}
			
			this.bShown = true;
		}
	};
	oScreen.hideViewModel = function ()
	{
		this.$viewModel.hide();
		if (typeof this.onHide === 'function')
		{
			this.onHide();
		}
		this.bShown = false;
	};

	if (typeof oScreen.onApplyBindings === 'function')
	{
		oScreen.onApplyBindings(oScreen.$viewModel);
	}
	
	return oScreen;
};

/**
 * @param {string} sMessage
 */
CScreens.prototype.showLoading = function (sMessage)
{
	if (this.informationScreen())
	{
		this.informationScreen().showLoading(sMessage);
	}
};

CScreens.prototype.hideLoading = function ()
{
	if (this.informationScreen())
	{
		this.informationScreen().hideLoading();
	}
};

/**
 * @param {string} sMessage
 * @param {number=} iDelay
 */
CScreens.prototype.showReport = function (sMessage, iDelay)
{
	if (this.informationScreen())
	{
		this.informationScreen().showReport(sMessage, iDelay);
	}
};

/**
 * @param {string} sMessage
 * @param {boolean=} bHtml = false
 * @param {boolean=} bNotHide = false
 * @param {boolean=} bGray = false
 */
CScreens.prototype.showError = function (sMessage, bHtml, bNotHide, bGray)
{
	if (this.informationScreen())
	{
		this.informationScreen().showError(sMessage, bHtml, bNotHide, bGray);
	}
};

/**
 * @param {boolean=} bGray = false
 */
CScreens.prototype.hideError = function (bGray)
{
	if (this.informationScreen())
	{
		this.informationScreen().hideError(bGray);
	}
};

CScreens.prototype.initInformation = function ()
{
	this.informationScreen(this.showNormalScreen('information'));
};

CScreens.prototype.initHelpdesk = function ()
{
	var oScreen = this.oItems[Enums.Screens.Helpdesk];

	if (AppData.User.IsHelpdeskSupported && oScreen && !oScreen.bInitialized)
	{
		oScreen = this.initViewModel(oScreen);
		this.oItems[Enums.Screens.Helpdesk] = oScreen;
		oScreen.bInitialized = true;
	}
};

var Screens = new CScreens();

module.exports = Screens;
