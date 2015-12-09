'use strict';

var
	$ = require('jquery'),
	_ = require('underscore'),
	ko = require('knockout')
;

function getCaretOffset(oElement)
{
	var
		caretOffset = 0,
		range,
		preCaretRange,
		textRange,
		preCaretTextRange
	;

	if (typeof window.getSelection !== "undefined")
	{
		range = window.getSelection().getRangeAt(0);
		preCaretRange = range.cloneRange();
		preCaretRange.selectNodeContents(oElement);
		preCaretRange.setEnd(range.endContainer, range.endOffset);
		caretOffset = preCaretRange.toString().length;
	}
	else if (typeof document.selection !== "undefined" && document.selection.type !== "Control")
	{
		textRange = document.selection.createRange();
		preCaretTextRange = document.body.createTextRange();
		preCaretTextRange.moveToElementText(oElement);
		preCaretTextRange.setEndPoint("EndToEnd", textRange);
		caretOffset = preCaretTextRange.text.length;
	}
	return caretOffset;
}

function setCursor(oElement, iCaretPos)
{
	var
		range,
		selection,
		textRange
	;
	
	if (!oElement)
	{
		return false;
	}
	else if(document.createRange)
	{
		range = document.createRange();
		range.selectNodeContents(oElement);
		range.setStart(oElement, iCaretPos);
		range.setEnd(oElement, iCaretPos);
		selection = window.getSelection();
		selection.removeAllRanges();
		selection.addRange(range);
	}
	else if(oElement.createTextRange)
	{
		textRange = oElement.createTextRange();
		textRange.collapse(true);
		textRange.moveEnd(iCaretPos);
		textRange.moveStart(iCaretPos);
		textRange.select();
		return true;
	}
	else if(oElement.setSelectionRange)
	{
		oElement.setSelectionRange(iCaretPos, iCaretPos);
		return true;
	}
	return false;
}


ko.bindingHandlers.highlighter = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			oValueObserver = oOptions.valueObserver ? oOptions.valueObserver : null,
			oHighlighterValueObserver = oOptions.highlighterValueObserver ? oOptions.highlighterValueObserver : null,
			oHighlightTrigger = oOptions.highlightTrigger ? oOptions.highlightTrigger : null,
			aHighlightWords = ['from:', 'to:', 'subject:', 'text:', 'email:', 'has:', 'date:', 'text:', 'body:'],
			rPattern = (function () {
				var sPatt = '';
				$.each(aHighlightWords, function(i, oEl) {
					sPatt = (!i) ? (sPatt + '\\b' + oEl) : (sPatt + '|\\b' + oEl);
				});

				return new RegExp('(' + sPatt + ')', 'g');
			}()),
			fClear = function (sStr) {
				return sStr.replace(/\xC2\xA0/g, ' ').replace(/\xA0/g, ' ').replace(/[\s]+/g, ' ');
			},
			iPrevKeyCode = -1,
			sUserLanguage = window.navigator.language || window.navigator.userLanguage,
			aTabooLang = ['zh', 'zh-TW', 'zh-CN', 'zh-HK', 'zh-SG', 'zh-MO', 'ja', 'ja-JP', 'ko', 'ko-KR', 'vi', 'vi-VN', 'th', 'th-TH'],// , 'ru', 'ru-RU'
			bHighlight = !_.include(aTabooLang, sUserLanguage)
		;

		$(oElement)
			.on('keydown', function (oEvent) {
				return oEvent.keyCode !== Enums.Key.Enter;
			})
			.on('keyup', function (oEvent) {
				var
					aMoveKeys = [Enums.Key.Left, Enums.Key.Right, Enums.Key.Home, Enums.Key.End],
					bMoveKeys = -1 !== $.inArray(oEvent.keyCode, aMoveKeys)
				;
				
				if (!(
//							oEvent.keyCode === Enums.Key.Enter					||
						oEvent.keyCode === Enums.Key.Shift					||
						oEvent.keyCode === Enums.Key.Ctrl					||
						// for international english -------------------------
						oEvent.keyCode === Enums.Key.Dash					||
						oEvent.keyCode === Enums.Key.Apostrophe				||
						oEvent.keyCode === Enums.Key.Six && oEvent.shiftKey	||
						// ---------------------------------------------------
						bMoveKeys											||
//							((oEvent.shiftKey || iPrevKeyCode === Enums.Key.Shift) && bMoveKeys) ||
						((oEvent.ctrlKey || iPrevKeyCode === Enums.Key.Ctrl) && oEvent.keyCode === Enums.Key.a)
					))
				{
					oValueObserver(fClear(jqEl.text()));
					highlight(false);
				}
				iPrevKeyCode = oEvent.keyCode;
				return true;
			})
			.on('paste', function (oEvent) {
				setTimeout(function () {
					oValueObserver(fClear(jqEl.text()));
					highlight(false);
				}, 0);
				return true;
			});

		// highlight on init
		setTimeout(function () {
			highlight(true);
		}, 0);

		function highlight(bNotRestoreSel) {
			if(bHighlight)
			{
				var
					iCaretPos = 0,
					sContent = jqEl.text(),
					aContent = sContent.split(rPattern),
					aDividedContent = [],
					sReplaceWith = '<span class="search_highlight"' + '>$&</span>'
				;
				_.each(aContent, function (sEl) {
					var aEl = sEl.split('');
					if (_.any(aHighlightWords, function (oAnyEl) {return oAnyEl === sEl;}))
					{
						_.each(aEl, function (sElem) {
							aDividedContent.push($(sElem.replace(/(.)/, sReplaceWith)));
						});
					}
					else
					{
						_.each(aEl, function(sElem) {
							if(sElem === ' ')
							{
								// space fix for firefox
								aDividedContent.push(document.createTextNode('\u00A0'));
							}
							else
							{
								aDividedContent.push(document.createTextNode(sElem));
							}
						});
					}
				});
				if (bNotRestoreSel)
				{
					jqEl.empty().append(aDividedContent);
				}
				else
				{
					iCaretPos = getCaretOffset(oElement);
					jqEl.empty().append(aDividedContent);
					setCursor(oElement, iCaretPos);
				}
			}
		}

		oHighlightTrigger.notifySubscribers();

		oHighlightTrigger.subscribe(function (bNotRestoreSel) {
			setTimeout(function () {
				highlight(!!bNotRestoreSel);
			}, 0);
		}, this);

		oHighlighterValueObserver.subscribe(function () {
			jqEl.text(oValueObserver());
		}, this);
	}
};

