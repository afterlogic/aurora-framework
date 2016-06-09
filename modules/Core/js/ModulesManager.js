'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	
	Settings = require('modules/Core/js/Settings.js'),
	
	AppData = window.auroraAppData,
	
	oModules = {}
;

module.exports = {
	init: function (oAvaliableModules, iUserRole, bPublic) {
		_.each(oAvaliableModules, function (fModuleConstructor, sModuleName) {
			if (_.indexOf(AppData.DisabledModules, sModuleName) === -1 && _.isFunction(fModuleConstructor))
			{
				var oModule = fModuleConstructor(AppData);
				if (oModule.isAvailable(iUserRole, bPublic))
				{
					oModules[sModuleName] = oModule;
				}
			}
		});
		
		if (Settings.AllowChangeSettings)
		{
			this.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/Core/js/views/CommonSettingsPaneView.js'); }, 'common', TextUtils.i18n('CORE/LABEL_COMMON_SETTINGS_TABNAME')]);
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
			if (_.isFunction(oModule.getScreens))
			{
				oModulesScreens[sModuleName] = oModule.getScreens();
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
				if ($.isFunction(oModule.getHeaderItem))
				{
					var oHeaderItem = oModule.getHeaderItem();
					if (oHeaderItem && oHeaderItem.item)
					{
						if ($.isFunction(oHeaderItem.item.setName))
						{
							oHeaderItem.item.setName(oHeaderItem.name || sModuleName);
							this.aStandardTabs.push(oHeaderItem.item);
						}
						this.aTabs.push(oHeaderItem.item);
						
						if (oModules[sModuleName] && oModules[sModuleName].enableModule)
						{
							oHeaderItem.item.visible(oModules[sModuleName].enableModule());
							oModules[sModuleName].enableModule.subscribe(function (bEnableModule) {
								oHeaderItem.item.visible(bEnableModule);
							});
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
	
	isModuleEnabled: function (sModuleName)
	{
		return oModules[sModuleName] && (!oModules[sModuleName].enableModule || oModules[sModuleName].enableModule());
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
