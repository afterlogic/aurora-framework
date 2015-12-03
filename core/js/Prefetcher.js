'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	
	Ajax = null,
	App = require('core/js/App.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	Settings = require('core/js/Settings.js'),
	
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