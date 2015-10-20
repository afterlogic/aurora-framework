'use strict';

var ko = require('knockout');

function CAbstractScreenView()
{
	this.bShown = false;
	this.$viewDom = null;
	this.browserTitle = ko.observable('');
}

CAbstractScreenView.prototype.ViewTemplate = '';

CAbstractScreenView.prototype.showView = function ()
{
	if (!this.bShown)
	{
		this.$viewDom.show();
		this.bShown = true;
		this.onShow();

//			if (('undefined' !== typeof AfterLogicApi) && AfterLogicApi.runPluginHook)
//			{
//				if (this.__name)
//				{
//					AfterLogicApi.runPluginHook('view-model-on-show', [this.__name, this]);
//				}
//			}

	}
};

CAbstractScreenView.prototype.hideView = function ()
{
	if (this.bShown)
	{
		this.$viewDom.hide();
		this.bShown = false;
		this.onHide();
	}
};

CAbstractScreenView.prototype.onBind = function ()
{
};

CAbstractScreenView.prototype.onShow = function ()
{
};

CAbstractScreenView.prototype.onHide = function ()
{
};

CAbstractScreenView.prototype.onRoute = function (aParams)
{
};

module.exports = CAbstractScreenView;