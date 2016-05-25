'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Types = require('modules/Core/js/utils/Types.js'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Settings = require('modules/Core/js/Settings.js')
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
	
	this.oGetScreenFunctions = {};
	this.oScreens = {};
	this.oModulesNames = {};

	this.currentScreen = ko.observable('');
	this.sDefaultScreen = '';
	
	this.browserTitle = ko.computed(function () {
		var oCurrScreen = this.oScreens[this.currentScreen()];
		return oCurrScreen ? oCurrScreen.browserTitle() : '';
	}, this);

	this.informationScreen = ko.observable(null);
}

CScreens.prototype.init = function ()
{
	var
		oModulesScreens = ModulesManager.getModulesScreens(),
		oModulesTabs = ModulesManager.getModulesTabs(false),
		aKeys = []
	;
	
	_.each(oModulesScreens, _.bind(function (oScreenList, sModuleName) {
		this.addToScreenList(sModuleName, oScreenList);
	}, this));
	
	this.addToScreenList('', require('modules/Core/js/screenList.js'));
	
	if (this.oGetScreenFunctions[Settings.EntryModule.toLowerCase()])
	{
		this.sDefaultScreen = Settings.EntryModule.toLowerCase();
	}
	
	if (this.sDefaultScreen === '')
	{
		aKeys = _.keys(this.oGetScreenFunctions);
		if (Types.isNonEmptyArray(aKeys))
		{
			this.sDefaultScreen = aKeys[0];
		}
	}
	
	if (oModulesTabs.length > 0)
	{
		this.showView('header');
	}
	
	this.initInformation();
};

/**
 * @param {string} sModuleName
 * @param {Object} oScreenList
 */
CScreens.prototype.addToScreenList = function (sModuleName, oScreenList)
{
	_.each(oScreenList, _.bind(function (fGetScreen, sKey) {
		var sNewKey = sKey.toLowerCase();
		if (sModuleName !== '')
		{
			if (sKey === 'main')
			{
				sNewKey = sModuleName.toLowerCase();
			}
			else
			{
				sNewKey = sModuleName.toLowerCase() + '-' + sKey;
			}
		}
		
		this.oGetScreenFunctions[sNewKey] = fGetScreen;
		this.oModulesNames[sNewKey] = sModuleName;
	}, this));
};

/**
 * @param {string} sScreen
 * 
 * @returns {boolean}
 */
CScreens.prototype.hasScreenData = function (sScreen)
{
	return !!(this.oScreens[sScreen] || this.oGetScreenFunctions[sScreen]);
};

/**
 * @param {Array} aParams
 */
CScreens.prototype.route = function (aParams)
{
	var
		sCurrentScreen = this.currentScreen(),
		oCurrentScreen = this.oScreens[sCurrentScreen],
		sNextScreen = aParams.shift()
	;
	
	if ((sNextScreen === '' || !this.hasScreenData(sNextScreen)) && sCurrentScreen === '')
	{
		sNextScreen = this.sDefaultScreen;
	}
	
	if (ModulesManager.isModuleEnabled(this.oModulesNames[sNextScreen]) && this.hasScreenData(sNextScreen))
	{
		if (sCurrentScreen !== sNextScreen)
		{
			if (oCurrentScreen)
			{
				oCurrentScreen.hideView();
			}
			
			oCurrentScreen = this.showView(sNextScreen);
		}
		
		if (oCurrentScreen)
		{
			this.currentScreen(sNextScreen);
			oCurrentScreen.onRoute(aParams);
		}
	}
};

/**
 * @param {string} sScreen
 * 
 * @returns {Object}
 */
CScreens.prototype.showView = function (sScreen)
{
	var
		sScreenId = sScreen,
		fGetScreen = this.oGetScreenFunctions[sScreenId],
		oScreen = this.oScreens[sScreenId]
	;
	
	if (!oScreen && fGetScreen)
	{
		oScreen = this.initView(sScreenId, fGetScreen);
	}
	
	if (oScreen)
	{
		oScreen.showView();
	}
	
	return oScreen;
};

/**
 * @param {string} sScreenId
 * @param {function} fGetScreen
 * 
 * @returns {Object}
 */
CScreens.prototype.initView = function (sScreenId, fGetScreen)
{
	var oScreen = fGetScreen();
	
	if (oScreen.ViewTemplate)
	{
		var $templatePlace = $('<!-- ko template: { name: \'' + oScreen.ViewTemplate + '\' } --><!-- /ko -->').appendTo($('#auroraContent .screens'));
		if ($templatePlace.length > 0)
		{
			ko.applyBindings(oScreen, $templatePlace[0]);

			oScreen.$viewDom = $templatePlace.next();

			oScreen.onBind();
		}
	}
	
	this.oScreens[sScreenId] = oScreen;
	delete this.oGetScreenFunctions[sScreenId];
	
	return oScreen;
};

/**
 * @param {Object} oView
 */
CScreens.prototype.showAnyView = function (oView)
{
	if (oView.ViewTemplate)
	{
		var $templatePlace = $('<!-- ko template: { name: \'' + oView.ViewTemplate + '\' } --><!-- /ko -->').appendTo($('#auroraContent .screens'));
		if ($templatePlace.length > 0)
		{
			ko.applyBindings(oView, $templatePlace[0]);
		}
	}
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
	this.informationScreen(this.showView('information'));
};

var Screens = new CScreens();

module.exports = Screens;
