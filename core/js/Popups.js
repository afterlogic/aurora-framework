'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js')
;

/**
 * @constructor
 */
function CPopups()
{
	this.popups = [];
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
 * @param {?} oPopupViewModel
 * @param {Array=} aParameters
 */
CPopups.prototype.showPopup = function (oPopupViewModel, aParameters)
{
	if (oPopupViewModel)
	{
		if (!oPopupViewModel.__builded)
		{
			var
				oViewModelDom = null,
				sTemplate = oPopupViewModel.PopupTemplate || ''
			;

			if ('' !== sTemplate)
			{
				oViewModelDom = $('<!-- ko template: { name: \'' + sTemplate + '\' } --><!-- /ko -->').appendTo($('#pSevenContent .popups'));

				ko.applyBindings(oPopupViewModel, oViewModelDom[0]);
				
				oPopupViewModel.$viewModel = oViewModelDom.next();
				
				oPopupViewModel.visibility = ko.observable(false);

				oPopupViewModel.showViewModel = Utils.createCommand(oPopupViewModel, _.bind(function () {
					this.showPopup(oPopupViewModel);
				}, this));

				oPopupViewModel.closeCommand = Utils.createCommand(oPopupViewModel, _.bind(function () {
					this.hidePopup(oPopupViewModel);
				}, this));

				if ($.isFunction(oPopupViewModel.onApplyBindings))
				{
					oPopupViewModel.onApplyBindings();
				}
				
				oPopupViewModel.__builded = true;
			}
		}

		if (oPopupViewModel && oPopupViewModel.$viewModel)
		{
			if (!oPopupViewModel.visibility())
			{
				oPopupViewModel.$viewModel.show();
				_.delay(function() {
					oPopupViewModel.$viewModel.addClass('visible');
				}, 50);
				oPopupViewModel.visibility(true);

				this.popups.push(oPopupViewModel);

				if (this.popups.length === 1)
				{
					this.keyupPopupBinded = _.bind(this.keyupPopup, this);
					$(document).on('keyup', this.keyupPopupBinded);
				}
			}
			
			if ($.isFunction(oPopupViewModel.onShow))
			{
				oPopupViewModel.onShow.apply(oPopupViewModel, aParameters);
			}
		}
	}
};

/**
 * @param {Object} oEvent
 */
CPopups.prototype.keyupPopup = function (oEvent)
{
	var oViewModel = (this.popups.length > 0) ? this.popups[this.popups.length - 1] : null;
	
	if (oEvent && oViewModel && (!oViewModel.minimized || !oViewModel.minimized()))
	{
		var iKeyCode = window.parseInt(oEvent.keyCode, 10);
		if (Enums.Key.Esc === iKeyCode)
		{
			if (oViewModel.onEscHandler)
			{
				oViewModel.onEscHandler(oEvent);
			}
			else
			{
				oViewModel.closeCommand();
			}
		}

		if ((Enums.Key.Enter === iKeyCode || Enums.Key.Space === iKeyCode) && oViewModel.onEnterHandler)
		{
			oViewModel.onEnterHandler();
		}
	}
};

/**
 * @param {?} oPopupViewModel
 */
CPopups.prototype.hidePopup = function (oPopupViewModel)
{
	if (oPopupViewModel && oPopupViewModel.$viewModel)
	{
		if (this.keyupPopupBinded && this.popups.length === 1)
		{
			$(document).off('keyup', this.keyupPopupBinded);
			this.keyupPopupBinded = undefined;
		}
		
		oPopupViewModel.$viewModel.removeClass('visible').hide();

		oPopupViewModel.visibility(false);
		
		if ($.isFunction(oPopupViewModel.onHide))
		{
			oPopupViewModel.onHide();
		}
		
		this.popups = _.without(this.popups, oPopupViewModel);
	}
};

module.exports = new CPopups();