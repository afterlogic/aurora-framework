'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	
	Ajax = null,
	App = require('core/js/App.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	Settings = require('core/js/Settings.js'),
	
	bSingleMode = false,
	
	ModulesPrefetchers = ModulesManager.getModulesPrefetchers(),
	Prefetcher = {},
	bServerInitializationsDone = false
;

Prefetcher.requireAjax = function ()
{
	if (Ajax === null)
	{
		Ajax = require('core/js/Ajax.js');
		Ajax.registerOnAllRequestsClosedHandler(function () {
			Prefetcher.start();
		});
	}
};

Prefetcher.start = function ()
{
	Prefetcher.requireAjax();
	
	if (App.isAuth() && !bSingleMode && !Ajax.hasInternetConnectionProblem() && !Ajax.hasOpenedRequests())
	{
		Prefetcher.prefetchAll();
	}
};

Prefetcher.prefetchAll = function ()
{
	var bPrefetchStarted = this.doServerInitializations();
	
	_.each(ModulesPrefetchers, function (oModulePrefetcher) {
		if (!bPrefetchStarted)
		{
			if (Settings.AllowPrefetch)
			{
				if ($.isFunction(oModulePrefetcher.startAll))
				{
					bPrefetchStarted = oModulePrefetcher.startAll();
				}
			}
			else
			{
				if ($.isFunction(oModulePrefetcher.startMin))
				{
					bPrefetchStarted = oModulePrefetcher.startMin();
				}
			}
		}
	});
};

//Prefetcher.prefetchCalendarList = function ()
//{
//	return App.CalendarCache.firstRequestCalendarList());
//};
//Prefetcher.initHelpdesk = function ()
//{
//	if (AppData.User.IsHelpdeskSupported && !this.helpdeskInitialized())
//	{
//		App.Screens.initHelpdesk();
//		this.helpdeskInitialized(true);
//		return true;
//	}
//	return false;
//};

Prefetcher.doServerInitializations = function ()
{
	if (!bSingleMode && !bServerInitializationsDone)
	{
		Prefetcher.requireAjax();
		Ajax.send({'Action': 'SystemDoServerInitializations'});
		bServerInitializationsDone = true;
		
		return true;
	}
	return false;
};

setInterval(_.bind(function () {
	Prefetcher.start();
}, this), 30000);

module.exports = {
	start: function () {
		Prefetcher.start();
	}
};