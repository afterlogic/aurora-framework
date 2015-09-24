'use strict';

function CAbstractView()
{
	this.bShown = false;
	this.$viewDom = null;
}

CAbstractView.prototype.showView = function ()
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

CAbstractView.prototype.hideView = function ()
{
	if (this.bShown)
	{
		this.$viewDom.hide();
		this.bShown = false;
		this.onHide();
	}
};

CAbstractView.prototype.onBind = function ()
{
};

CAbstractView.prototype.onShow = function ()
{
};

CAbstractView.prototype.onHide = function ()
{
};

CAbstractView.prototype.onRoute = function (aParams)
{
};

module.exports = CAbstractView;