'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	Popups = require('core/js/Popups.js')
;

function CAbstractPopup()
{
	this.opened = ko.observable(false);
	this.$popupDom = null;
}

CAbstractPopup.prototype.PopupTemplate = '';

CAbstractPopup.prototype.openPopup = function (aParameters)
{
	if (this.$popupDom && !this.opened())
	{
		this.$popupDom.show();
		
		this.opened(true);
		
		_.delay(_.bind(function() {
			this.$popupDom.addClass('visible');
		}, this), 50);

		Popups.addPopup(this);
	}
		
	this.onShow.apply(this, aParameters);
};

CAbstractPopup.prototype.closePopup = function ()
{
	if (this.$popupDom && this.opened())
	{
		this.$popupDom.hide();
		
		this.opened(false);
		
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