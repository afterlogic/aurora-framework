'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	
	Browser = require('modules/Core/js/Browser.js')
;

ko.bindingHandlers.i18n = {
	'init': function (oElement, fValueAccessor) {
		var
			oCommand = fValueAccessor(),
			sKey = oCommand.key,
			sType = oCommand.type || 'text',
			sValue = TextUtils.i18n(sKey)
		;

		if ('' !== sValue)
		{
			switch (sType)
			{
				case 'html':
					$(oElement).html(sValue);
					break;
				case 'placeholder':
					$(oElement).attr({'placeholder': sValue});
					break;
				case 'text':
				default:
					$(oElement).text(sValue);
					break;
			}
		}
	}
};

ko.bindingHandlers.dropdown = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = _.defaults(
				fValueAccessor(), {
					'disabled': 'disabled',
					'expand': 'expand',
					'control': true,
					'container': '.dropdown_content',
					'scrollToTopContainer': '.scroll-inner',
					'passClick': true,
					'trueValue': true
				}
			),
			bControl = typeof oCommand['control'] === 'function' ? oCommand['control']() : oCommand['control'],
			jqControl = jqElement.find('.control'),
			jqDrop = jqElement.find('.dropdown'),
			jqDropHelper = jqElement.find('.dropdown_helper'),
			jqDropArrow = jqElement.find('.dropdown_arrow'),
			jqDropBottomArrow = jqElement.find('.dropdown_arrow.bottom_arrow'),
			oDocument = $(document),
			bScrollBar = false,
			oOffset,
			iLeft,
			iFitToScreenOffset,
			fCallback = function () {
				if ($.isFunction(oCommand['callback']))
				{
					oCommand['callback'].call(
						oViewModel,
						jqElement.hasClass(oCommand['expand']) ? oCommand['trueValue'] : false,
						jqElement
					);
				}
			},
			fStop = function (event) {
				event.stopPropagation();
			},
			fScrollToTop = function () {
				if (oCommand['scrollToTopContainer'])
				{
					jqElement.find(oCommand['scrollToTopContainer']).scrollTop(0);
				}
			},
			fToggleExpand = function (bValue) {
				if (bValue === undefined)
				{
					bValue = !jqElement.hasClass(oCommand['expand']);
				}

				if (!bValue && jqElement.hasClass(oCommand['expand']))
				{
					fScrollToTop();
				}

				jqElement.toggleClass(oCommand['expand'], bValue);
				
				if (jqDropBottomArrow.length > 0 && jqElement.hasClass(oCommand['expand']))
				{
					jqDrop.css({
						'top': (jqElement.position().top - jqDropHelper.height()) + 'px',
						'left': jqElement.position().left + 'px',
						'width': 'auto'
					});
				}
			},
			fFitToScreen = function (iOffsetLeft) {
				oOffset = jqDropHelper.offset();
				if (oOffset)
				{
					iLeft = oOffset.left + 10;
					iFitToScreenOffset = $(window).width() - (iLeft + jqDropHelper.outerWidth(true));

					if (iFitToScreenOffset > 0)
					{
						iFitToScreenOffset = 0;
					}

					jqDropHelper.css('left', iOffsetLeft || iFitToScreenOffset + 'px');
					jqDropArrow.css('left', iOffsetLeft || Math.abs(iFitToScreenOffset ? iFitToScreenOffset + Types.pInt(jqDropArrow.css('margin-left')) : 0) + 'px');
				}
			},
			fControlClick = function (oEv) {
				var
					jqDropdownParent = $(oEv.originalEvent.originalTarget).parents('.dropdown'),
					bHasDropdownParent = jqDropdownParent.length > 0
				;
				
				if (!bHasDropdownParent && !jqElement.hasClass(oCommand['disabled']) && !bScrollBar)
				{
					fToggleExpand();

					_.defer(function () {
						fCallback();
					});

					if (jqElement.hasClass(oCommand['expand']))
					{

						if (oCommand['close'] && oCommand['close']['subscribe'])
						{
							oCommand['close'](true);
						}
						
						_.defer(function () {
							oDocument.on('click.dropdown', function (ev) {
								var iMouseRightClick = 2;
								if((oCommand['passClick'] || ev.button !== iMouseRightClick) && !bScrollBar)
								{
									oDocument.unbind('click.dropdown');
									if (oCommand['close'] && oCommand['close']['subscribe'])
									{
										oCommand['close'](false);
									}

									fToggleExpand(false);

									fCallback();
									fFitToScreen(0);
								}
								bScrollBar = false;
							});
						});

						fFitToScreen();
					}
				}
			}
		;
		
		jqElement.off();
		jqControl.off();
		
		if (!oCommand['passClick'])
		{
			jqElement.find(oCommand['container']).on('click', fStop);
			jqElement.on('click', fStop);
			jqControl.on('click', fStop);
		}

		fToggleExpand(false);
		
		if (oCommand['close'] && oCommand['close']['subscribe'])
		{
			oCommand['close'].subscribe(function (bValue) {
				if (!bValue)
				{
					oDocument.unbind('click.dropdown');
					fToggleExpand(false);
				}

				fCallback();
			});
		}

		jqElement.on('mousedown', function (oEv, oEl) {
			bScrollBar = ($(oEv.target).hasClass('customscroll-scrollbar') || $(oEv.target.parentElement).hasClass('customscroll-scrollbar'));
		});

		jqElement.on('click', function (oEv) {
			if (!bControl)
			{
				fControlClick(oEv);
			}
		});
		jqControl.on('click', function (oEv) {
			if (bControl)
			{
				fControlClick(oEv);
			}
		});
	}
};

ko.bindingHandlers.initDom = {
	'init': function (oElement, fValueAccessor) {
		var oCommand = fValueAccessor();
		if (oCommand)
		{
			oCommand($(oElement));
		}
	}
};

ko.bindingHandlers.command = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = fValueAccessor()
		;

		if (!oCommand || !oCommand.enabled || !oCommand.canExecute)
		{
			throw new Error('You are not using command function');
		}

		jqElement.addClass('command');
		ko.bindingHandlers[jqElement.is('form') ? 'submit' : 'click'].init.apply(oViewModel, arguments);
	},

	'update': function (oElement, fValueAccessor) {
		var
			bResult = true,
			jqElement = $(oElement),
			oCommand = fValueAccessor()
		;

		bResult = oCommand.enabled();
		jqElement.toggleClass('command-not-enabled', !bResult);

		if (bResult)
		{
			bResult = oCommand.canExecute();
			jqElement.toggleClass('unavailable', !bResult);
		}

		jqElement.toggleClass('command-disabled disable disabled', !bResult);
		jqElement.toggleClass('enable', bResult);
	}
};

function deferredUpdate(element, state, duration, callback)
{
	if (!element.__interval && !!state)
	{
		element.__state = true;
		callback(element, true);

		element.__interval = window.setInterval(function () {
			if (!element.__state)
			{
				callback(element, false);
				window.clearInterval(element.__interval);
				element.__interval = null;
			}
		}, duration);
	}
	else if (!state)
	{
		element.__state = false;
	}
};

ko.bindingHandlers.checkstate = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
		var
			oOptions = oElement.oOptions || null,
			jqElement = oElement.jqElement || null,
			oIconIE = oElement.oIconIE || null,
			values = fValueAccessor(),
			state = values.state
		;
		
		if (values.state !== undefined)
		{
			if (!jqElement)
			{
				oElement.jqElement = jqElement = $(oElement);
			}

			if (!oOptions)
			{
				oElement.oOptions = oOptions = _.defaults(
					values, {
						'activeClass': 'process',
						'duration': 800
					}
				);
			}

			deferredUpdate(jqElement, state, oOptions['duration'], function (element, state) {
				if (Browser.ie9AndBelow)
				{
					if (!oIconIE)
					{
						oElement.oIconIE = oIconIE = jqElement.find('.icon');
					}

					if (!oIconIE.__intervalIE && !!state)
					{
						var
							i = 0,
							style = ''
						;

						oIconIE.__intervalIE = setInterval(function () {
							style = '0px -' + (20 * i) + 'px';
							i = i < 7 ? i + 1 : 0;
							oIconIE.css({'background-position': style});
						}, 1000/12);
					}
					else
					{
						oIconIE.css({'background-position': '0px 0px'});
						clearInterval(oIconIE.__intervalIE);
						oIconIE.__intervalIE = null;
					}
				}
				else
				{
					element.toggleClass(oOptions['activeClass'], state);
				}
			});
		}
	}
};

ko.bindingHandlers.heightAdjust = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor) {
		
		var 
			jqElement = oElement.jqElement || null,
			height = 0,
			sLocation = fValueAccessor().location,
			sDelay = fValueAccessor().delay || 400
		;

		if (!jqElement)
		{
			oElement.jqElement = jqElement = $(oElement);
		}
		_.delay(function () {
			_.each(fValueAccessor().elements, function (mItem) {
				var element = mItem();
				if (element)
				{
					height += element.is(':visible') ? element.outerHeight() : 0;
				}
			});
			
			if (sLocation === 'top' || sLocation === undefined)
			{
				jqElement.css({
					'padding-top': height,
					'margin-top': -height
				});
			}
			else if (sLocation === 'bottom')
			{
				jqElement.css({
					'padding-bottom': height,
					'margin-bottom': -height
				});
			}
		}, sDelay);
	}
};

ko.bindingHandlers.minHeightAdjust = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor) {
		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			jqAdjustEl = oOptions.adjustElement || $('body'),
			iMinHeight = oOptions.minHeight || 0
		;
		
		if (oOptions.removeTrigger)
		{
			jqAdjustEl.css('min-height', 'inherit');
		}
		
		if (oOptions.trigger)
		{
			_.delay(function () {
				jqAdjustEl.css({'min-height': jqEl.outerHeight(true) + iMinHeight});
			}, 100);
		}
	}
};

ko.bindingHandlers.listWithMoreButton = {
	'init': function (oElement, fValueAccessor) {
		var
			$Element = $(oElement),
			skipOneResize = false //for some flicker at slow resize (does not solve the problem completely TODO)
		;

		$Element.closest('div.panel.message_panel').resize(function () {
			
			var
				$ItemsVisible = $Element.find('span.hotkey'),
				$ItemsHidden = $Element.find('span.item'),
				$MoreHints = $Element.find('span.more_hints').show(),
				iElementWidth = $Element.width(),
				iMoreWidth = $MoreHints.width(),
				bHideMoreHints = true
			;

			if (!skipOneResize)
			{
				_.each($ItemsVisible, function (oItem, index) {
					var
						$Item = $(oItem),
						iItemWidth = $Item.width()
					;

					if (bHideMoreHints && iMoreWidth + iItemWidth < iElementWidth)
					{
						skipOneResize = false;
						$Item.show();
						$($ItemsHidden[index]).hide();
						iMoreWidth += iItemWidth;
					}
					else
					{
						skipOneResize = true;
						bHideMoreHints = false;
						$Item.hide();
						$($ItemsHidden[index]).show();
					}
				});

				if (bHideMoreHints)
				{
					$MoreHints.hide();
				}
			}
			else
			{
				skipOneResize = false;
			}
		});
	}
};

ko.bindingHandlers.onEnter = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		$(oElement).on('keyup', function (oEvent) {
			if (oEvent.keyCode === Enums.Key.Enter)
			{
				$(oElement).trigger('change');
				fValueAccessor().call(oViewModel);
			}
		});
	}
};

ko.bindingHandlers.onFocusSelect = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'focus': function () {
					oElement.select();
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

//helpdesk
ko.bindingHandlers.watchWidth = {
	'init': function (oElement, fValueAccessor) {
		var bTriggered = false;

		if (!bTriggered)
		{
			fValueAccessor().subscribe(function () {
				fValueAccessor()($(oElement).outerWidth());
				bTriggered = true;
			}, this);
		}
	}
};

//files
ko.bindingHandlers.columnCalc = {
	'init': function (oElement, fValueAccessor) {
		var
			$oElement = $(oElement),
			oProp = fValueAccessor()['prop'],
			$oItem = null,
			iWidth = 0
		;
			
		$oItem = $oElement.find(fValueAccessor()['itemSelector']);

		if ($oItem[0] === undefined)
		{
			return;
		}
		
		iWidth = $oItem.outerWidth(true);
		iWidth = 1 >= iWidth ? 1 : iWidth;
		
		if (oProp)
		{
			$(window).bind('resize', function () {
				var iW = $oElement.width();
				oProp(0 < iW ? Math.floor(iW / iWidth) : 1);
			});
		}
	}
};

//settings
ko.bindingHandlers.adjustHeightToContent = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
		var
			jqEl = $(oElement),
			jqTargetEl = null,
			jqParentEl = null,
			jqNearEl = null
		;

		_.delay(_.bind(function(){
			jqTargetEl = $(_.max(jqEl.find('.title .text'), function(domEl){
				return domEl.offsetWidth;
			}));

			jqParentEl = jqTargetEl.parent();
			jqNearEl = jqParentEl.find('.icon');

			jqEl.css('min-width',
				Types.pInt(jqParentEl.css("margin-left")) +
				Types.pInt(jqParentEl.css("padding-left")) +
				Types.pInt(jqNearEl.width()) +
				Types.pInt(jqNearEl.css("margin-left")) +
				Types.pInt(jqNearEl.css("margin-right")) +
				Types.pInt(jqNearEl.css("padding-left")) +
				Types.pInt(jqNearEl.css("padding-right")) +
				Types.pInt(jqTargetEl.width()) +
				Types.pInt(jqTargetEl.css("margin-left")) +
				Types.pInt(jqTargetEl.css("padding-left")) +
				10
			);
		},this), 1);
	}
};

module.exports = {};
