'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Types = require('core/js/utils/Types.js')
;

/**
 * @constructor
 */
function CPopups()
{
	this.popups = [];
	this.$popupsPlace = $('#pSevenContent .popups');
}

CPopups.prototype.hasOpenedMinimizedPopups = function ()
{
	var bOpenedMinimizedPopups = false;
	
	_.each(this.popups, function (oPopup) {
		if (oPopup.minimized && oPopup.minimized())
		{
			bOpenedMinimizedPopups = true;
		}
	});
	
	return bOpenedMinimizedPopups;
};

CPopups.prototype.hasOnlyOneOpenedPopup = function ()
{
	return this.popups.length === 1;
};

CPopups.prototype.hasOpenedMaximizedPopups = function ()
{
	var bOpenedMaximizedPopups = false;
	
	_.each(this.popups, function (oPopup) {
		if (!oPopup.minimized || !oPopup.minimized())
		{
			bOpenedMaximizedPopups = true;
		}
	});
	
	return bOpenedMaximizedPopups;
};

/**
 * @param {?} oPopup
 * @param {Array=} aParameters
 */
CPopups.prototype.showPopup = function (oPopup, aParameters)
{
	if (oPopup)
	{
		if (!oPopup.$popupDom && Types.isNonEmptyString(oPopup.PopupTemplate))
		{
			var $templatePlace = $('<!-- ko template: { name: \'' + oPopup.PopupTemplate + '\' } --><!-- /ko -->').appendTo(this.$popupsPlace);

			ko.applyBindings(oPopup, $templatePlace[0]);

			oPopup.$popupDom = $templatePlace.next();

			oPopup.onBind();
		}

		oPopup.openPopup(aParameters);
	}
};

/**
 * @param {Object} oPopup
 */
CPopups.prototype.addPopup = function (oPopup)
{
	this.popups.push(oPopup);

	if (this.popups.length === 1)
	{
		this.keyupPopupBinded = _.bind(this.keyupPopup, this);
		$(document).on('keyup', this.keyupPopupBinded);
	}
};

/**
 * @param {Object} oEvent
 */
CPopups.prototype.keyupPopup = function (oEvent)
{
	var oPopup = (this.popups.length > 0) ? this.popups[this.popups.length - 1] : null;
	
	if (oEvent && oPopup && (!oPopup.minimized || !oPopup.minimized()))
	{
		var iKeyCode = Types.pInt(oEvent.keyCode);
		
		if (Enums.Key.Esc === iKeyCode)
		{
			oPopup.onEscHandler(oEvent);
		}

		if ((Enums.Key.Enter === iKeyCode || Enums.Key.Space === iKeyCode))
		{
			oPopup.onEnterHandler();
		}
	}
};

/**
 * @param {?} oPopup
 */
CPopups.prototype.removePopup = function (oPopup)
{
	if (oPopup)
	{
		oPopup.closePopup();
	}
};

/**
 * @param {?} oPopup
 */
CPopups.prototype.removePopup = function (oPopup)
{
	if (this.keyupPopupBinded && this.popups.length === 1)
	{
		$(document).off('keyup', this.keyupPopupBinded);
		this.keyupPopupBinded = undefined;
	}

	this.popups = _.without(this.popups, oPopup);
};

module.exports = new CPopups();
