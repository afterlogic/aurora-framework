/**
 * Inputosaurus Text 
 *
 * Must be instantiated on an <input> element
 * Allows multiple input items. Each item is represented with a removable tag that appears to be inside the input area.
 *
 * @requires:
 *
 * 	jQuery 1.7+
 * 	jQueryUI 1.8+ Core
 *
 * @version 0.1.6
 * @author Dan Kielp <dan@sproutsocial.com>
 * @created October 3,2012
 *
 */

'use strict';

var
	jQuery = require('jquery'),
	_ = require('underscore'),
	
	TextUtils = require('core/js/utils/Text.js'),
	AddressUtils = require('core/js/utils/Address.js')
;

function GetAutocomplete(oInput)
{
	return oInput.data('customAutocomplete') || oInput.data('uiAutocomplete') || oInput.data('autocomplete') || oInput.data('ui-autocomplete');
}

(function($) {

	var inputosaurustext = {

		version: "0.1.6",

		eventprefix: "inputosaurus",

		options: {

			// bindable events
			//
			// 'change' - triggered whenever a tag is added or removed (should be similar to binding the the change event of the instantiated input
			// 'keyup' - keyup event on the newly created input
			
			// while typing, the user can separate values using these delimiters
			// the value tags are created on the fly when an inputDelimiter is detected
			inputDelimiters : [',', ';'],

			// this separator is used to rejoin all input items back to the value of the original <input>
			outputDelimiter : ',',

			allowDuplicates : false,

			parseOnBlur : false,

			// optional wrapper for widget
			wrapperElement : null,

			width : null,
			
			autoCompleteAppendTo : null,

			// simply passing an autoComplete source (array, string or function) will instantiate autocomplete functionality
			autoCompleteSource : '',

			// When forcing users to select from the autocomplete list, allow them to press 'Enter' to select an item if it's the only option left.
			activateFinalResult : false,

			// manipulate and return the input value after parseInput() parsing
			// the array of tag names is passed and expected to be returned as an array after manipulation
			parseHook : null,
			
			// define a placeholder to display when the input is empty
			placeholder: null,

			// for triggering search on empty field
			openedByClick: false,
			// delete item from suggestions list
			sourceResponse: function () {},
			deleteSelectedItem: function (widget) {
				var self = this;
				this.autoCompleteDeleteItem(this.selectedItem);
				$.ui.autocomplete.prototype.__response.call(GetAutocomplete($(widget.elements.input)), _.filter(this.sourceResponseItems, function(oItem){ return oItem.value !== self.selectedItem.value; }));
			},
			sourceResponseItems: null,
			selectedItem: null,
			autoCompleteDeleteItem: function () {}
		},

		_create: function(e) {
			var widget = this,
				els = {},
				o = widget.options,
				placeholder =  o.placeholder || this.element.attr('placeholder') || null,
				tabindexValue = this.element.attr('tabindex'),
				tabindexStr = tabindexValue ? ' tabindex="' + tabindexValue + '"' : '',
				ulParent = null;

			this._chosenValues = [];

			// Create the elements
			els.ul = $('<ul class="inputosaurus-container" style="padding: 3px;">');
			els.fakeSpan = $('<span class="inputosaurus-fake-span"></span>');
			els.input = $('<input type="text"' + tabindexStr + ' autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />');
			els.inputCont = $('<li class="inputosaurus-input inputosaurus-required"></li>');
			els.origInputCont = $('<li class="inputosaurus-input-hidden inputosaurus-required"></li>');
			els.lastEdit = '';

			// define starting placeholder
			if (placeholder) {
				o.placeholder = placeholder;
				els.input.attr('placeholder', o.placeholder);
				if (o.width) {
					els.input.css('min-width', o.width - 50);
				}
			}

			o.wrapperElement && o.wrapperElement.append(els.ul);
			this.element.replaceWith(o.wrapperElement || els.ul);
			els.origInputCont.append(this.element).hide();

			els.inputCont.append(els.input);
			els.ul.append(els.inputCont);
			els.ul.append(els.origInputCont);
			els.ul.append(els.fakeSpan);

			o.width && els.ul.css('width', o.width);

			ulParent = els.ul.parent();
			if (!widget.options.mobileDevice)
			{
				ulParent.droppable({
					drop: function(event, ui) {
						var
							oLiDraggable = $(ui.draggable),
							oDragWidget = ui.helper.__widget,
							sFullVal = oLiDraggable.data('full')
						;

						if (oDragWidget)
						{
							oDragWidget._removeLiTag(oLiDraggable, oDragWidget);
						}

						_.defer(function () {
							$(els.input).val(sFullVal);
							$(widget.element).inputosaurus('parseInput');
						});
					}
				});
			}

			this.elements = els;

			widget._attachEvents();

			// if instantiated input already contains a value, parse that junk
			if($.trim(this.element.val())){
				els.input.val( this.element.val() );
				this.parseInput();
			}

			this._instAutocomplete();
		},

		_instAutocomplete : function() {
			if(this.options.autoCompleteSource){
				var
					widget = this,
					sOrigSearch = ''
				;

				this.elements.input.autocomplete({
					position : {
						of : this.elements.ul
					},
					source : function (oRequest, fResponse) {
						sOrigSearch = oRequest.term;
						widget.options.sourceResponse = fResponse;
						widget.options.autoCompleteSource(oRequest, function (oItems) { //additional layer for story oItems
							widget.options.sourceResponseItems = oItems;
							widget.options.sourceResponse(oItems);
						});
					},
					minLength : 1,
					autoFocus: true,
					appendTo: this.options.autoCompleteAppendTo || 'body',
					select : function(ev, ui){
						if ($(this).val() !== sOrigSearch)
						{
							GetAutocomplete($(this)).close();
							return false;
						}

						ev.preventDefault();
						widget.elements.input.val(ui.item.value);
						widget.parseInput();
					},
					open : function() {
						var
							menu = GetAutocomplete($(this)).menu || null,
							$menuItems,
							maxHeight
						;

						if (menu)
						{
							menu.element.width(0 + widget.elements.ul.outerWidth(false) - 20);

							// set max-height
							maxHeight = $(window).height() - widget.elements.ul.outerHeight() - widget.elements.ul.offset().top + window.pageYOffset;
							menu.element.css('max-height', maxHeight > 200 ? maxHeight - 50 : maxHeight - 2);

							// auto-activate the result if it's the only one
							if(widget.options.activateFinalResult)
							{
								$menuItems = menu.element.find('li');

								// activate single item to allow selection upon pressing 'Enter'
								if($menuItems.size() === 1){
									menu[menu.activate ? 'activate' : 'focus']($.Event('click'), $menuItems);
								}
							}

							menu.element.find('span.del').on('click', function(oEvent, oItem) {
								oEvent.preventDefault();
								oEvent.stopPropagation();
								widget.options.deleteSelectedItem(widget);
							});
						}
					},
					close : function () {
						setTimeout(function () {
							widget.options.openedByClick = false;
						}.bind(this), 200);
					},
					focus : function (oEvent, oItem) {
						widget.options.selectedItem = oItem.item;
						return false;
					}
				});
			}
		},

		_autoCompleteMenuPosition : function() {
			if (this.options.autoCompleteSource)
			{
				GetAutocomplete(this.elements.input).menu.element.position({
					of: this.elements.ul,
					my: 'left top',
					at: 'left bottom',
					collision: 'none'
				});
			}
		},

		parseInput : function(ev) {
			var widget = (ev && ev.data.widget) || this,
				val = widget.elements.input.val(),
				values = [],
				sLastSymbol = (val && val.length > 0) ? val[val.length - 1] : '',
				aRecipients = AddressUtils.getArrayRecipients(val, false),
				bPressedDelimiter = aRecipients.length > 0 && $.inArray(sLastSymbol, [',', ';', ' ']) > -1,
				bPressedEnter = !ev || ev.which === $.ui.keyCode.ENTER && !$('.ui-menu-item .ui-state-focus').size() && !$('#ui-active-menuitem').size(),
				bLostFocus = ev && ev.type === 'blur' && !$('#ui-active-menuitem').size();

			if (bPressedDelimiter || bPressedEnter || bLostFocus)
			{
				values = _.map(aRecipients, function (oRecipient) {
					return oRecipient.fullEmail;
				});
				if (values.length === 0 && bPressedEnter)
				{
					values.push(val);
				}
				if (bPressedEnter)
				{
					ev && ev.preventDefault();
				}
			}

			$.isFunction(widget.options.parseHook) && (values = widget.options.parseHook(values));

			if (values.length)
			{
				widget.elements.input.val('');
				widget._resizeInput();
				widget._setChosen(values);
			}
			widget._resetPlaceholder();
			widget._resizeInput();
		},

		_inputFocus : function(ev) {

			var widget = ev.data.widget || this;

			widget.elements.input.value || widget.elements.input.autocomplete("option", "minLength") && (widget.options.autoCompleteSource.length && widget.elements.input.autocomplete('search', ''));

			widget._resizeInput();

			if ($.isFunction(widget.options.focus))
			{
				widget.options.focus();
			}
		},

		_inputKeypress : function(ev) {
			var widget = ev.data.widget || this,
				pasteValue = '';

			ev.type === 'keyup' && widget._trigger('keyup', ev, widget);

			switch(true){
				case ev.which === 86 && ev.ctrlKey:
					if ($.isFunction(widget.options.paste)) {
						pasteValue = widget.options.paste();
						if (pasteValue) {
							widget._setChosen([pasteValue]);
							ev.preventDefault();
						}
					}
					break;

				case ev.which === $.ui.keyCode.BACKSPACE:
					ev.type === 'keydown' && widget._inputBackspace(ev);
					break;

				case ev.which === $.ui.keyCode.LEFT:
					ev.type === 'keydown' && widget._inputBackspace(ev);
					break;

				default :
					if (ev.type === 'keydown')
					{
						widget.parseInput(ev);
					}
			}

			if (widget.options.sourceResponseItems && widget.options.selectedItem && !widget.options.selectedItem.global && ev.keyCode === $.ui.keyCode.DELETE && ev.shiftKey) //shift+del on suggestions list
			{
				ev.preventDefault();
				ev.stopPropagation();
				widget.options.deleteSelectedItem(widget);
			}
		},

		resizeInput : function () {
			this._resizeInput();
		},

		// the input dynamically resizes based on the length of its value
		_resizeInput : function(ev) {
			var widget = (ev && ev.data.widget) || this;

			if (!widget.elements.input.is(":focus") && widget.elements.input.val() === '')
			{
				widget.elements.input.width(1);
			}
			else
			{
				if (widget.elements.lastEdit === '')
				{
					widget._resizeEndInput(widget, 100);
				}
				else
				{
					this._resizeAnyPlaceInput(widget, 100);
				}
			}
		},

		_resizeAnyPlaceInput : function (widget, minWidth) {
			var
				maxWidth = widget.elements.ul.outerWidth() - 10,
				txtWidth = 0
			;

			widget.elements.fakeSpan.text(widget.elements.input.val());
			txtWidth = 20 + widget.elements.fakeSpan.width();

			txtWidth = txtWidth < maxWidth ? txtWidth : maxWidth;
			txtWidth = txtWidth < minWidth ? minWidth : txtWidth;

			widget.elements.input.width(txtWidth);
		},

		_resizeEndInput : function (widget, minWidth) {
			var lastTag = widget.elements.ul.find('li:not(.inputosaurus-required):last'),
				ulWidth = widget.elements.ul.outerWidth(),
				ulLeft = widget.elements.ul.position().left,
				liWidth = (lastTag && lastTag.length > 0) ? lastTag.outerWidth(): 0,
				liLeft = (lastTag && lastTag.length > 0) ? lastTag.position().left : ulLeft,
				inputWidth = ulWidth + ulLeft - liWidth - liLeft - 25;

			if (inputWidth < minWidth)
			{
				inputWidth = ulWidth - 25;
			}

			widget.elements.input.width(inputWidth + 'px');
		},

		// resets placeholder on representative input
		_resetPlaceholder: function () {
			var placeholder = this.options.placeholder,
				input = this.elements.input,
				width = this.options.width || 'inherit';
			if (placeholder && this.element.val().length === 0)
			{
				input.attr('placeholder', placeholder).css('min-width', width - 50);
			}
			else
			{
				input.attr('placeholder', '').css('min-width', 'inherit');
			}
		},

		// if our input contains no value and backspace has been pressed, select the last tag
		_inputBackspace : function(ev) {
			var widget = (ev && ev.data.widget) || this,
				lastTag = widget.elements.ul.find('li:not(.inputosaurus-required):last');

			// IE goes back in history if the event isn't stopped
			ev.stopPropagation();

			if((!$(ev.currentTarget).val() || (('selectionStart' in ev.currentTarget) && ev.currentTarget.selectionStart === 0 && ev.currentTarget.selectionEnd === 0)) && lastTag.size()){
				ev.preventDefault();
				lastTag.find('a').focus();
			}

		},

		_editTag : function(ev) {
			var widget = (ev && ev.data.widget) || this,
				tagName = '',
				$li = $(ev.currentTarget).closest('li'),
				tagKey = $li.data('inputosaurus');

			if (!tagKey) {
				return;
			}

			ev.preventDefault();

			var
				oPrev = null,
				next = false
			;

			$.each(widget._chosenValues, function(i,v) {
				if (v.key === tagKey)
				{
					tagName = v.value;
					next = true;
				}
				else if (next && !oPrev)
				{
					oPrev = v;
				}
			});

			if (oPrev)
			{
				widget.elements.lastEdit = oPrev.value;
			}

			$li.after(widget.elements.inputCont);

			widget.elements.input.val(tagName);
			setTimeout(function () {
				widget.elements.input.select();
			}, 100);

			widget._removeTag(ev);
		},

		_tagKeypress : function(ev) {
			var widget = ev.data.widget;
			switch(ev.which){
				// ctrl + 'x' - copy tag
				case 88:
					if (ev.ctrlKey) {
						if ($.isFunction(widget.options.copy)) {
							widget.options.copy(widget._cutTag(ev));
						}
					}
					break;

				// ctrl + 'c' - copy tag
				case 67:
					if (ev.ctrlKey) {
						if ($.isFunction(widget.options.copy)) {
							widget.options.copy(widget._copyTag(ev));
						}
					}
					break;

				// 'e' - edit tag (removes tag and places value into visible input
				case 69:
				case $.ui.keyCode.ENTER:
					widget._editTag(ev);
					break;

				case $.ui.keyCode.BACKSPACE:
				case $.ui.keyCode.DELETE:
					widget._removeTag(ev);
					ev && ev.preventDefault();
					ev && ev.stopPropagation();
					break;

				case $.ui.keyCode.LEFT:
					ev.type === 'keydown' && widget._prevTag(ev);
					break;

				case $.ui.keyCode.RIGHT:
					ev.type === 'keydown' && widget._nextTag(ev);
					break;

				case $.ui.keyCode.DOWN:
					ev.type === 'keydown' && widget._focus(ev);
					break;
			}
		},

		_cutTag : function(ev) {
			var copiedTagValue = this._copyTag(ev);
			this._removeTag(ev);
			return copiedTagValue;
		},

		_copyTag : function(ev) {
			var widget = (ev && ev.data.widget) || this,
				$li = $(ev.currentTarget).closest('li'),
				tagKey = $li.data('inputosaurus'),
				copiedTagValue = '';

			if (!tagKey) {
				return copiedTagValue;
			}

			ev.preventDefault();

			$.each(widget._chosenValues, function(i,v) {
				if (v.key === tagKey)
				{
					copiedTagValue = v.value;
				}
			});

			return copiedTagValue;
		},

		// select the previous tag or input if no more tags exist
		_prevTag : function(ev) {
			var widget = (ev && ev.data.widget) || this,
				tag = $(ev.currentTarget).closest('li'),
				previous = tag.prev();

			if(previous.is('li')){
				previous.find('a').focus();
			} else {
				widget._focus();
			}
		},

		// select the next tag or input if no more tags exist
		_nextTag : function(ev) {
			var widget = (ev && ev.data.widget) || this,
				tag = $(ev.currentTarget).closest('li'),
				next = tag.next();

			if(next.is('li:not(.inputosaurus-input)')){
				next.find('a').focus();
			} else {
				widget._focus();
			}
		},

		// return the inputDelimiter that was detected or false if none were found
		_containsDelimiter : function(tagStr) {

			var found = false;

			$.each(this.options.inputDelimiters, function(k,v) {
				if(tagStr.indexOf(v) !== -1){
					found = v;
				}
			});

			return found;
		},

		_setChosen : function(valArr) {
			var self = this;

			if (!_.isArray(valArr)) {
				return;
			}

			$.each(valArr, function(k,v) {
				var exists = false,
					lastIndex = -1,
					obj = {
						key : '',
						value : ''
					};

				v = $.trim(v);

				$.each(self._chosenValues, function(kk,vv) {
					if (vv.value === self.elements.lastEdit)
					{
						lastIndex = kk;
					}

					vv.value === v && (exists = true);
				});

				if(v !== '' && (!exists || self.options.allowDuplicates)){

					obj.key = 'mi_' + Math.random().toString( 16 ).slice( 2, 10 );
					obj.value = v;

					if (-1 < lastIndex)
					{
						self._chosenValues.splice(lastIndex, 0, obj);
					}
					else
					{
						self._chosenValues.push(obj);
					}

					self.elements.lastEdit = '';
					self._renderTags();
				}
			});

			if (valArr.length === 1 && valArr[0] === '' && self.elements.lastEdit !== '')
			{
				self.elements.lastEdit = '';
				self._renderTags();
			}

			self._setValue(self._buildValue());
		},

		_buildValue : function() {
			var widget = this,
				value = '';

			$.each(this._chosenValues, function(k,v) {
				value +=  value.length ? widget.options.outputDelimiter + v.value : v.value;
			});

			if (this.elements.input.val().length > 0)
			{
				value +=  value.length ? widget.options.outputDelimiter + this.elements.input.val() : this.elements.input.val();
			}

			return value;
		},

		_setValue : function(value) {
			var val = this.element.val();

			if(val !== value){
				this.options.openedByClick = false;
				this.element.val(value);
				this._trigger('change');
			}
		},

		// @name text for tag
		_createTag: function (key, fullValue) {
			var
				oEmail = AddressUtils.getEmailParts(fullValue, true),
				name = oEmail.name ? oEmail.name : oEmail.email,
				title = fullValue ?
					' title="' + TextUtils.i18n('COMPOSE/TOOLTIP_DOUBLECLICK_TO_EDIT', {'EMAIL': fullValue.replace(/"/g, '&quot;')}) + '"' :
					'',
				deleteTitle = TextUtils.i18n('COMPOSE/TOOLTIP_CLICK_TO_DELETE'),
				deleteHtml = '<a href="javascript:void(0);" class="ficon" title="' + deleteTitle + '">&#x2716;</a>',
				li = null,
				widget = this
			;

			if (name !== undefined)
			{
				li = $('<li data-inputosaurus="' + key + '"' + title + '>' + deleteHtml + '<span>' + name + '</span></li>');
				if (!widget.options.mobileDevice)
				{
					li.data('full', fullValue);
					li.draggable({
						revert: 'invalid',
						helper: function () {
							var
								oLiDraggable = $(this),
								oParent = oLiDraggable.parent().parent().parent().parent(),
								oLiClone = oLiDraggable.clone()
							;
							oLiDraggable.css('visibility', 'hidden');
							return $('<div class="inputosaurus-moving-container"><div>').appendTo(oParent).append(oLiClone);
						},
						start: function (event, ui) {
							ui.helper.__widget = widget;
						},
						stop: function (event, ui) {
							var oLiDraggable = $(this);
							oLiDraggable.css('visibility', 'visible');
						}
					});
				}
			}

			return li;
		},

		_renderTags : function() {
			var self = this;

			this.elements.ul.find('li:not(.inputosaurus-required)').remove();

			$.each(this._chosenValues, function (k, v) {
					var el = self._createTag(v.key, v.value);
					self.elements.ul.find('li.inputosaurus-input').before(el);
			});
		},

		_removeTag : function(ev) {
			var widget = (ev && ev.data.widget) || this;

			widget._removeLiTag($(ev.currentTarget).closest('li'), widget);
			widget.options.openedByClick = false;
		},

		_removeLiTag : function ($li, widget) {
			var key = $li.data('inputosaurus'),
				indexFound = false;

			$.each(widget._chosenValues, function(k,v) {
				if(key === v.key){
					indexFound = k;
				}
			});

			indexFound !== false && widget._chosenValues.splice(indexFound, 1);

			widget._setValue(widget._buildValue());

			$li.remove();
			setTimeout(function () {
				widget.elements.input.focus();
			}, 100);

			widget._resizeInput();
		},

		focus : function () {
			this.elements.input.focus();
		},

		_focus : function(ev) {
			var
				widget = (ev && ev.data.widget) || this,
				li = (ev && ev.target) ? $(ev.target).closest('li') : null
			;

			if (li && li.is('li')) {
				li.find('a').focus();
			}
			if (!ev || !$(ev.target).closest('li').data('inputosaurus')) {
				widget.elements.input.focus();
			}
		},

		_click : function(ev) {
			var widget = (ev && ev.data.widget) || this ;

			if (widget.elements.input.val() === '')
			{
				if (!widget.options.openedByClick)
				{
					widget.elements.input.autocomplete("option", "minLength", 0); //for triggering search on empty field
					widget.elements.input.autocomplete("search");
					setTimeout(function () {
						widget.elements.input.autocomplete("option", "minLength", 1);
					}.bind(this), 0);
					widget.options.openedByClick = true;
				}
				else
				{
					widget.options.openedByClick = false;
				}
			}
		},

		_tagFocus : function(ev) {
			$(ev.currentTarget).parent()[ev.type === 'focusout' ? 'removeClass' : 'addClass']('inputosaurus-selected');
		},

		refresh : function() {
			var val = this.element.val(),
				values = [],
				aRecipients = AddressUtils.getArrayRecipients(val, true);

			values = _.map(aRecipients, function (oRecipient) {
				return oRecipient.fullEmail;
			});
			this._chosenValues = [];

			$.isFunction(this.options.parseHook) && (values = this.options.parseHook(values));

			this._setChosen(values);
			this._renderTags();
			this.elements.input.val('');
			this._resizeInput();
		},

		_attachEvents : function() {
			var widget = this;
			
			this.elements.input.on('keyup.inputosaurus', {widget : widget}, this._inputKeypress);
			this.elements.input.on('keydown.inputosaurus', {widget : widget}, this._inputKeypress);
			this.elements.input.on('change.inputosaurus', {widget : widget}, this._inputKeypress);
			this.elements.input.on('focus.inputosaurus', {widget : widget}, this._inputFocus);
			this.options.parseOnBlur && this.elements.input.on('blur.inputosaurus', {widget : widget}, this.parseInput);
			
			this.elements.input.on('focus.inputosaurus', function () {
				widget.elements.input.show();
			});

			this.elements.ul.on('click.inputosaurus', {widget : widget}, this._click);
			this.elements.ul.on('click.inputosaurus', {widget : widget}, this._focus);
			this.elements.ul.on('click.inputosaurus', 'a', {widget : widget}, this._removeTag);
			this.elements.ul.on('dblclick.inputosaurus', 'li', {widget : widget}, this._editTag);
			this.elements.ul.on('focus.inputosaurus', 'a', {widget : widget}, this._tagFocus);
			this.elements.ul.on('blur.inputosaurus', 'a', {widget : widget}, this._tagFocus);
			this.elements.ul.on('keydown.inputosaurus', 'a', {widget : widget}, this._tagKeypress);
			
			if (widget.options.mobileDevice)
			{
				this.elements.ul.on('tap.inputosaurus', {widget : widget}, this._focus);
				this.elements.ul.on('doubletap.inputosaurus', 'li', {widget : widget}, this._editTag);
			}
		},

		_destroy: function() {
			this.elements.input.unbind('.inputosaurus');

			this.elements.ul.replaceWith(this.element);

		}
	};

	$.widget("ui.inputosaurus", inputosaurustext);
})(jQuery);

