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
	
	getModulesTabs: function (bOnlyStandard) {
		if (!_.isArray(this.aTabs))
		{
			this.aTabs = [];
			this.aStandardTabs = [];
			_.each(oModules, _.bind(function (oModule, sName) {
				if ($.isFunction(oModule.getHeaderItem))
				{
					var oHeaderItem = oModule.getHeaderItem();
					if (oHeaderItem)
					{
						if ($.isFunction(oHeaderItem.setName))
						{
							oHeaderItem.setName(sName);
							this.aStandardTabs.push(oHeaderItem);
						}
						this.aTabs.push(oHeaderItem);
					}
				}
			}, this));
		}
		
		return bOnlyStandard ? this.aStandardTabs : this.aTabs;
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
			
			return oModule[sFunctionName].apply(oModule, aParams);
		}
		
		return false;
	}
};