
/**
 * @param {string} sName
 * @param {string} sHeaderTitle
 * @param {string} sDocumentTitle
 * @param {string} sTemplateName
 * @param {Object} oViewModelClass
 */
AfterLogicApi.addScreenToHeader = function (sName, sHeaderTitle, sDocumentTitle, sTemplateName, oViewModelClass)
{
	App.addScreenToHeader(sName, sHeaderTitle, sDocumentTitle, sTemplateName, oViewModelClass, true);
};

/**
 * @param {string} sNewDefaultTab
 */
AfterLogicApi.setDefaultTab = function (sNewDefaultTab)
{
	var bDefaultTabInEnum = !!_.find(Enums.Screens, function (sScreenInEnum) {
		return sScreenInEnum === sNewDefaultTab;
	});
	
	if (bDefaultTabInEnum)
	{
		AppData.App.DefaultTab = sNewDefaultTab;
	}
};

AfterLogicApi.aSettingsTabs = [];

/**
 * @param {Object} oViewModelClass
 */
AfterLogicApi.addSettingsTab = function (oViewModelClass)
{
	if (oViewModelClass.prototype.TabName)
	{
		Enums.SettingsTab[oViewModelClass.prototype.TabName] = oViewModelClass.prototype.TabName;
		AfterLogicApi.aSettingsTabs.push(oViewModelClass);
	}
};

/**
 * @return {Array}
 */
AfterLogicApi.getPluginsSettingsTabs = function ()
{
	return AfterLogicApi.aSettingsTabs;
};

/**
 * @param {string} sSettingName
 * 
 * @return {string}
 */
AfterLogicApi.getSetting = function (sSettingName)
{
	return AppData.App[sSettingName];
};

/**
 * @param {string} sPluginName
 * 
 * @return {string|null}
 */
AfterLogicApi.getPluginSettings = function (sPluginName)
{
	if (AppData && AppData.Plugins)
	{
		return AppData.Plugins[sPluginName];
	}
	
	return null;
};

AfterLogicApi.getAuthToken = function ()
{
	return App.Storage.getData('AuthToken');
};

AfterLogicApi.oPluginHooks = {};

/**
 * @param {string} sName
 * @param {Function} fCallback
 */
AfterLogicApi.addPluginHook = function (sName, fCallback)
{
	if (Utils.isFunc(fCallback))
	{
		if (!$.isArray(this.oPluginHooks[sName]))
		{
			this.oPluginHooks[sName] = [];
		}
		
		this.oPluginHooks[sName].push(fCallback);
	}
};

/**
 * @param {string} sName
 * @param {Array=} aArguments
 */
AfterLogicApi.runPluginHook = function (sName, aArguments)
{
	if ($.isArray(this.oPluginHooks[sName]))
	{
		aArguments = aArguments || [];
		
		_.each(this.oPluginHooks[sName], function (fCallback) {
			fCallback.apply(null, aArguments);
		});
	}
};

/**
 * @param {Object} oParameters
 * @param {Function=} fResponseHandler
 * @param {Object=} oContext
 */
AfterLogicApi.sendAjaxRequest = function (oParameters, fResponseHandler, oContext)
{
	App.Ajax.send(oParameters, fResponseHandler, oContext);
};

/**
 * @param {string} sKey
 * @param {?Object=} oValueList
 * @param {?string=} sDefaulValue
 * @param {number=} nPluralCount
 * 
 * @return {string}
 */
AfterLogicApi.i18n = Utils.i18n;

/**
 * @param {string} sRecipients
 * 
 * @return {Array}
 */
AfterLogicApi.getArrayRecipients = Utils.Address.getArrayRecipients;

/**
 * @param {string} sFullEmail
 * 
 * @return {Object}
 */
AfterLogicApi.getEmailParts = Utils.Address.getEmailParts;

/**
 * @param {string} sFullEmail
 *
 * @return {Object}
 */
AfterLogicApi.isCorrectEmail = Utils.Address.isCorrectEmail;

/**
* @param {string} sAlert
*/
AfterLogicApi.showAlertPopup = function (sAlert)
{
	App.Screens.showPopup(AlertPopup, [sAlert]);
};

/**
* @param {string} sConfirm
* @param {Function} fConfirmCallback
*/
AfterLogicApi.showConfirmPopup = function (sConfirm, fConfirmCallback)
{
	App.Screens.showPopup(ConfirmPopup, [sConfirm, fConfirmCallback]);
};

AfterLogicApi.showPopup = function (sName, aParams)
{
	App.Screens.showPopup(sName, aParams);
};

/**
* @param {string} sReport
* @param {number=} iDelay if 0 comes then report will not be closed automatically
*/
AfterLogicApi.showReport = function(sReport, iDelay)
{
	App.Screens.showReport(sReport, iDelay);
};

/**
* @param {string} sError
*/
AfterLogicApi.showError = function(sError)
{
	App.Screens.showError(sError);
};

AfterLogicApi.getPrimaryAccountData = function()
{
	var oDefault = AppData.Accounts.getDefault();
	
	return {
		'Id': oDefault.id(),
		'Email': oDefault.email(),
		'FriendlyName': oDefault.friendlyName()
	};
};

AfterLogicApi.getCurrentAccountData = function()
{
	var oDefault = AppData.Accounts.getCurrent();
	
	return {
		'Id': oDefault.id(),
		'Email': oDefault.email(),
		'FriendlyName': oDefault.friendlyName()
	};
};

/**
 * @return {boolean}
 */
AfterLogicApi.isMobile = function ()
{
	return this.getAppDataItem('IsMobile');
};

/**
 * @param {string} sParamName
 * 
 * @return {string|null}
 */

AfterLogicApi.getRequestParam = Utils.Common.getRequestParam;

AfterLogicApi.editedFolderList = function ()
{
	return App.MailCache.editedFolderList;
};

AfterLogicApi.FileSizeLimit = AppData.App.FileSizeLimit;

AfterLogicApi.isFunc = Utils.isFunc;
AfterLogicApi.isUnd = Utils.isUnd;
AfterLogicApi.emptyFunction = Utils.emptyFunction;

/**
 * @param {string} sItemName
 * 
 * @return {string}
 */
AfterLogicApi.getAppDataItem = function (sItemName)
{
	if (AppData && AppData[sItemName])
	{
		return AppData[sItemName];
	}
	
	return null;	
};

AfterLogicApi.WindowOpener = Utils.WindowOpener;

AfterLogicApi.getAppPath = Utils.Common.getAppPath;

AfterLogicApi.loadScript = Utils.loadScript;

/* jshint ignore:start */		
AfterLogicApi.createObjectInstance = function (sClassName)
{
	var 
		oReg = new RegExp('^[a-zA-Z]+$')
	; 
	if(oReg.test(sClassName))
	{
		return eval('new ' + sClassName + '()');
	}		
	
	return null;
};
/* jshint ignore:end */
