'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Browser = require('core/js/Browser.js')
;

/**
 * @constructor
 * 
 * @param {Object} oOptions
 */
function CCrea(oOptions)
{
	this.oOptions = _.extend({
		'creaId': 'creaId',
		'fontNameArray': ['Tahoma'],
		'defaultFontName': 'Tahoma',
		'defaultFontSize': 3,
		'dropableArea': null,
		'isRtl': false,
		'onChange': function () {},
		'onCursorMove': function () {},
		'onFocus': function () {},
		'onBlur': function () {},
		'onUrlIn': function () {},
		'onUrlOut': function () {},
		'onImageSelect': function () {},
		'onImageBlur': function () {},
		'onItemOver':  null,
		'onItemOut':  null,
		'openInsertLinkDialog':  function () {},
		'onUrlClicked': false
	}, (typeof oOptions === 'undefined') ? {} : oOptions);
}

/**
 * @type {Object}
 */
CCrea.prototype.oOptions = {};

/**
 * @type {Object}
 */
CCrea.prototype.$container = null;

/**
 * @type {Object}
 */
CCrea.prototype.$editableArea = null;

CCrea.prototype.aEditableAreaHtml = [];

CCrea.prototype.iUndoRedoPosition = 0;

CCrea.prototype.bEditable = false;

CCrea.prototype.bFocused = false;

CCrea.prototype.bEditing = false;

/**
 * @type {Array}
 */
CCrea.prototype.aSizes = [
	{inNumber: 1, inPixels: 10},
	{inNumber: 2, inPixels: 13},
	{inNumber: 3, inPixels: 16},
	{inNumber: 4, inPixels: 18},
	{inNumber: 5, inPixels: 24},
	{inNumber: 6, inPixels: 32},
	{inNumber: 7, inPixels: 48}
];

CCrea.prototype.bInUrl = false;

CCrea.prototype.oCurrLink = null;

CCrea.prototype.oCurrImage = null;

CCrea.prototype.bInImage = false;

CCrea.prototype.sBasicFontName = '';
CCrea.prototype.sBasicFontSize = '';
CCrea.prototype.sBasicDirection = '';

/**
 * Creates editable area.
 * 
 * @param {boolean} bEditable
 */
CCrea.prototype.start = function (bEditable)
{
	function isValidURL(sUrl)
	{
		var oRegExp = /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/;

		return oRegExp.test(sUrl);
	}

	function isCorrectEmail(sValue)
	{
		return !!(sValue.match(/^[A-Z0-9\"!#\$%\^\{\}`~&'\+\-=_\.]+@[A-Z0-9\.\-]+$/i));
	}

	this.$container = $('#' + this.oOptions.creaId);
	this.$editableArea = $('<div></div>').addClass('crea-content-editable')
			.prop('contentEditable', 'true').appendTo(this.$container);

	var self = this;

	this.$editableArea.on('focus', function () {
		self.bFocused = true;
	});
	this.$editableArea.on('blur', function () {
		self.bFocused = false;
	});

	this.$editableArea.on('click', 'img', function (ev) {
		var oImage = $(this);
		self.bInImage = true;
		self.oCurrImage = oImage;
		self.oOptions.onImageSelect(oImage, ev);
		ev.stopPropagation();
	});
	this.$editableArea.on('click', function (ev) {
		self.bInImage = false;
		self.oCurrImage = null;
		self.oOptions.onImageBlur();
	});

	if (self.oOptions.onItemOver !== null)
	{
		this.$editableArea.on('mouseover', function (ev) {
			self.oOptions.onItemOver(ev);
		});
	}
	if (self.oOptions.onItemOver !== null)
	{
		this.$editableArea.on('mouseout', function (ev) {
			self.oOptions.onItemOut(ev);
		});
	}

	this.$editableArea.on('cut paste', function () {
		self.bEditing = true;
		self.editableSave();
		_.defer(function () {
			self.editableSave();
		});
	});
	this.$editableArea.on('paste', function (oEvent) {
		oEvent = oEvent.originalEvent || oEvent;

		if (oEvent.clipboardData)
		{
			var
				sText = oEvent.clipboardData.getData('text/plain'),
				sHtml = oEvent.clipboardData.getData('text/html'),
				aHtml
			;

			if (self.pasteImage(oEvent))
			{
				oEvent.preventDefault();
			}
			else
			{
				if (isValidURL(sText))
				{
					oEvent.preventDefault();
					self.execCom('insertHTML', '<a href="' + sText + '">' + sText + '</a>');
				}
				else if (isCorrectEmail(sText))
				{
					oEvent.preventDefault();
					self.execCom('insertHTML', '<a href="mailto:' + sText + '">' + sText + '</a>');
				}
				else if (sHtml !== '')
				{
					oEvent.preventDefault();

					aHtml = sHtml.split(/<!--StartFragment-->|<!--EndFragment-->/gi);
					if (aHtml.length === 3)
					{
						sHtml = aHtml[1];
					}
					sHtml = self.replacePToBr(sHtml);

					self.execCom('insertHTML', sHtml);
				}
			}
		}
	});
	this.$editableArea.on('keydown', function(oEvent) {
		var
			iKey = oEvent.keyCode || oEvent.which || oEvent.charCode || 0,
			bCtrlKey = oEvent.ctrlKey || oEvent.metaKey,
			bAltKey =  oEvent.altKey,
			bShiftKey = oEvent.shiftKey,
			sLink = ''
		;

		self.bEditing = true;

		if((bShiftKey && bCtrlKey && iKey === Enums.Key.z) || (bCtrlKey && iKey === Enums.Key.y))
		{
			oEvent.preventDefault();

			self.editableRedo();
		}
		else if(bCtrlKey && !bAltKey && iKey === Enums.Key.z)
		{
			oEvent.preventDefault();

			self.editableUndo();
		}
		else if (bCtrlKey && (iKey === Enums.Key.k || iKey === Enums.Key.b || iKey === Enums.Key.i || iKey === Enums.Key.u))
		{
			oEvent.preventDefault();
			switch (iKey)
			{
				case Enums.Key.k:
					sLink = self.getSelectedText();
					if (isValidURL(sLink))
					{
						self.insertLink(sLink);
					}
					else if (isCorrectEmail(sLink))
					{
						self.insertLink('mailto:' + sLink);
					}
					else
					{
						self.oOptions.openInsertLinkDialog();
					}
					break;
				case Enums.Key.b:
					self.bold();
					break;
				case Enums.Key.i:
					self.italic();
					break;
				case Enums.Key.u:
					self.underline();
					break;
			}
		}
		else if (!bAltKey && !bShiftKey && !oEvent.altKey)
		{
			if (iKey === Enums.Key.Space || iKey === Enums.Key.Enter)
			{
				self.editableSave();
			}
			else
			{
				self.oOptions.onChange();
			}
		}
	});

	this.initContentEditable();
	this.setEditable(bEditable);
};

CCrea.prototype.clearUndoRedo = function ()
{
	this.aEditableAreaHtml = [];
	this.iUndoRedoPosition = 0;
	this.bEditing = false;
};

CCrea.prototype.isUndoAvailable = function ()
{
	return this.iUndoRedoPosition > 0;
};

CCrea.prototype.clearRedo = function ()
{
	this.aEditableAreaHtml = this.aEditableAreaHtml.slice(0, this.iUndoRedoPosition + 1);
};

CCrea.prototype.editableSave = function ()
{
	var
		sEditableHtml = this.$editableArea.html(),
		oLastSaved = _.last(this.aEditableAreaHtml),
		sLastSaved = oLastSaved ? oLastSaved[0] : ''
	;

	if (sEditableHtml !== sLastSaved)
	{
		this.clearRedo();
		this.aEditableAreaHtml.push([sEditableHtml, this.getCaretPos(this.$editableArea[0])]);
		this.iUndoRedoPosition = this.aEditableAreaHtml.length - 1;
		this.oOptions.onChange();
	}
};

CCrea.prototype.editableUndo = function ()
{
	if (this.iUndoRedoPosition === this.aEditableAreaHtml.length - 1)
	{
		this.editableSave();
	}

	if (this.iUndoRedoPosition > 0)
	{
		this.iUndoRedoPosition--;
		this.$editableArea.html(this.aEditableAreaHtml[this.iUndoRedoPosition]);
		this.setCaretPos(this.$editableArea[0], this.aEditableAreaHtml[this.iUndoRedoPosition][1]);
	}
};

CCrea.prototype.editableRedo = function ()
{
	if (this.iUndoRedoPosition < (this.aEditableAreaHtml.length - 1))
	{
		this.iUndoRedoPosition++;
		this.$editableArea.html(this.aEditableAreaHtml[this.iUndoRedoPosition]);
		this.setCaretPos(this.$editableArea[0], this.aEditableAreaHtml[this.iUndoRedoPosition] ? this.aEditableAreaHtml[this.iUndoRedoPosition][1] : {});
	}
};

CCrea.prototype.getCaretPos = function (oContainerEl)
{
	var
		oSel = null,
		oRange = {},
		oPreSelectionRange = {},
		iStart = 0,
		oCaretPos = {}
	;

	if (window.getSelection && document.createRange)
	{
		oSel = window.getSelection();
		if (oSel.rangeCount > 0)
		{
			oRange = oSel.getRangeAt(0);
			oPreSelectionRange = oRange.cloneRange();
			oPreSelectionRange.selectNodeContents(oContainerEl);
			oPreSelectionRange.setEnd(oRange.startContainer, oRange.startOffset);
			iStart = oPreSelectionRange.toString().length;
			oCaretPos = {
				start: iStart,
				end: iStart + oRange.toString().length
			};
		}
	}
	else if (document.selection && document.body.createTextRange)
	{
		oRange = document.selection.createRange();
		oPreSelectionRange = document.body.createTextRange();
		oPreSelectionRange.moveToElementText(oContainerEl);
		if (typeof(oPreSelectionRange.setEndPoint) === 'function')
		{
			oPreSelectionRange.setEndPoint("EndToStart", oRange);
		}
		iStart = oPreSelectionRange.text.length;
		oCaretPos = {
			start: iStart,
			end: iStart + oRange.text.length
		};
	}

	return oCaretPos;
};

CCrea.prototype.setCaretPos = function(oContainerEl, oSavedSel)
{
	if (window.getSelection && document.createRange)
	{
		var
			oNodeStack = [oContainerEl],
			oNode = {},
			oSel = {},
			bFoundStart = false,
			bStop = false,
			iCharIndex = 0,
			iNextCharIndex = 0,
			iChildNodes = 0,
			oRange = document.createRange()
		;

		oRange.setStart(oContainerEl, 0);
		oRange.collapse(true);

		oNode = oNodeStack.pop();
		while (!bStop && oNode)
		{
			if (oNode.nodeType === 3)
			{
				iNextCharIndex = iCharIndex + oNode.length;
				if (!bFoundStart && oSavedSel.start >= iCharIndex && oSavedSel.start <= iNextCharIndex)
				{
					oRange.setStart(oNode, oSavedSel.start - iCharIndex);
					bFoundStart = true;
				}
				if (bFoundStart && oSavedSel.end >= iCharIndex && oSavedSel.end <= iNextCharIndex)
				{
					oRange.setEnd(oNode, oSavedSel.end - iCharIndex);
					bStop = true;
				}
				iCharIndex = iNextCharIndex;
			}
			else
			{
				iChildNodes = oNode.childNodes.length;
				while (iChildNodes--)
				{
					oNodeStack.push(oNode.childNodes[iChildNodes]);
				}
			}
			oNode = oNodeStack.pop();
		}

		oSel = window.getSelection();
		oSel.removeAllRanges();
		oSel.addRange(oRange);
	}
	else if (document.selection && document.body.createTextRange)
	{
		var oTextRange = document.body.createTextRange();

		oTextRange.moveToElementText(oContainerEl);
		oTextRange.collapse(true);
		oTextRange.moveEnd("character", oSavedSel.end);
		oTextRange.moveStart("character", oSavedSel.start);
		oTextRange.select();
	}
};

/**
 * Sets tab index.
 * 
 * @param {string} sTabIndex
 */
CCrea.prototype.setTabIndex = function (sTabIndex)
{
	if (sTabIndex)
	{
		this.$editableArea.attr('tabindex', sTabIndex);
	}
};

/**
 * Initializes properties.
 */
CCrea.prototype.initContentEditable = function ()
{
	this.$editableArea.bind({
		'mousemove': _.bind(this.storeSelectionPosition, this),
		'mouseup': _.bind(this.onCursorMove, this),
		'keydown': _.bind(this.onButtonPressed, this),
		'keyup': _.bind(this.onCursorMove, this),
		'click': _.bind(this.onClickWith, this),
		'focus': this.oOptions.onFocus,
		'blur': this.oOptions.onBlur
	});

	if (window.File && window.FileReader && window.FileList)
	{
		 if (this.oOptions.enableDrop) {
			this.$editableArea.bind({
			   'dragover': _.bind(this.onDragOver, this),
			   'dragleave': _.bind(this.onDragLeave, this),
			   'drop': _.bind(this.onFileSelect, this)
			});
		 }
	}

	var self = this,
		lazyScroll = _.debounce(function () {
		self.oCurrLink = null;
		self.bInUrl = false;
		self.oOptions.onUrlOut();
	}, 300);
	$('html, body').on('scroll', lazyScroll);
};

/**
 * Starts cursor move handlers.
 * @param {Object} ev
 */
CCrea.prototype.onCursorMove = function (ev)
{
	var iKey = -1;
	if (window.event)
	{
		iKey = window.event.keyCode;
	}
	else if (ev)
	{
		iKey = ev.which;
	}

	if (iKey === 13) // Enter
	{
		this.breakQuotes(ev);
	}

	if (iKey === 17) // Cntr
	{
		this.$editableArea.find('a').css('cursor', 'inherit');
	}

	if (iKey === 8) // BackSpace
	{
		this.uniteWithNextQuote(ev);
	}

	if (iKey === 46 && Browser.chrome) // Delete
	{
		this.uniteWithPrevQuote(ev);
	}


	this.storeSelectionPosition();
	this.oOptions.onCursorMove();
};

/**
 * Starts when clicked.
 * @param {Object} oEvent
 */
CCrea.prototype.onClickWith = function (oEvent)
{
	if(oEvent.ctrlKey) {
		if (oEvent.target.nodeName === 'A'){
			window.open(oEvent.target.href,'_blank');
		}
	}
	this.checkAnchorNode();
};

/**
 * Starts when key pressed.
 * @param {Object} oEvent
 */
CCrea.prototype.onButtonPressed = function (oEvent)
{
	var iKey = -1;
	if (window.event)
	{
		iKey = window.event.keyCode;
	}
	else if (oEvent)
	{
		iKey = oEvent.which;
	}

	if (iKey === 17) // Cntr
	{
		this.$editableArea.find('a').css('cursor', 'pointer');
	}
};

/**
 * Starts cursor move handlers.
 * @param {Object} oEvent
 */
CCrea.prototype.onFileSelect = function (oEvent)
{
	oEvent = (oEvent && oEvent.originalEvent ?
		oEvent.originalEvent : oEvent) || window.event;

	if (oEvent)
	{
		oEvent.stopPropagation();
		oEvent.preventDefault();

		var
			oReader = null,
			oFile = null,
			aFiles = (oEvent.files || (oEvent.dataTransfer ? oEvent.dataTransfer.files : null)),
			self = this
		;

		if (aFiles && 1 === aFiles.length && this.checkIsImage(aFiles[0]))
		{
			oFile = aFiles[0];

			oReader = new window.FileReader();
			oReader.onload = (function () {
				return function (oEvent) {
					self.insertImage(oEvent.target.result);
				};
			}());

			oReader.readAsDataURL(oFile);
		}
	}
};

CCrea.prototype.onDragLeave = function ()
{
	this.$editableArea.removeClass('editorDragOver');
};

/**
 * @param {Object} oEvent
 */
CCrea.prototype.onDragOver = function (oEvent)
{
	oEvent.stopPropagation();
	oEvent.preventDefault();

	this.$editableArea.addClass('editorDragOver');
};

/**
 * @param {Object} oEvent
 * @returns {Boolean}
 */
CCrea.prototype.pasteImage = function (oEvent)
{
	var
		oClipboardItems = oEvent.clipboardData && oEvent.clipboardData.items,
		self = this,
		bImagePasted = false
	;

	if (window.File && window.FileReader && window.FileList && oClipboardItems)
	{
		_.each(oClipboardItems, function (oItem) {
			if (self.checkIsImage(oItem) && oItem['getAsFile']) {
				var
					oReader = null,
					oFile = oItem['getAsFile']()
				;
				if (oFile)
				{
					oReader = new window.FileReader();
					oReader.onload = (function () {
						return function (oEvent) {
							self.insertImage(oEvent.target.result);
						};
					}());

					oReader.readAsDataURL(oFile);
					bImagePasted = true;
				}
			}
		});
	}

	return bImagePasted;
};

/**
 * @param {Object} oItem
 * @return {boolean}
 */
CCrea.prototype.checkIsImage = function (oItem)
{
	return oItem && oItem.type && 0 === oItem.type.indexOf('image/');
};

/**
 * Sets plain text to rich editor.
 * 
 * @param {string} sText
 */
CCrea.prototype.setPlainText = function (sText)
{
	if (typeof sText !== 'string')
	{
		sText = '';
	}

	if (this.$editableArea)
	{
		this.$editableArea.empty().text(sText).css('white-space', 'pre');
		this.editableSave();
	}
};

/**
 * Sets text to rich editor.
 * 
 * @param {string} sText
 */
CCrea.prototype.setText = function (sText)
{
	if (typeof sText !== 'string')
	{
		sText = '';
	}

	if (this.$editableArea)
	{
		if (sText.length === 0)
		{
			sText = '<br />';
		}

		var
			oText = $(sText),
			oOuter = $(sText),
			oChildren = oOuter.children(),
			oInner = oChildren.first(),
			bOuterWrapper = oOuter.length === 1 && oOuter.data('crea') === 'font-wrapper',
			bInnerWrapper = oOuter.length === 1 && oChildren.length === 1 &&
			oOuter.data('xDivType') === 'body' && oInner.data('crea') === 'font-wrapper'
		;

		if (bOuterWrapper)
		{
			this.setBasicStyles(oOuter.css('font-family'), oOuter.css('font-size'), oOuter.css('direction'));
			oText = oOuter.contents();
		}
		else if (bInnerWrapper)
		{
			this.setBasicStyles(oInner.css('font-family'), oInner.css('font-size'), oInner.css('direction'));
			oText = oInner.contents();
		}
		else
		{
			this.setBasicStyles(this.oOptions.defaultFontName, this.convertFontSizeToPixels(this.oOptions.defaultFontSize), this.oOptions.isRtl ? 'rtl' : 'ltr');
		}

		this.$editableArea.empty().append(oText).css('white-space', 'normal');
		this.editableSave();
	}
};

/**
 * @param {string} sFontName
 * @param {string} sFontSize
 * @param {string} sDirection
 */
CCrea.prototype.setBasicStyles = function (sFontName, sFontSize, sDirection)
{
	this.sBasicFontName = sFontName;
	this.sBasicFontSize = sFontSize;
	this.sBasicDirection = sDirection;

	this.$editableArea.css({
		'font-family': this.getFontNameWithFamily(this.sBasicFontName),
		'font-size': this.sBasicFontSize,
		'direction': this.sBasicDirection
	});
};

/**
 * @param {string} sText
 * @returns {string}
 */
CCrea.prototype.replacePToBr = function (sText)
{
	return sText.replace(/<\/p>/gi, '<br />').replace(/<p [^>]*>/gi, '').replace(/<p>/gi, '');
};

/**
 * Gets plain text from rich editor.
 * 
 * @return {string}
 */
CCrea.prototype.getPlainText = function ()
{
	var sVal = '';

	if (this.$editableArea)
	{
		sVal = this.$editableArea.html()
			.replace(/<style[^>]*>[^<]*<\/style>/gi, '\n')
			.replace(/<br *\/{0,1}>/gi, '\n')
			.replace(/<\/p>/gi, '\n')
			.replace(/<a [^>]*href="([^"]*?)"[^>]*>(.*?)<\/a>/gi, '$2 ($1)')
			.replace(/<[^>]*>/g, '')
			.replace(/&nbsp;/g, ' ')
			.replace(/&lt;/g, '<')
			.replace(/&gt;/g, '>')
			.replace(/&amp;/g, '&')
			.replace(/&quot;/g, '"')
		;
	}

	return sVal;
};

/**
 * Gets text from rich editor.
 * 
 * @param {boolean=} bRemoveSignatureAnchor = false
 * @return {string}
 */
CCrea.prototype.getText = function (bRemoveSignatureAnchor)
{
	var
		$Anchor = null,
		sVal = ''
	;

	if (this.$editableArea)
	{
		if (bRemoveSignatureAnchor)
		{
			$Anchor = this.$editableArea.find('div[data-anchor="signature"]');
			$Anchor.removeAttr('data-anchor');
		}

		sVal = this.$editableArea.html();
		sVal = this.replacePToBr(sVal);
		sVal = '<div data-crea="font-wrapper" style="font-family: ' + this.sBasicFontName + '; font-size: ' + this.sBasicFontSize + '; direction: ' + this.sBasicDirection + '">' + sVal + '</div>';
	}

	return sVal;
};

/**
 * @param {string} sNewSignatureContent
 * @param {string} sOldSignatureContent
 */
CCrea.prototype.changeSignatureContent = function (sNewSignatureContent, sOldSignatureContent)
{
	var
		$Anchor = this.$editableArea.find('div[data-anchor="signature"]'),
		$NewSignature = $(sNewSignatureContent).closest('div[data-crea="font-wrapper"]'),
		$OldSignature = $(sOldSignatureContent).closest('div[data-crea="font-wrapper"]'),
		sClearOldSignature, sClearNewSignature,
		sAnchorHtml,
		$SignatureContainer,
		$SignatureBlockquoteParent,
		sFoundOldSignature,
		sSignatureContainerHtml,
		$AnchorBlockquoteParent
	;

	/*** there is a signature container in the message ***/
	if ($Anchor.length > 0)
	{
		sAnchorHtml = $Anchor.html();
		/*** previous signature is empty -> append to the container a new signature ***/
		if (sOldSignatureContent === '')
		{
			$Anchor.html(sAnchorHtml + sNewSignatureContent);
		}
		/*** previous signature was found in the container -> replace it with a new ***/
		else if (sAnchorHtml.indexOf(sOldSignatureContent) !== -1)
		{
			$Anchor.html(sAnchorHtml.replace(sOldSignatureContent, sNewSignatureContent));
		}
		/*** new signature is found in the container -> do nothing ***/
		else if (sAnchorHtml.indexOf(sNewSignatureContent) !== -1)
		{
		}
		else
		{
			sClearOldSignature = ($NewSignature.length === 0 || $OldSignature.length === 0) ? sOldSignatureContent : $OldSignature.html();
			sClearNewSignature = ($NewSignature.length === 0 || $OldSignature.length === 0) ? sNewSignatureContent : $NewSignature.html();
			/*** found a previous signature without wrapper -> replace it with a new ***/
			if (sAnchorHtml.indexOf(sClearOldSignature) !== -1)
			{
				$Anchor.html(sAnchorHtml.replace(sClearOldSignature, sNewSignatureContent));
			}
			/*** found a new signature without wrapper -> do nothing ***/
			else if (sAnchorHtml.indexOf(sClearNewSignature) !== -1)
			{
			}
			else
			{
				/*** append the new signature to the end of the container ***/
				$Anchor.html(sAnchorHtml + sNewSignatureContent);
			}
		}
	}
	/*** there is NO signature container in the message ***/
	else
	{
		sFoundOldSignature = sOldSignatureContent;
		$SignatureContainer = this.$editableArea.find('*:contains("' + sFoundOldSignature + '")');
		if ($SignatureContainer.length === 0 && $OldSignature.length > 0)
		{
			sFoundOldSignature = $OldSignature.html();
			$SignatureContainer = this.$editableArea.find('*:contains("' + sFoundOldSignature + '")');
		}

		if ($SignatureContainer.length > 0)
		{
			$SignatureContainer = $($SignatureContainer[0]);
			$SignatureBlockquoteParent = $SignatureContainer.closest('blockquote');
		}

		if ($SignatureBlockquoteParent && $SignatureBlockquoteParent.length === 0)
		{
			$SignatureContainer.html($SignatureContainer.html().replace(sFoundOldSignature, sNewSignatureContent));
		}
		else
		{
			$Anchor = this.$editableArea.find('div[data-anchor="reply-title"]');
			$AnchorBlockquoteParent = ($Anchor.length > 0) ? $($Anchor[0]).closest('blockquote') : $Anchor;
			if ($Anchor.length === 0 || $AnchorBlockquoteParent.length > 0)
			{
				$Anchor = this.$editableArea.find('blockquote');
			}

			if ($Anchor.length > 0)
			{
				$($Anchor[0]).before($('<br /><div data-anchor="signature">' + sNewSignatureContent + '</div><br />'));
			}
			else
			{
				this.$editableArea.append($('<br /><div data-anchor="signature">' + sNewSignatureContent + '</div><br />'));
			}
		}
	}

	this.editableSave();
};

/**
 * @return {boolean}
 */
CCrea.prototype.isFocused = function ()
{
	return this.bFocused;
};

/**
 * Sets focus.
 * @param {boolean} bKeepCurrent
 */
CCrea.prototype.setFocus = function (bKeepCurrent)
{
	var
		aContents = this.$editableArea.contents(),
		iTextNodeType = 3,
		oTextNode = null,
		sText = ''
	;

	this.$editableArea.focus();
	if (bKeepCurrent && $.isArray(this.aRanges) && this.aRanges.length > 0)
	{
		this.restoreSelectionPosition();
	}
	else if (aContents.length > 0)
	{
		if (aContents[0].nodeType === iTextNodeType)
		{
			oTextNode = $(aContents[0]);
		}
		else
		{
			oTextNode = $(document.createTextNode(''));
			$(aContents[0]).before(oTextNode);
		}

		sText = oTextNode.text();
		this.setCursorPosition(oTextNode[0], sText.length);
	}
};

CCrea.prototype.setBlur = function ()
{
	this.$editableArea.blur();
};

/**
 * @param {boolean} bEditable
 */
CCrea.prototype.setEditable = function (bEditable)
{
	if (bEditable)
	{
		this.enableContentEditable();
	}
	else
	{
		this.disableContentEditable();
	}
};

CCrea.prototype.disableContentEditable = function ()
{
	this.bEditable = false;
	this.$editableArea.prop('contentEditable', 'false');
};

CCrea.prototype.enableContentEditable = function ()
{
	this.$editableArea.prop('contentEditable', 'true');
	setTimeout(_.bind(function () {this.bEditable = true;}, this), 0);
};

CCrea.prototype.fixFirefoxCursorBug = function ()
{
	if (Browser.firefox)
	{
		this.disableContentEditable();

		setTimeout(_.bind(function () {this.enableContentEditable();}, this), 0);
	}
};

CCrea.prototype.setRtlDirection = function ()
{
	this.setBasicStyles(this.sBasicFontName, this.sBasicFontSize, 'rtl');
};

CCrea.prototype.setLtrDirection = function ()
{
	this.setBasicStyles(this.sBasicFontName, this.sBasicFontSize, 'ltr');
};

CCrea.prototype.pasteHtmlAtCaret = function (html)
{
	var sel, range;
	if (window.getSelection) {
		// IE9 and non-IE
		sel = window.getSelection();
		if (sel.getRangeAt && sel.rangeCount) {
			range = sel.getRangeAt(0);
			range.deleteContents();

			// Range.createContextualFragment() would be useful here but is
			// only relatively recently standardized and is not supported in
			// some browsers (IE9, for one)
			var el = document.createElement("div");
			el.innerHTML = html;
			var frag = document.createDocumentFragment(), node, lastNode;
			while ( (node = el.firstChild) ) {
				lastNode = frag.appendChild(node);
			}
			range.insertNode(frag);

			// Preserve the selection
			if (lastNode) {
				range = range.cloneRange();
				range.setStartAfter(lastNode);
				range.collapse(true);
				sel.removeAllRanges();
				sel.addRange(range);
			}
		}
	} else if (document.selection && document.selection.type !== "Control") {
		// IE < 9
		range = document.selection.createRange();
		if (range && range.pasteHTML)
		{
			range.pasteHTML(html);
		}
	}
};

/**
 * Executes command.
 * 
 * @param {string} sCmd
 * @param {string=} sParam
 * @param {boolean=} bDontAddToHistory
 * @return {boolean}
 */
CCrea.prototype.execCom = function (sCmd, sParam, bDontAddToHistory)
{
	var
		bRes = false,
		oRange
	;

	if (this.bEditable)
	{
		this.editableSave();

		if (Browser.opera)
		{
			this.restoreSelectionPosition();
		}

		if ('insertHTML' === sCmd && Browser.ie)
		{
			this.pasteHtmlAtCaret(sParam);
		}
		else
		{
			if (typeof sParam === 'undefined')
			{
				bRes = window.document.execCommand(sCmd);
			}
			else
			{
				bRes = window.document.execCommand(sCmd, false, sParam);
			}
		}

		if (Browser.chrome)
		{
			// Chrome need to resave the selection after the operation.
			this.storeSelectionPosition();
			if (sCmd === 'insertHTML' && this.aRanges.length > 0)
			{
				// Chrome selects line after inserted text. Disable do it.
				oRange = this.aRanges[0];
				oRange.setEnd(oRange.startContainer, oRange.startOffset);
				this.restoreSelectionPosition();
			}
		}

		if (!bDontAddToHistory)
		{
			this.editableSave();
		}
	}

	return bRes;
};

/**
 * Inserts html.
 * 
 * @param {string} sHtml
 * @param {boolean} bDontAddToHistory
 */
CCrea.prototype.insertHtml = function (sHtml, bDontAddToHistory)
{
	this.execCom('insertHTML', sHtml, bDontAddToHistory);
};

/**
 * @param {string} sId
 * @param {string} sSrc
 */
CCrea.prototype.changeImageSource = function (sId, sSrc)
{
	this.$editableArea.find('img[id="' + sId + '"]').attr('src', sSrc);
	this.editableSave();
};

/**
 * Inserts link.
 * 
 * @param {string} sLink
 */
CCrea.prototype.insertEmailLink = function (sLink)
{
	this.restoreSelectionPosition();
	if (this.getSelectedText() === '')
	{
		this.execCom('insertHTML', '<a href="mailto:' + sLink + '">' + sLink + '</a>');
	}
	else
	{
		this.insertLink('mailto:' + sLink);
	}
};

/**
 * Inserts link.
 * 
 * @param {string} sLink
 */
CCrea.prototype.insertLink = function (sLink)
{
	sLink = this.normaliseURL(sLink);
	this.restoreSelectionPosition(sLink);

	if (this.getSelectedText() === '' && Browser.ie)
	{
		this.execCom('insertHTML', '<a href="' + sLink + '">' + sLink + '</a>');
	}
	else
	{
		var sCmd = Browser.ie8AndBelow ? 'CreateLink' : 'createlink';
		this.execCom(sCmd, sLink);
	}

	this.changeFocusLink(sLink);
};

/**
 * Removes link.
 */
CCrea.prototype.removeLink = function ()
{
	var sCmd = Browser.ie8AndBelow ? 'Unlink' : 'unlink';
	this.execCom(sCmd);
};

/**
 * Inserts image.
 * 
 * @param {string} sImage
 * @return {boolean}
 */
CCrea.prototype.insertImage = function (sImage)
{
	var sCmd = Browser.ie8AndBelow ? 'InsertImage' : 'insertimage';
	if (!this.isFocused())
	{
		this.setFocus(true);
	}
	else
	{
		this.restoreSelectionPosition();
	}

	return this.execCom(sCmd, sImage);
};

/**
 * Inserts ordered list.
 */
CCrea.prototype.numbering = function ()
{
	this.execCom('InsertOrderedList');
};

/**
 * Inserts unordered list.
 */
CCrea.prototype.bullets = function ()
{
	this.execCom('InsertUnorderedList');
};

/**
 * Inserts horizontal line.
 */
CCrea.prototype.horizontalLine = function ()
{
	this.execCom('InsertHorizontalRule');
};

/**
 * @param {string} sFontName
 */
CCrea.prototype.getFontNameWithFamily = function (sFontName)
{
	var sFamily = '';

	switch (sFontName)
	{
		case 'Arial':
		case 'Arial Black':
		case 'Tahoma':
		case 'Verdana':
			sFamily = ', sans-serif';
			break;
		case 'Courier New':
			sFamily = ', monospace';
			break;
		case 'Times New Roman':
			sFamily = ', serif';
			break;
	}

	return sFontName + sFamily;
};

/**
 * Sets font name.
 * 
 * @param {string} sFontName
 */
CCrea.prototype.fontName = function (sFontName)
{
	var bFirstTime = !this.aRanges;



	this.setFocus(true);
	this.execCom('FontName', this.getFontNameWithFamily(sFontName));

	if (bFirstTime)
	{
		this.setBasicStyles(sFontName, this.sBasicFontSize, this.sBasicDirection);
	}
};

/**
 * Sets font size.
 * 
 * @param {string} sFontSize
 */
CCrea.prototype.fontSize = function (sFontSize)
{
	var bFirstTime = !this.aRanges;

	this.setFocus(true);
	this.execCom('FontSize', sFontSize);

	if (bFirstTime)
	{
		this.setBasicStyles(this.sBasicFontName, this.convertFontSizeToPixels(sFontSize), this.sBasicDirection);
	}
};

/**
 * Sets bold style.
 */
CCrea.prototype.bold = function ()
{
	this.execCom('Bold');
};

/**
 * Sets italic style.
 */
CCrea.prototype.italic = function ()
{
	this.execCom('Italic');
};

/**
 * Sets underline style.
 */
CCrea.prototype.underline = function ()
{
	this.execCom('Underline');
};

/**
 * Sets strikethrough style.
 */
CCrea.prototype.strikeThrough = function ()
{
	this.execCom('StrikeThrough');
};

CCrea.prototype.undo = function ()
{
//		this.execCom('Undo');
	this.editableUndo();
};

CCrea.prototype.redo = function ()
{
//		this.execCom('Redo');
	this.editableRedo();
};

/**
 * Sets left justify.
 */
CCrea.prototype.alignLeft = function ()
{
	this.execCom('JustifyLeft');
};

/**
 * Sets center justify.
 */
CCrea.prototype.center = function ()
{
	this.execCom('JustifyCenter');
};

/**
 * Sets right justify.
 */
CCrea.prototype.alignRight = function ()
{
	this.execCom('JustifyRight');
};

/**
 * Sets full justify.
 */
CCrea.prototype.justify = function ()
{
	this.execCom('JustifyFull');
};

/**
 * Sets text color.
 * 
 * @param {string} sFontColor
 */
CCrea.prototype.textColor = function (sFontColor)
{
	this.execCom('ForeColor', sFontColor);
};

/**
 * Sets background color.
 * 
 * @param {string} sBackColor
 */
CCrea.prototype.backgroundColor = function (sBackColor)
{
	var sCmd = Browser.ie ? 'BackColor' : 'hilitecolor';
	this.execCom(sCmd, sBackColor);
};

/**
 * Removes format.
 */
CCrea.prototype.removeFormat = function ()
{
	this.execCom('removeformat');
};

/**
 * Gets font name from selected text.
 *
 * @return {string}
 */
CCrea.prototype.getFontName = function ()
{
	if (this.bEditable)
	{
		var
			sFontName = window.document.queryCommandValue('FontName'),
			sValidFontName = this.sBasicFontName,
			sFindedFontName = ''
		;

		if (typeof sFontName === 'string')
		{
			sFontName = sFontName.replace(/'/g, '');
			$.each(this.oOptions.fontNameArray, function (iIndex, sFont) {
				if (sFontName.indexOf(sFont) > -1 || sFontName.indexOf(sFont.toLowerCase()) > -1)
				{
					sFindedFontName = sFont;
				}
			});

			if (sFindedFontName !== '')
			{
				sValidFontName = sFindedFontName;
			}
		}
	}

	return sValidFontName;
};

/**
 * @param {number} iFontSizeInNumber
 * 
 * @return {string}
 */
CCrea.prototype.convertFontSizeToPixels = function (iFontSizeInNumber)
{
	var iFontSizeInPixels = 0;

	$.each(this.aSizes, function (iIndex, oSize) {
		if (iFontSizeInPixels === 0 && iFontSizeInNumber <= oSize.inNumber)
		{
			iFontSizeInPixels = oSize.inPixels;
		}
	});

	return iFontSizeInPixels + 'px';
};

/**
 * @param {string} sFontSizeInPixels
 * 
 * @return {number}
 */
CCrea.prototype.convertFontSizeToNumber = function (sFontSizeInPixels)
{
	var
		iFontSizeInPixels = parseInt(sFontSizeInPixels, 10),
		iFontSizeInNumber = 0
	;

	if (iFontSizeInPixels > 0)
	{
		$.each(this.aSizes, function (iIndex, oSize) {
			if (iFontSizeInNumber === 0 && iFontSizeInPixels <= oSize.inPixels)
			{
				iFontSizeInNumber = oSize.inNumber;
			}
		});
	}

	return iFontSizeInNumber;
};

/**
 * Gets font size from selected text.
 * 
 * @return {number}
 */
CCrea.prototype.getFontSizeInNumber = function ()
{
	var
		sFontSizeInNumber = '',
		iFontSizeInNumber = 0
	;

	if (this.bEditable)
	{
		sFontSizeInNumber = window.document.queryCommandValue('FontSize');
		iFontSizeInNumber = parseInt(sFontSizeInNumber, 10);
	}

	if (isNaN(iFontSizeInNumber) || iFontSizeInNumber <= 0)
	{
		iFontSizeInNumber = this.convertFontSizeToNumber(this.sBasicFontSize);
	}

	return iFontSizeInNumber;
};

/**
 * @param {string} sHref
 */
CCrea.prototype.changeLink = function (sHref)
{
	var
		sNormHref = this.normaliseURL(sHref),
		oCurrLink = $(this.oCurrLink)
	;

	if (this.oCurrLink)
	{
		if (oCurrLink.attr('href') === oCurrLink.text())
		{
			oCurrLink.text(sNormHref);
		}
		if (this.oCurrLink.tagName === 'A')
		{
			oCurrLink.attr('href', sNormHref);
		}
		else
		{
			oCurrLink.parent().attr('href', sNormHref);
		}

		this.oCurrLink = null;
		this.bInUrl = false;
	}
};

CCrea.prototype.removeCurrentLink = function ()
{
	if (this.oCurrLink && document.createRange && window.getSelection)
	{
		var
			oRange = document.createRange(),
			oSel = window.getSelection()
		;

		oRange.selectNodeContents(this.oCurrLink);
		oSel.removeAllRanges();
		oSel.addRange(oRange);

		this.removeLink();
		this.oCurrLink = null;
		this.bInUrl = false;
		this.oOptions.onUrlOut();
	}
};

/**
 * Fix for FF - execCommand inserts broken link, if it is present not Latin.
 * 
 * @param {string} sLink
 */
CCrea.prototype.changeFocusLink = function (sLink)
{
	var
		oSel = null,
		oFocusNode = null
	;

	if (Browser.firefox && window.getSelection)
	{
		oSel = window.getSelection();
		oFocusNode = oSel.focusNode ? oSel.focusNode.parentElement : null;
		if (oFocusNode && oFocusNode.tagName === 'A')
		{
			$(oFocusNode).attr('href', sLink);
		}
	}
};

CCrea.prototype.removeCurrentImage = function ()
{
	if (this.oCurrImage)
	{
		this.oCurrImage.remove();
		this.oCurrImage = null;
		this.bInImage = false;
		this.oOptions.onImageBlur();
	}
};

CCrea.prototype.changeCurrentImage = function (aParams)
{
	if (this.oCurrImage && aParams !== undefined)
	{
		var image = this.oCurrImage;
		$.each(aParams, function (key, value) {
			image.css(key, value);
		});
	}
};

CCrea.prototype.showImageTooltip = function (aParams)
{
	if (this.oCurrImage && aParams !== undefined)
	{
		var image = this.oCurrImage;
		$.each(aParams, function (key, value) {
			image.css(key, value);
		});
	}
};

/**
 * @param {string} sText
 * @return {string}
 */
CCrea.prototype.normaliseURL = function (sText)
{
	return sText.search(/^https?:\/\/|^mailto:|^tel:/g) !== -1 ? sText : 'http://' + sText;
};

/**
 * @return {string}
 */
CCrea.prototype.getSelectedText = function ()
{
	var
		sText = '',
		oSel = null
	;

	if (window.getSelection)
	{
		oSel = window.getSelection();
		if (oSel.rangeCount > 0)
		{
			sText = oSel.getRangeAt(0).toString();
		}
	}

	return sText;
};

/**
 * Stores selection position.
 */
CCrea.prototype.storeSelectionPosition = function ()
{
	var aNewRanges = this.getSelectionRanges();
	if ($.isArray(aNewRanges) && aNewRanges.length > 0)
	{
		this.aRanges = aNewRanges;
	}
};

/**
 * @return {Array}
 */
CCrea.prototype.editableIsActive = function ()
{
	return !!($(document.activeElement).hasClass('crea-content-editable') || $(document.activeElement).children().first().hasClass('crea-content-editable'));
};

/**
 * @return {Array}
 */
CCrea.prototype.getSelectionRanges = function ()
{
	var
		aRanges = []
	;

	if (window.getSelection && this.editableIsActive())
	{
		var
			oSel = window.getSelection(),
			oRange = null,
			iIndex = 0,
			iLen = oSel.rangeCount
		;

		for (; iIndex < iLen; iIndex++)
		{
			oRange = oSel.getRangeAt(iIndex);
			aRanges.push(oRange);
		}
	}

	return aRanges;
};


CCrea.prototype.checkAnchorNode = function ()
{
	if (window.getSelection && this.editableIsActive())
	{
		var
			oSel = window.getSelection(),
			oCurrLink = null
		;

		if (oSel.anchorNode && (oSel.anchorNode.parentElement || oSel.anchorNode.parentNode))
		{
			oCurrLink = oSel.anchorNode.parentElement || oSel.anchorNode.parentNode;

			if (oCurrLink.tagName === 'A' || oCurrLink.parentNode.tagName === 'A' || oCurrLink.parentElement.tagName === 'A')
			{
				if (!this.bInUrl || oCurrLink !== this.oCurrLink)
				{
					this.oCurrLink = oCurrLink;
					this.bInUrl = true;
					this.oOptions.onUrlIn($(oCurrLink));
				}
				else if (this.bInUrl && oCurrLink === this.oCurrLink)
				{
					this.oCurrLink = null;
					this.bInUrl = false;
					this.oOptions.onUrlOut();
				}
			}
			else if (this.bInUrl)
			{
				this.oCurrLink = null;
				this.bInUrl = false;
				this.oOptions.onUrlOut();
			}
		}
	}
};

/**
 * Restores selection position.
 * 
 * @param {string} sText
 */
CCrea.prototype.restoreSelectionPosition = function (sText)
{
	var
		sRangeText = '',
		oSel = null,
		oRange = null,
		oNode = null
	;

	sRangeText = this.setSelectionRanges(this.aRanges);
	if (window.getSelection && $.isArray(this.aRanges))
	{
		sText = (sText !== undefined) ? sText : '';
		if (Browser.firefox && sRangeText === '' && sText !== '')
		{
			if (window.getSelection && window.getSelection().getRangeAt)
			{
				oSel = window.getSelection();
				if (oSel.getRangeAt && oSel.rangeCount > 0)
				{
					oRange = oSel.getRangeAt(0);
					oNode = oRange.createContextualFragment(sText);
					oRange.insertNode(oNode);
				}
			}
			else if (document.selection && document.selection.createRange)
			{
				document.selection.createRange().pasteHTML(sText);
			}
		}
	}
};

/**
 * @param {Array} aRanges
 * @return {string}
 */
CCrea.prototype.setSelectionRanges = function (aRanges)
{
	var
		oSel = null,
		oRange = null,
		iIndex = 0,
		iLen = 0,
		sRangeText = ''
	;

	if (window.getSelection && $.isArray(aRanges))
	{
		iLen = aRanges.length;

		oSel = window.getSelection();

		if (!Browser.ie10AndAbove)
		{
			oSel.removeAllRanges();
		}

		for (; iIndex < iLen; iIndex++)
		{
			oRange = aRanges[iIndex];
			if (oRange)
			{
				oSel.addRange(oRange);
				sRangeText += '' + oRange;
			}
		}

	}

	return sRangeText;
};

CCrea.prototype.uniteWithNextQuote = function ()
{
	var
		oSel = window.getSelection ? window.getSelection() : null,
		eFocused = oSel ? oSel.focusNode : null,
		eBlock = eFocused ? this.getLastBlockQuote(eFocused) : null,
		oNext = eBlock ? $(eBlock).next() : null,
		eNext = (oNext && oNext.length > 0 && oNext[0].tagName === 'BLOCKQUOTE') ? oNext[0] : null,
		aChildren = [],
		iIndex = 0,
		iLen = 0,
		eChild = null
	;

	if (eBlock && eNext)
	{
		$('<br />').appendTo(eBlock);

		aChildren = $(eNext).contents();
		iLen = aChildren.length;

		for (iIndex = 0; iIndex < iLen; iIndex++)
		{
			eChild = aChildren[iIndex];
			$(eChild).appendTo(eBlock);
		}

		$(eNext).remove();
	}
};

CCrea.prototype.uniteWithPrevQuote = function ()
{
	var
		oSel = window.getSelection ? window.getSelection() : null,
		eFocused = oSel ? oSel.focusNode : null,
		eBlock = eFocused ? this.getLastBlockQuote(eFocused) : null
	;

	this.getPrevAndUnite(eBlock);
	this.getPrevAndUnite(eBlock);
};

/**
 * @param {Object} eBlock
 */
CCrea.prototype.getPrevAndUnite = function (eBlock)
{
	var
		oPrev = eBlock ? $(eBlock).prev() : null,
		ePrev = (oPrev && oPrev.length > 0 && oPrev[0].tagName === 'BLOCKQUOTE') ? oPrev[0] : null,
		aChildren = [],
		iIndex = 0,
		iLen = 0,
		eChild = null
	;

	if (eBlock && ePrev)
	{
		$('<br />').prependTo(eBlock);

		aChildren = $(ePrev).contents();
		iLen = aChildren.length;

		for (iIndex = iLen - 1; iIndex > 0; iIndex--)
		{
			eChild = aChildren[iIndex];
			$(eChild).prependTo(eBlock);
		}

		$(ePrev).remove();
	}
};

/**
 * @param {Object} eFocused
 * @return {Object}
 */
CCrea.prototype.getLastBlockQuote = function (eFocused)
{
	var
		eCurrent = eFocused,
		eBlock = null
	;

	while (eCurrent && eCurrent.parentNode)
	{
		if (eCurrent.tagName === 'BLOCKQUOTE')
		{
			eBlock = eCurrent;
		}
		eCurrent = eCurrent.parentNode;
	}

	return eBlock;
};

/**
 * @param {Object} ev
 */
CCrea.prototype.breakQuotes = function (ev)
{
	var
		oSel = window.getSelection ? window.getSelection() : null,
		eFocused = oSel ? oSel.focusNode : null,
		eBlock = eFocused ? this.getLastBlockQuote(eFocused) : null
	;

	if (eFocused && eBlock)
	{
		this.breakBlocks(eFocused, eBlock, oSel.focusOffset);
	}
};

/**
 * @param {Object} eStart
 * @param {number} iStartOffset
 */
CCrea.prototype.setCursorPosition = function (eStart, iStartOffset)
{
	if (document.createRange && window.getSelection)
	{
		var
			oRange = document.createRange(),
			oSel = window.getSelection()
		;

		oSel.removeAllRanges();

		oRange.setStart(eStart, iStartOffset);
		oRange.setEnd(eStart, iStartOffset);
		oRange.collapse(true);

		oSel.addRange(oRange);

		this.aRanges = [oRange];
	}
};

/**
 * @param {Object} eNode
 * @return {Object}
 */
CCrea.prototype.cloneNode = function (eNode)
{
	var
		$clonedNode = null,
		sTagName = ''
	;

	try
	{
		$clonedNode = $(eNode).clone();
	}
	catch (er)
	{
		sTagName = eNode.tagName;
		$clonedNode = $('<' + sTagName + '></' + sTagName + '>');
	}

	return $clonedNode;
};

/**
 * @param {Object} eFocused
 * @param {Object} eBlock
 * @param {number} iFocusOffset
 */
CCrea.prototype.breakBlocks = function (eFocused, eBlock, iFocusOffset)
{
	var
		eCurrent = eFocused,
		eCurChild = null,
		aChildren = [],
		iIndex = 0,
		iLen = 0,
		eChild = null,
		bBeforeCurrent = true,
		$firstParent = null,
		$secondParent = null,
		$first = null,
		$second = null,
		bLast = false,
		bContinue = true,
		$span = null
	;

	while (bContinue && eCurrent.parentNode)
	{
		$first = $firstParent;
		$second = $secondParent;

		$firstParent = this.cloneNode(eCurrent).empty();
		$secondParent = this.cloneNode(eCurrent).empty();

		aChildren = $(eCurrent).contents();
		iLen = aChildren.length;
		bBeforeCurrent = true;

		if (eCurChild === null)
		{
			eCurChild = aChildren[iFocusOffset];
		}
		if (iLen === 0)
		{
			$firstParent = null;
		}

		for (iIndex = 0; iIndex < iLen; iIndex++)
		{
			eChild = aChildren[iIndex];
			if (eChild === eCurChild)
			{
				if ($first === null)
				{
					if (!(iIndex === iFocusOffset && eChild.tagName === 'BR'))
					{
						$(eChild).appendTo($secondParent);
					}
				}
				else
				{
					if ($first.html().length > 0)
					{
						$first.appendTo($firstParent);
					}

					$second.appendTo($secondParent);
				}
				bBeforeCurrent = false;
			}
			else if (bBeforeCurrent)
			{
				$(eChild).appendTo($firstParent);
			}
			else
			{
				$(eChild).appendTo($secondParent);
			}
		}

		bLast = (eBlock === eCurrent);
		if (bLast)
		{
			bContinue = false;
		}

		eCurChild = eCurrent;
		eCurrent = eCurrent.parentNode;
	}

	if ($firstParent !== null && $secondParent !== null)
	{
		$firstParent.insertBefore($(eBlock));
		$span = $('<span>&nbsp;</span>').insertBefore($(eBlock));
		$('<br>').insertBefore($(eBlock));
		$secondParent.insertBefore($(eBlock));

		$(eBlock).remove();
		this.setCursorPosition($span[0], 0);
	}
};

module.exports = CCrea;