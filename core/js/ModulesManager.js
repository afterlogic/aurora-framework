'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Settings = require('core/js/Settings.js'),
	
	oModules = {}
;

module.exports = {
	init: function (oAvaliableModules) {
		_.each(Settings.Modules, function (oModuleSettings, sModuleName) {
			if ($.isFunction(oAvaliableModules[sModuleName]))
			{
				oModules[sModuleName] = oAvaliableModules[sModuleName](oModuleSettings);
			}
		});
	},
	
	getModulesScreens: function (bAuth) {
		var oModulesScreens = {};
		
		_.each(oModules, function (oModule, sModuleName) {
			if (!!oModule.screens && (bAuth ? sModuleName !== 'Auth' : sModuleName === 'Auth'))
			{
				oModulesScreens[sModuleName] = oModule.screens;
			}
		});
		
		return oModulesScreens;
	},
	
	getModulesTabs: function () {
		if (!_.isArray(this.aTabs))
		{
			this.aTabs = [];
			_.each(oModules, _.bind(function (oModule, sName) {
				if (oModule.headerItem)
				{
					oModule.headerItem.setName(sName);
					this.aTabs.push(oModule.headerItem);
				}
			}, this));
		}
		
		return this.aTabs;
	},
	
	getModulesPrefetchers: function ()
	{
		var aPrefetchers = [];

		_.each(oModules, function (oModule, sName) {
			if (oModule.prefetcher)
			{
				aPrefetchers.push(oModule.prefetcher);
			}
		});

		return aPrefetchers;
	},
	
	isModuleIncluded: function (sName)
	{
		return oModules[sName] !== undefined;
	},
	
	run: function (sModuleName, sFunctionName, aParams)
	{
		var oModule = oModules[sModuleName];
		
		if (oModule && $.isFunction(oModule[sFunctionName]))
		{
			if (!_.isArray(aParams))
			{
				aParams = [];
			}
			
			return oModule[sFunctionName].apply(oModule, aParams)
		}
		
		return false;
	}
};