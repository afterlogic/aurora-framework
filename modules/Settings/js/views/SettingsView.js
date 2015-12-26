'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Routing = require('core/js/Routing.js'),
	CAbstractScreenView = require('core/js/views/CAbstractScreenView.js'),
	
	Settings = require('modules/Settings/js/Settings.js'),
	
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

CSettingsView.prototype.ViewTemplate = 'Settings_SettingsView';

CSettingsView.prototype.registerTab = function (oTabView, oTabName, oTabTitle) {
	var iLastIndex = Settings.TabsOrder.length;
	
	this.tabs.push({
		view: oTabView,
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
CSettingsView.prototype.onRoute = function (aParams) {
	var
		sNewTabName = aParams.shift(),
		oCurrentTab = this.currentTab(),
		oNewTab = _.find(this.tabs(), function (oTab) {
			return oTab.name === sNewTabName;
		}),
		fShowNewTab = function () {
			if (oNewTab)
			{
				oNewTab.view.show(aParams);
				this.currentTab(oNewTab);
			}
		}.bind(this),
		fRevertRouting = _.bind(function () {
			if (oCurrentTab)
			{
				Routing.replaceHashDirectly(['settings', oCurrentTab.name]);
			}
		}, this),
		bShow = true
	;
	
	if (oNewTab)
	{
		if (oCurrentTab)
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
	}
	
	if (bShow)
	{
		fShowNewTab();
	}
};

CSettingsView.prototype.changeTab = function (sTabName) {
	Routing.setHash(['settings', sTabName]);
};

module.exports = new CSettingsView();
