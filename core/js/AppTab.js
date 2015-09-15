var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	CFavico = require('core/js/vendors/favico.js'),
	
	App = require('core/js/App.js'),
	Browser = require('core/js/Browser.js'),
	Settings = require('core/js/Settings.js'),
	
	bSingleMode = false
;

function CAppTab()
{
	this.tabs = App.getModulesTabs();
	
	this.focused = ko.observable(true);
	
	this.favico = (!Browser.ie8AndBelow && CFavico) ? new CFavico({
		'animation': 'none'
	}) : null;
}

CAppTab.prototype.init = function ()
{
	this.focused.subscribe(function() {
		if (!bSingleMode)
		{
			this.change();
		}
	}, this);
	
	_.each(this.tabs, _.bind(function (oTab) {
		
		oTab.activeTitle.subscribe(function () {
			if (this.focused() && oTab.isCurrent())
			{
				this.change();
			}
		}, this);
		
		oTab.isCurrent.subscribe(function () {
			if (this.focused() && oTab.isCurrent())
			{
				this.change();
			}
		}, this);
		
		if (!bSingleMode && oTab.allowChangeTitle())
		{
			oTab.inactiveTitle.subscribe(function () {
				if (!this.focused())
				{
					this.change();
				}
			}, this);
		}
	}, this));
	
	if (Browser.ie)
	{
		$(document)
			.bind('focusin', _.bind(this.focused, this, true))
			.bind('focusout', _.bind(this.focused, this, false))
		;
	}
	else
	{
		$(window)
			.bind('focus', _.bind(this.focused, this, true))
			.bind('blur', _.bind(this.focused, this, false))
		;
	}
	
	if (this.favico)
	{
		ko.computed(function () {
			var iCount = 0;
			_.each(this.tabs, function (oTab) {
				if (oTab.allowChangeTitle())
				{
					iCount += oTab.unseenCount();
				}
			});
			this.favico.badge(iCount < 100 ? iCount : '99+');
		}, this);
	}
	
	this.change();
};

CAppTab.prototype.change = function ()
{
	var sTitle = (bSingleMode || this.focused()) ? this.getActiveTitle() : this.getInactiveTitle();
	
	if (sTitle === '')
	{
		sTitle = Settings.SiteName;
	}
	else
	{
		sTitle += (Settings.SiteName !== '') ? ' - ' + Settings.SiteName : '';
	}
	
	document.title = '.';
	document.title = sTitle;
};

CAppTab.prototype.getActiveTitle = function ()
{
	var oCurrentTab = _.find(this.tabs, function (oTab) {
		return oTab.isCurrent();
	});
	return (oCurrentTab) ? oCurrentTab.activeTitle() : '';
};

CAppTab.prototype.getInactiveTitle = function ()
{
	var
		sTitle = '',
		iCount = 0
	;

	_.each(this.tabs, function (oTab) {
		if (oTab.allowChangeTitle())
		{
			iCount += oTab.unseenCount();
			if (oTab.unseenCount() > 0 && iCount === oTab.unseenCount())
			{
				sTitle = oTab.inactiveTitle();
			}
			else
			{
				sTitle = '';
			}
		}
	});

	if (iCount > 0 && sTitle === '')
	{
		sTitle = iCount + ' new';
	}
	
	return sTitle;
};

/**
 * @param {string} sFaviconUrl
 */
CAppTab.prototype.changeFavicon = function (sFaviconUrl)
{
	$('head').append('<link rel="shortcut icon" type="image/x-icon" href=' + sFaviconUrl + ' />');
};

var AppTab = new CAppTab();

AppTab.init();

module.exports = {
	focused: AppTab.focused,
	changeFavicon: _.bind(AppTab.changeFavicon, AppTab)
};
