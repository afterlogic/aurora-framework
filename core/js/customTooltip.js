'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js')
;


var CustomTooltip = {
	_$Region: null,
	_$ArrowTop: null,
	_$Text: null,
	_$ArrowBottom: null,
	_iArrowBorderLeft: 0,
	_iArrowMarginLeft: 0,
	_iLeftShift: 0,
	_bInitialized: false,
	_bShown: false,
	
	iHideTimer: 0,
	iTimer: 0,
	
	init: function ()
	{
		if (!this._bInitialized)
		{
			this._$Region = $('<span class="custom_tooltip"></span>').appendTo('body').hide();
			this._$ArrowTop = $('<span class="custom_tooltip_arrow top"></span>').appendTo(this._$Region);
			this._$Text = $('<span class="custom_tooltip_text"></span>').appendTo(this._$Region);
			this._$ArrowBottom = $('<span class="custom_tooltip_arrow bottom"></span>').appendTo(this._$Region);
			
			this._iArrowMarginLeft = Utils.pInt(this._$ArrowTop.css('margin-left'));
			this._iArrowBorderLeft = Utils.pInt(this._$ArrowTop.css('border-left-width'));
			this._iLeftShift = Utils.pInt(this._$Region.css('margin-left')) + this._iArrowMarginLeft + this._iArrowBorderLeft;
			
			this._bInitialized = true;
		}
		
		this._$ArrowTop.show();
		this._$ArrowBottom.hide();
		this._$ArrowTop.css({
			'margin-left': this._iArrowMarginLeft + 'px'
		});
		this._$ArrowBottom.css({
			'margin-left': this._iArrowMarginLeft + 'px'
		});
	},
	
	show: function (sText, $ItemToAlign)
	{
		this.init();
		
		var
			oItemOffset = $ItemToAlign.offset(),
			iItemWidth = $ItemToAlign.width(),
			iItemHalfWidth = (iItemWidth < 70) ? iItemWidth/2 : iItemWidth/4,
			iItemPaddingLeft = Utils.pInt($ItemToAlign.css('padding-left')),
			jqBody = $('body')
		;
		
		this._$Text.html(sText);
		this._bShown = true;
		this._$Region.stop().fadeIn(260, _.bind(function () {
			if (!this._bShown)
			{
				this._$Region.hide();
			}
		}, this)).css({
			'top': oItemOffset.top + $ItemToAlign.outerHeight() + 1,
			'left': oItemOffset.left + iItemPaddingLeft + iItemHalfWidth - this._iLeftShift,
			'right': 'auto'
		});
		
		if (jqBody.outerHeight() < this._$Region.outerHeight() + this._$Region.offset().top)
		{
			this._$ArrowTop.hide();
			this._$ArrowBottom.show();
			this._$Region.css({
				'top': oItemOffset.top - this._$Region.outerHeight()
			});
		}

		setTimeout(function () {
			if (jqBody.width() < (this._$Region.outerWidth(true) + this._$Region.offset().left))
			{
				this._$Region.css({
					'left': 'auto',
					'right': 0
				});
				this._$ArrowTop.css({
					'margin-left': (iItemHalfWidth + oItemOffset.left - this._$Region.offset().left - this._iArrowBorderLeft) + 'px'
				});
				this._$ArrowBottom.css({
					'margin-left': (iItemHalfWidth + oItemOffset.left - this._$Region.offset().left - this._iArrowBorderLeft + Utils.pInt(this._$Region.css('margin-right'))) + 'px'
				});
			}
		}.bind(this), 1);
	},
	
	hide: function ()
	{
		if (this._bInitialized)
		{
			this._bShown = false;
			this._$Region.hide();
		}
	}
};

ko.bindingHandlers.customTooltip = {
	'init': function (oElement, fValueAccessor) {
		var
			sTooltipText = TextUtils.i18n(fValueAccessor()),
			$Element = $(oElement),
			$Dropdown = $Element.find('span.dropdown'),
			bShown = false,
			fMouseIn = function () {
				var $ItemToAlign = $(this);
				if (!$ItemToAlign.hasClass('expand'))
				{
					clearTimeout(CustomTooltip.iHideTimer);
					bShown = true;
					clearTimeout(CustomTooltip.iTimer);
					CustomTooltip.iTimer = setTimeout(function () {
						if (bShown)
						{
							if ($ItemToAlign.hasClass('expand'))
							{
								bShown = false;
								clearTimeout(CustomTooltip.iTimer);
								CustomTooltip.hide();
							}
							else
							{
								CustomTooltip.show(sTooltipText, $ItemToAlign);
							}
						}
					}, 100);
				}
			},
			fMouseOut = function () {
				clearTimeout(CustomTooltip.iHideTimer);
				CustomTooltip.iHideTimer = setTimeout(function () {
					bShown = false;
					clearTimeout(CustomTooltip.iTimer);
					CustomTooltip.hide();
				}, 10);
			},
			fEmpty = function () {},
			fBindEvents = function () {
				$Element.unbind('mouseover', fMouseIn);
				$Element.unbind('mouseout', fMouseOut);
				$Element.unbind('click', fMouseOut);
				$Dropdown.unbind('mouseover', fMouseOut);
				$Dropdown.unbind('mouseout', fEmpty);
				if (sTooltipText !== '')
				{
					$Element.bind('mouseover', fMouseIn);
					$Element.bind('mouseout', fMouseOut);
					$Element.bind('click', fMouseOut);
					$Dropdown.bind('mouseover', fMouseOut);
					$Dropdown.bind('mouseout', fEmpty);
				}
			},
			fSubscribtion = null
		;
		
		if (typeof sTooltipText === 'function')
		{
			sTooltipText = sTooltipText();
		}
		
		fBindEvents();
		
		if (typeof fValueAccessor().subscribe === 'function' && fSubscribtion === null)
		{
			fSubscribtion = fValueAccessor().subscribe(function (sValue) {
				sTooltipText = sValue;
				fBindEvents();
			});
		}
	}
};
