'use strict';

var
	jQuery = require('jquery'),
	_ = require('underscore'),
	
	Storage = require('core/js/Storage.js'),
	Browser = require('core/js/Browser.js')
;

(function ($) {
 
 /**
  * @param {{name:string,resizeFunc:Function}} args
  */
 $.fn.splitter = function(args) {

	args = args || {};

	return this.each(function () {
		
		var
			bIsMouseSplit = false,
			bCollapse = args.collapse ? args.collapse : false,
			storageKey = args.name,
			initPosition = 0,
			nSize = 0,
			oLastState = {},
			oLastStateReserve = {},
			startSplitMouse = function (e) {
				bIsMouseSplit = true;
				bar.addClass(opts['activeClass']);

				opts['_posSplit'] = -((rtl ? splitter._overallWidth - e[opts['eventPos']] : e[opts['eventPos']]) - panes.get(0)[opts['pxSplit']] );
				
				$('body')
					.attr({'unselectable': "on"})
					.addClass('unselectable');

				$(document)
					.bind('mousemove', doSplitMouse)
					.bind('mouseup', endSplitMouse);
			},
			doSplitMouse = function (e) {
				var newPos = (rtl ? splitter._overallWidth - e[opts['eventPos']] : e[opts['eventPos']]) + opts['_posSplit'];
				resplit(newPos);
				
				if ($.isFunction(args.resizeFunc))
				{
					args.resizeFunc();
				}
			},
			endSplitMouse = function endSplitMouse(e) {
				bar.removeClass(opts['activeClass']);

				$('body')
					.attr({'unselectable': 'off'})
					.removeClass('unselectable');

				// Store 'width' data
				if (storageKey)
				{
					Storage.setData(storageKey + 'ResizerWidth', panes.get(0)[opts['pxSplit']]);
				}

				$(document)
					.unbind('mousemove', doSplitMouse)
					.unbind('mouseup', endSplitMouse);
				
				if ($.isFunction(args.resizeFunc))
				{
					args.resizeFunc();
				}
			},
			resplit = function (newPosition, bIgnoreSizeLimits) {

				var iLeftMin = panes.get(0)._min;

				if (bCollapse && (iLeftMin - newPosition) > 150) //Collapse
				{
					newPosition = 5;
					bIgnoreSizeLimits = true;
				}

				if (!bIgnoreSizeLimits) { //Constrain new splitbar position to fit pane size limits
					newPosition = window.Math.max(
						iLeftMin,
						splitter._overallWidth - panes.get(1)._max,
						window.Math.min(
							newPosition,
							panes.get(0)._max,
							splitter._overallWidth - panes.get(1)._min
						)
					);
				}

				panes.get(0).$.css(opts['split'], newPosition);
				panes.get(1).$.css(opts['split'], splitter._overallWidth - newPosition);
				
				if (!Browser.ie8AndBelow)
				{
					panes.trigger('resize');
				}
			},
			dimSum = function (elem, dims) {
				// Opera returns -1 for missing min/max width, turn into 0
				var sum = 0, i = 1;
				for (; i < arguments.length; i++)
				{
					sum += window.Math.max(window.parseInt(elem.css(arguments[i]), 10) || 0, 0);
				}
				
				return sum;
			},
			vh = (args.splitHorizontal ? 'h' : args.splitVertical ? 'v' : args.type) || 'v',
			opts = $.extend({
				'activeClass': 'active',	// class name for active splitter
				'pxPerKey': 8,				// splitter px moved per keypress
				'tabIndex': 0,				// tab order indicator
				'accessKey': ''				// accessKey for splitbar
			},{
				v: {						// Vertical splitters:
					'keyLeft': 39, 'keyRight': 37,
					'type': 'v', 'eventPos': "pageX", 'origin': "left",
					'split': "width",  'pxSplit': "offsetWidth",  'side1': "Left", 'side2': "Right",
					'fixed': "height", 'pxFixed': "offsetHeight", 'side3': "Top",  'side4': "Bottom"
				},
				h: {						// Horizontal splitters:
					'keyTop': 40, 'keyBottom': 38,
					'type': 'h', 'eventPos': "pageY", 'origin': "top",
					'split': "height", 'pxSplit': "offsetHeight", 'side1': "Top",  'side2': "Bottom",
					'fixed': "width",  'pxFixed': "offsetWidth",  'side3': "Left", 'side4': "Right"
				}
			}[vh], args),
			
			splitter = $(this),
			panes = $(">*:not(css3pie)", splitter).each(function(){this.$ = $(this);}),
			bar = $('.resize_handler', panes.get(0))
				.attr({'unselectable': 'on'})
				.bind('mousedown', startSplitMouse),
			rtl = splitter.css('direction') === 'rtl'
		;

		panes.get(0)._paneName = opts['side1'];
		panes.get(1)._paneName = opts['side2'];
		
		panes.each(function(){
			this._min = opts['min' + this._paneName] || dimSum(this.$, 'min-' + opts['split']);
			this._max = opts['max' + this._paneName] || dimSum(this.$, 'max-' + opts['split']) || 9999;
			this._init = opts['size' + this._paneName] === undefined ?
				window.parseInt($.css(this, opts['split']), 10) : opts['size' + this._paneName];
		});

		// Determine initial position, get from cookie if specified
		if (storageKey)
		{
			initPosition = Storage.getData(storageKey + 'ResizerWidth') || panes.get(0)._init;
		}
		else
		{
			initPosition = panes.get(0)._init;
		}

		if (isNaN(initPosition))
		{
			initPosition = splitter[0][opts['pxSplit']];
			initPosition = window.Math.round(initPosition / panes.length);
		}
		// Resize event propagation and splitter sizing
		if (opts['resizeToWidth'] && !(Browser.ie8AndBelow))
		{
			$(window).bind('resize', function(e) {
				if (e.target !== this)
				{
					return;
				}
				splitter.trigger('resize'); 
			});
		}

		splitter.bind('resize', function (ev, size, sCommand, bIgnoreSizeLimits, bMaximizeOnly) {

			var tKey = ev.target.className + '_' + sCommand;
			if (bIsMouseSplit)
			{
				oLastState = {};
			}

			// Custom events bubble in jQuery 1.3; don't get into a Yo Dawg
			if (ev.target !== this)
			{
				return;
			}

			// Determine new width/height of splitter container
			splitter._overallWidth = splitter[0][opts['pxSplit']];

			// Return if splitter isn't visible or content isn't there yet
			if (splitter._overallWidth <= 0)
			{
				return;
			}

			if (!(opts['sizeRight'] || opts['sizeBottom']))
			{
				nSize = panes.get(0)[opts['pxSplit']];
			}
			else
			{
				nSize = splitter._overallWidth - panes.get(1)[opts['pxSplit']];
			}

			if (isNaN(size))
			{
				size = nSize;
			}
			else if (sCommand)
			{
				if(bMaximizeOnly)
				{
					size = oLastState[tKey] ? oLastState[tKey] : nSize;
				}
				else
				{
					bIsMouseSplit = false;

					if (oLastState[tKey])
					{
						size = oLastState[tKey];
						oLastState[tKey] = null;
					}
					else
					{
						if (size === nSize)
						{
							oLastState[tKey] = null;
							size = oLastStateReserve[tKey];
						}
						else
						{
							oLastState[tKey] = oLastStateReserve[tKey] = nSize;
						}

						_.each(oLastState, function(num, key) {
							if (key !== tKey)
							{
								oLastState[key] = null;
							}
						});
					}
				}
			}

			resplit(size, bIgnoreSizeLimits);
			
		}).trigger('resize', [initPosition]);
	});
};

})(jQuery);