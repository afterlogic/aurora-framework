'use strict';

var
	Utils = require('core/js/utils/Common.js'),
	Popups = require('core/js/Popups.js')
;

function CAbstractPopup()
{
	this.bOpened = false;
	this.$popupDom = null;
}

CAbstractPopup.prototype.PopupTemplate = '';

CAbstractPopup.prototype.openPopup = function (aParameters)
{
	if (this.$popupDom && !this.bOpened)
	{
		this.$popupDom.show();
		
		this.bOpened = true;
		
		_.delay(_.bind(function() {
			this.$popupDom.addClass('visible');
		}, this), 50);

		Popups.addPopup(this);
		
		this.onShow.apply(this, aParameters);
	}
};

CAbstractPopup.prototype.closePopup = function ()
{
	if (this.$popupDom && this.bOpened)
	{
		this.$popupDom.hide();
		
		this.bOpened = false;
		
		this.$popupDom.removeClass('visible').hide();
		
		Popups.removePopup(this);
		
		this.onHide();
	}
};

CAbstractPopup.prototype.cancelPopup = function ()
{
	this.closePopup();
};

CAbstractPopup.prototype.onEscHandler = function (oEvent)
{
	this.cancelPopup();
};

CAbstractPopup.prototype.onEnterHandler = function ()
{
};

CAbstractPopup.prototype.onBind = function ()
{
};

CAbstractPopup.prototype.onShow = function ()
{
};

CAbstractPopup.prototype.onHide = function ()
{
};

CAbstractPopup.prototype.onRoute = function (aParams)
{
};

module.exports = CAbstractPopup;