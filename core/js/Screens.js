'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout')
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

	this.informationScreen = ko.observable(null);
}

CScreens.prototype.addToScreenList = function (sPrefix, oScreenList) {
	_.each(oScreenList, _.bind(function (oScreen, sKey) {
		var sNewKey = sKey;
		if (sPrefix !== '')
		{
			if (sKey === 'main')
			{
				sNewKey = sPrefix;
			}
			else
			{
				sNewKey = sPrefix + '-' + sKey;
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
		oCurrentModel = (typeof oCurrentScreen !== 'undefined') ? oCurrentScreen.Model : null,
		sScreen = aParams.shift()
	;
	
	if (this.oItems[sScreen])
	{
		if (sCurrentScreen === sScreen)
		{
			if (oCurrentModel && oCurrentScreen.bInitialized && $.isFunction(oCurrentModel.onRoute))
			{
				oCurrentModel.onRoute(aParams);
			}
		}
		else
		{
			if (oCurrentModel && oCurrentScreen.bInitialized)
			{
				oCurrentModel.hideViewModel();
			}
			
			this.showNormalScreen(sScreen, aParams);
			
			oCurrentScreen = this.oItems[sScreen];
			oCurrentModel = (typeof oCurrentScreen !== 'undefined') ? oCurrentScreen.Model : null;
			if (oCurrentModel && oCurrentScreen.bInitialized)
			{
				this.currentScreen(sScreen);
				if ($.isFunction(oCurrentModel.onRoute))
				{
					oCurrentModel.onRoute(aParams);
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

CScreens.prototype.getCurrentScreenModel = function ()
{
	var
		oCurrentScreen = this.oItems[this.currentScreen()],
		oCurrentModel = (typeof oCurrentScreen !== 'undefined') ? oCurrentScreen.Model : null
	;
	
	return oCurrentModel;
};

/**
 * @param {string} sScreen
 * @param {?=} mParams
 */
CScreens.prototype.showCurrentScreen = function (sScreen, mParams)
{
	var
		oCurrentScreen = this.oItems[this.currentScreen()],
		oCurrentModel = (typeof oCurrentScreen !== 'undefined') ? oCurrentScreen.Model : null
	;
	
	if (this.currentScreen() !== sScreen)
	{
		if (oCurrentModel && oCurrentScreen.bInitialized)
		{
			oCurrentModel.hideViewModel();
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
			oScreen.Model = this.initViewModel(oScreen.Model, oScreen.TemplateName);
			oScreen.bInitialized = true;
		}

		oScreen.Model.showViewModel(mParams);
	}
	
	return oScreen ? oScreen.Model : null;
};

/**
 * @param {?} CViewModel
 * @param {string} sTemplateId
 * 
 * @return {Object}
 */
CScreens.prototype.initViewModel = function (CViewModel, sTemplateId)
{
	var
		oViewModel = null,
		$viewModel = null
	;

	oViewModel = new CViewModel();
	
	$viewModel = $('<!-- ko template: { name: \'' + sTemplateId + '\' } --><!-- /ko -->').appendTo($('#pSevenContent .screens'));
	
	ko.applyBindings(oViewModel, $viewModel[0]);
	
	oViewModel.$viewModel = $viewModel.next();
	oViewModel.bShown = false;
	oViewModel.showViewModel = function (mParams)
	{
		this.$viewModel.show();
		if (!this.bShown)
		{
			if (typeof this.onShow === 'function')
			{
				this.onShow(mParams);
			}
			
			if (('undefined' !== typeof AfterLogicApi) && AfterLogicApi.runPluginHook)
			{
				if (this.__name)
				{
					AfterLogicApi.runPluginHook('view-model-on-show', [this.__name, this]);
				}
			}
			
			this.bShown = true;
		}
	};
	oViewModel.hideViewModel = function ()
	{
		this.$viewModel.hide();
		if (typeof this.onHide === 'function')
		{
			this.onHide();
		}
		this.bShown = false;
	};

	if (typeof oViewModel.onApplyBindings === 'function')
	{
		oViewModel.onApplyBindings($viewModel);
	}
	
	return oViewModel;
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
		oScreen.Model = this.initViewModel(oScreen.Model, oScreen.TemplateName);
		oScreen.bInitialized = true;
	}
};

var Screens = new CScreens();

module.exports = Screens;
