'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	App = require('modules/CoreClient/js/App.js'),
	ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
	
	Settings = require('modules/CoreClient/js/Settings.js'),
	
	ModulesPrefetchers = ModulesManager.getModulesPrefetchers(),
	Prefetcher = {},
	bServerInitializationsDone = false
;

Prefetcher.start = function ()
{
	if (App.getUserRole() !== Enums.UserRole.Anonymous && !App.isNewTab() && !Ajax.hasInternetConnectionProblem() && !Ajax.hasOpenedRequests())
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

Prefetcher.doServerInitializations = function ()
{
	if (App.getUserRole() !== Enums.UserRole.Anonymous && !App.isNewTab() && !App.isPublic() && !bServerInitializationsDone)
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
