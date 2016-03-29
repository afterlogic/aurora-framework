'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	Settings = require('modules/Core/js/Settings.js'),
	
	oModules = {},
	ShowOnlyAuthModule = false
;

function CanModuleBeDisplayed(sModuleName)
{
	return ShowOnlyAuthModule ? sModuleName === 'Auth' : sModuleName !== 'Auth';
}

module.exports = {
	init: function (oAvaliableModules, bShowOnlyAuthModule) {
		if (!_.isUndefined(bShowOnlyAuthModule))
		{
			ShowOnlyAuthModule = bShowOnlyAuthModule;
		}
		
		_.each(Settings.Modules, function (oModuleSettings, sModuleName) {
			if ($.isFunction(oAvaliableModules[sModuleName]))
			{
				oModules[sModuleName] = oAvaliableModules[sModuleName](oModuleSettings);
			}
		});
		
		if (Settings.AllowChangeSettings)
		{
			this.run('Settings', 'registerSettingsTab', [function () { return require('modules/Core/js/views/CommonSettingsPaneView.js'); }, 'common', TextUtils.i18n('CORE/LABEL_COMMON_SETTINGS_TABNAME')]);
		}
		
		_.each(oModules, _.bind(function (oModule) {
			if ($.isFunction(oModule.start))
			{
				oModule.start(this);
			}
		}, this));
	},
	
	getModulesScreens: function () {
		var oModulesScreens = {};
		
		_.each(oModules, function (oModule, sModuleName) {
			if (!!oModule.screens && CanModuleBeDisplayed(sModuleName))
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
			_.each(oModules, _.bind(function (oModule, sModuleName) {
				if (CanModuleBeDisplayed(sModuleName))
				{
					if ($.isFunction(oModule.getHeaderItem))
					{
						var oHeaderItem = oModule.getHeaderItem();
						if (oHeaderItem)
						{
							if ($.isFunction(oHeaderItem.setName))
							{
								oHeaderItem.setName(sModuleName);
								this.aStandardTabs.push(oHeaderItem);
							}
							this.aTabs.push(oHeaderItem);
						}
					}
				}
			}, this));
		}
		
		return bOnlyStandard ? this.aStandardTabs : this.aTabs;
	},
	
	getModulesPrefetchers: function ()
	{
		var aPrefetchers = [];

		_.each(oModules, function (oModule, sModuleName) {
			if ($.isFunction(oModule.getPrefetcher))
			{
				aPrefetchers.push(oModule.getPrefetcher());
			}
		});

		return aPrefetchers;
	},
	
	isModuleIncluded: function (sModuleName)
	{
		return oModules[sModuleName] !== undefined;
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