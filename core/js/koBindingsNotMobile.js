'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	Browser = require('core/js/Browser.js')
;

require('customscroll');
require('jquery-ui');

ko.bindingHandlers.splitter = {
	'init': function (oElement, fValueAccessor) {
		setTimeout(function() {
			$(oElement).splitter(fValueAccessor());
		}, 1);
	}
};

ko.bindingHandlers.customScrollbar = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = _.defaults(fValueAccessor(), {
				'oScroll' : null,
				'scrollToTopTrigger': null,
				'scrollToBottomTrigger': null,
				'scrollTo': null

			}),
			oScroll = null
		;

		/*_.delay(_.bind(function () {
			var jqCustomScrollbar = jqElement.find('.customscroll-scrollbar-vertical');

			jqCustomScrollbar.on('click', function (oEv) {
				oEv.stopPropagation();
			});
		}, this), 1000);*/



		oCommand = /** @type {{scrollToTopTrigger:{subscribe:Function},scrollToBottomTrigger:{subscribe:Function},scrollTo:{subscribe:Function},reset:Function}}*/ oCommand;

		jqElement.addClass('scroll-wrap').customscroll(oCommand);
		oScroll = jqElement.data('customscroll');

		if (oCommand['oScroll'] && $.isFunction(oCommand['oScroll'].subscribe))
		{		
			oCommand['oScroll'](oScroll);
		}
		else
		{
			oCommand['oScroll'] = oScroll;
		}

		if (!Utils.isUnd(oCommand.reset))
		{
			oElement._customscroll_reset = _.throttle(function () {
				oScroll.reset();
			}, 100);
		}
		
		if (oCommand['scrollToTopTrigger'] && $.isFunction(oCommand.scrollToTopTrigger.subscribe)) {
			oCommand.scrollToTopTrigger.subscribe(function () {
				if (oScroll) {
					oScroll['scrollToTop']();
				}
			});
		}
		
		if (oCommand['scrollToBottomTrigger'] && $.isFunction(oCommand.scrollToBottomTrigger.subscribe))
		{
			oCommand.scrollToBottomTrigger.subscribe(function () {
				if (oScroll)
				{
					oScroll['scrollToBottom']();
				}
			});
		}

		if (oCommand['scrollTo'] && $.isFunction(oCommand.scrollTo.subscribe))
		{
			oCommand.scrollTo.subscribe(function ()
			{
				if (oScroll)
				{
					oScroll['scrollTo'](oCommand.scrollTo());
				}
			});
		}
	},
	
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
		if (oElement._customscroll_reset)
		{
			oElement._customscroll_reset();
		}
		if (!Utils.isUnd(fValueAccessor().top))
		{

			$(oElement).data('customscroll')['vertical'].set(fValueAccessor().top);
		}
	}
};

function removeActiveFocus()
{
	if (document && document.activeElement && document.activeElement.blur)
	{
		var oA = $(document.activeElement);
		if (oA.is('input') || oA.is('textarea'))
		{
			document.activeElement.blur();
		}
	}
}

ko.bindingHandlers.draggablePlace = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
		if (fValueAccessor() === null)
		{
			return null;
		}

		var oAllBindingsAccessor = fAllBindingsAccessor ? fAllBindingsAccessor() : null;
		$(oElement).draggable({
			'distance': 20,
			'handle': '.dragHandle',
			'cursorAt': {'top': 0, 'left': 0},
			'helper': function (oEvent) {
				//return fValueAccessor().call(oViewModel, oEvent && oEvent.target ? ko.dataFor(oEvent.target) : null);
				return fValueAccessor().apply(oViewModel, oEvent && oEvent.target ? [ko.dataFor(oEvent.target), oEvent.ctrlKey] : null);
			},
			'start': (oAllBindingsAccessor && oAllBindingsAccessor['draggableDragStartCallback']) ? oAllBindingsAccessor['draggableDragStartCallback'] : function () {},
			'stop': (oAllBindingsAccessor && oAllBindingsAccessor['draggableDragStopCallback']) ? oAllBindingsAccessor['draggableDragStopCallback'] : function () {}
		}).on('mousedown', function () {
			removeActiveFocus();
		});
	}
};

ko.bindingHandlers.droppable = {
	'init': function (oElement, fValueAccessor) {
		var
			oOptions = fValueAccessor(),
			fValueFunc = oOptions.valueFunc,
			fSwitchObserv = oOptions.switchObserv
		;
		
		if (false !== fValueFunc)
		{
			$(oElement).droppable({
				'hoverClass': 'droppableHover',
				'drop': function (oEvent, oUi) {
					fValueFunc(oEvent, oUi);
				}
			});
		}
		
		if (fSwitchObserv && fValueFunc !== false)
		{
			fSwitchObserv.subscribe(function (bIsSelected) {
				if($(oElement).data().droppable)
				{
					if(bIsSelected)
					{
						$(oElement).droppable('disable');
					}
					else
					{
						$(oElement).droppable('enable');
					}
				}
			}, this);
			fSwitchObserv.valueHasMutated();
		}
	}
};

ko.bindingHandlers.quickReplyAnim = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqTextarea = oElement.jqTextarea || null,
			jqStatus = oElement.jqStatus || null,
			jqButtons = oElement.jqButtons || null,
			jqElement = oElement.jqElement || null,
			oPrevActions = oElement.oPrevActions || null,
			values = fValueAccessor(),
			oActions = null
		;

		oActions = _.defaults(
			values, {
				'saveAction': false,
				'sendAction': false,
				'activeAction': false
			}
		);

		if (!jqElement)
		{
			oElement.jqElement = jqElement = $(oElement);
			oElement.jqTextarea = jqTextarea = jqElement.find('textarea');
			oElement.jqStatus = jqStatus = jqElement.find('.status');
			oElement.jqButtons = jqButtons = jqElement.find('.buttons');
			
			oElement.oPrevActions = oPrevActions = {
				'saveAction': null,
				'sendAction': null,
				'activeAction': null
			};
		}

		if (true || jqElement.is(':visible'))
		{
			if (Browser.ie9AndBelow)
			{
				if (jqTextarea && !jqElement.defualtHeight && !jqTextarea.defualtHeight)
				{
					jqElement.defualtHeight = jqElement.outerHeight();
					jqTextarea.defualtHeight = jqTextarea.outerHeight();
					jqStatus.defualtHeight = jqButtons.outerHeight();
					jqButtons.defualtHeight = jqButtons.outerHeight();
				}

				_.defer(function () {
					var 
						activeChanged = oPrevActions.activeAction !== oActions['activeAction'],
						sendChanged = oPrevActions.sendAction !== oActions['sendAction'],
						saveChanged = oPrevActions.saveAction !== oActions['saveAction']
					;

					if (activeChanged)
					{
						if (oActions['activeAction'])
						{
							jqTextarea.animate({
								'height': jqTextarea.defualtHeight + 50
							}, 300);
							jqElement.animate({
								'max-height': jqElement.defualtHeight + jqButtons.defualtHeight + 50
							}, 300);
						}
						else
						{
							jqTextarea.animate({
								'height': jqTextarea.defualtHeight
							}, 300);
							jqElement.animate({
								'max-height': jqElement.defualtHeight
							}, 300);
						}
					}

					if (sendChanged || saveChanged)
					{
						if (oActions['sendAction'])
						{
							jqElement.animate({
								'max-height': '30px'
							}, 300);
							jqStatus.animate({
								'max-height': '30px',
								'opacity': 1
							}, 300);
						}
						else if (oActions['saveAction'])
						{
							jqElement.animate({
								'max-height': 0
							}, 300);
						}
						else
						{
							jqElement.animate({
								'max-height': jqElement.defualtHeight + jqButtons.defualtHeight + 50
							}, 300);
							jqStatus.animate({
								'max-height': 0,
								'opacity': 0
							}, 300);
						}
					}
				});
			}
			else
			{
				jqElement.toggleClass('saving', oActions['saveAction']);
				jqElement.toggleClass('sending', oActions['sendAction']);
				jqElement.toggleClass('active', oActions['activeAction']);
			}
		}

		_.defer(function () {
			oPrevActions = oActions;
		});
	}
};

ko.bindingHandlers.onCtrlEnter = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var $Element = $(oElement);
		$Element.on('keydown', function (oEvent) {
			if (oEvent.ctrlKey && oEvent.keyCode === Enums.Key.Enter)
			{
				$Element.trigger('change');
				fValueAccessor().call(oViewModel);
			}
		});
	}
};

ko.bindingHandlers.onEsc = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var $Element = $(oElement);
		$Element.on('keydown', function (oEvent) {
			if (oEvent.keyCode === Enums.Key.Esc)
			{
				$Element.trigger('change');
				fValueAccessor().call(oViewModel);
			}
		});
	}
};

ko.bindingHandlers.autocompleteSimple = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			fCallback = oOptions['callback'],
			fDataAccessor = oOptions.dataAccessor ? oOptions.dataAccessor : function () {},
			fDeleteAccessor = oOptions.deleteAccessor ? oOptions.deleteAccessor : function () {},
			fSourceResponse = function () {},
			fDelete = function () {
				fDeleteAccessor(oSelectedItem);
				$.ui.autocomplete.prototype.__response.call(jqEl.data('autocomplete'), _.filter(aSourceResponseItems, function(oItem){ return oItem.value !== oSelectedItem.value; }));
			},
			aSourceResponseItems = null,
			oSelectedItem = null
		;

		if (fCallback && jqEl && jqEl[0])
		{
			jqEl.autocomplete({
				'minLength': 1,
				'autoFocus': true,
				'position': {
					collision: "flip" //prevents the escape off the screen
				},
				'source': function (request, response) {
					fSourceResponse = response;
					fCallback(request['term'], function (oItems) { //additional layer for story oItems
						aSourceResponseItems = oItems;
						fSourceResponse(oItems);
					});
				},
				'focus': function (oEvent, oItem) {
					oSelectedItem = oItem.item;
				},
				'open': function (oEvent, oItem) {
					$(jqEl.autocomplete('widget')).find('span.del').on('click', function(oEvent, oItem) {
						Utils.calmEvent(oEvent);
						fDelete();
					});
				},
				'select': function (oEvent, oItem) {
					_.delay(function () {
						jqEl.trigger('change');
					}, 5);
					fDataAccessor(oItem.item);

					return true;
				}
			}).on('click', function(oEvent, oItem) {
				if (jqEl.val() === '')
				{
					if (!$(jqEl.autocomplete('widget')).is(':visible'))
					{
						jqEl.autocomplete("option", "minLength", 0); //for triggering search on empty field
						jqEl.autocomplete("search");
						jqEl.autocomplete("option", "minLength", 1);
					}
					else
					{
						jqEl.autocomplete("close");
					}
				}
			}).on('keydown', function(oEvent, oItem) {
				if (aSourceResponseItems && oSelectedItem && !oSelectedItem.global && oEvent.keyCode === Enums.Key.Del && oEvent.shiftKey) //shift+del on suggestions list
				{
					Utils.calmEvent(oEvent);
					fDelete();
				}
			});
		}
	}
};
