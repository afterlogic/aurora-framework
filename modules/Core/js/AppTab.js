var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	App = require('modules/Core/js/App.js'),
	Browser = require('modules/Core/js/Browser.js'),
	CFavico = require('modules/Core/js/vendors/favico.js'),
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Screens = require('modules/Core/js/Screens.js'),
	UserSettings = require('modules/Core/js/Settings.js')
;

function CAppTab()
{
	this.tabs = ModulesManager.getModulesTabs(true);
	
	this.focused = ko.observable(true);
	
	ko.computed(function () {
		var sTitle = '';
		
		if (!App.isNewTab() && !this.focused())
		{
			sTitle = this.getInactiveTitle();
		}
		
		if (sTitle === '')
		{
			sTitle = Screens.browserTitle();
		}
		
		this.setTitle(sTitle);
	}, this);
	
	this.favico = (!Browser.ie8AndBelow && CFavico) ? new CFavico({
		'animation': 'none'
	}) : null;
}

CAppTab.prototype.init = function ()
{
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
};

/**
 * @param {string} sTitle
 */
CAppTab.prototype.setTitle = function (sTitle)
{
	if (sTitle === '')
	{
		sTitle = UserSettings.SiteName;
	}
	else
	{
		sTitle += (UserSettings.SiteName !== '') ? ' - ' + UserSettings.SiteName : '';
	}
	
	document.title = '.';
	document.title = sTitle;
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

var AppTab = new CAppTab();

AppTab.init();

module.exports = {
	focused: AppTab.focused
};
