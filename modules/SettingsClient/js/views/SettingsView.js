'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Routing = require('modules/CoreClient/js/Routing.js'),
	CAbstractScreenView = require('modules/CoreClient/js/views/CAbstractScreenView.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js'),
	
	$html = $('html')
;

/**
 * @constructor
 */
function CSettingsView()
{
	CAbstractScreenView.call(this);
	
	this.tabs = ko.observableArray([]);
	
	this.currentTab  = ko.observable(null);
}

_.extendOwn(CSettingsView.prototype, CAbstractScreenView.prototype);

CSettingsView.prototype.ViewTemplate = '%ModuleName%_SettingsView';

CSettingsView.prototype.registerTab = function (fGetTabView, oTabName, oTabTitle) {
	var iLastIndex = Settings.TabsOrder.length;
	
	this.tabs.push({
		view: null,
		getView: fGetTabView,
		name: oTabName,
		title: oTabTitle
	});
	
	this.tabs(_.sortBy(this.tabs(), function (oTab) {
		var iIndex = _.indexOf(Settings.TabsOrder, oTab.name);
		return iIndex !== -1 ? iIndex : iLastIndex;
	}));
};

CSettingsView.prototype.onShow = function ()
{
	$html.addClass('non-adjustable');
};

CSettingsView.prototype.onHide = function ()
{
	$html.removeClass('non-adjustable');
};

/**
 * @param {Array} aParams
 */
CSettingsView.prototype.onRoute = function (aParams)
{
	var
		sNewTabName = aParams.shift(),
		oCurrentTab = this.currentTab(),
		oNewTab = _.find(this.tabs(), function (oTab) {
			return oTab.name === sNewTabName;
		}),
		fShowNewTab = function () {
			if (oNewTab)
			{
				if (!oNewTab.view && $.isFunction(oNewTab.getView))
				{
					oNewTab.view = oNewTab.getView();
					oNewTab.getView = undefined;
				}
				if ($.isFunction(oNewTab.view.onRoute))
				{
					oNewTab.view.onRoute(aParams);
				}
				this.currentTab(oNewTab);
			}
		}.bind(this),
		fRevertRouting = _.bind(function () {
			if (oCurrentTab)
			{
				Routing.replaceHashDirectly([Settings.HashModuleName, oCurrentTab.name]);
			}
		}, this),
		bShow = true
	;
	
	if (oNewTab)
	{
		if (oCurrentTab && $.isFunction(oCurrentTab.view.hide))
		{
			oCurrentTab.view.hide(fShowNewTab, fRevertRouting);
			bShow = false;
		}
	}
	else if (!oCurrentTab)
	{
		oNewTab = _.find(this.tabs(), function (oTab) {
			return oTab.name === 'common';
		});
		
		if (!oNewTab)
		{
			oNewTab = _.find(this.tabs(), function (oTab) {
				return !oTab.visible || oTab.visible();
			});
		}
	}
	
	if (bShow)
	{
		fShowNewTab();
	}
};

/**
 * @param {string} sTabName
 */
CSettingsView.prototype.changeTab = function (sTabName)
{
	Routing.setHash([Settings.HashModuleName, sTabName]);
};

/**
 * @param {Array} aAddHash
 */
CSettingsView.prototype.setAddHash = function (aAddHash)
{
	Routing.setHash(_.union([Settings.HashModuleName, this.currentTab() ? this.currentTab().name : ''], aAddHash));
};

module.exports = new CSettingsView();
