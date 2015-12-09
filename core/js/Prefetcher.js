'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Ajax = require('core/js/Ajax.js'),
	App = require('core/js/App.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	Settings = require('core/js/Settings.js'),
	
	ModulesPrefetchers = ModulesManager.getModulesPrefetchers(),
	Prefetcher = {},
	bServerInitializationsDone = false
;

Prefetcher.start = function ()
{
	if (App.isAuth() && !App.isNewTab() && !Ajax.hasInternetConnectionProblem() && !Ajax.hasOpenedRequests())
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
			if (Settings.AllowPrefetch && $.isFunction(oModulePrefetcher.startAll))
			{
				bPrefetchStarted = oModulePrefetcher.startAll();
			}
			else if ($.isFunction(oModulePrefetcher.startMin))
			{
				bPrefetchStarted = oModulePrefetcher.startMin();
			}
		}
	});
};

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
	if (!App.isNewTab() && !bServerInitializationsDone)
	{
		Ajax.send('Core', 'DoServerInitializations', null);
		bServerInitializationsDone = true;
		
		return true;
	}
	return false;
};

Ajax.registerOnAllRequestsClosedHandler(function () {
	Prefetcher.start();
});
