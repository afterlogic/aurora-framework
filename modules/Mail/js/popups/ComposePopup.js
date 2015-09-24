'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Browser = require('core/js/Browser.js'),
	
	Popups = require('core/js/Popups.js'),
	
	CComposeView = require('modules/Mail/js/views/CComposeView.js'),
	ConfirmAnotherMessageComposedPopup = require('modules/Mail/js/popups/ConfirmAnotherMessageComposedPopup.js')
;

/**
 * @constructor
 * @extends CComposePopup
 */
function CComposePopup()
{
	CComposeView.call(this);
	
	this.minimized = ko.observable(false);
	
	this.fPreventBackspace = function (ev) {
		var
			bBackspace = ev.which === $.ui.keyCode.BACKSPACE,
			bInput = ev.target.tagName === 'INPUT' || ev.target.tagName === 'TEXTAREA',
			bEditableDiv = ev.target.tagName === 'DIV' && $(ev.target).attr('contenteditable') === 'true'
		;
		
		if (bBackspace && !bInput && !bEditableDiv)
		{
			ev.preventDefault();
			ev.stopPropagation();
		}
	};
	
	this.minimized.subscribe(function () {
		if (this.minimized())
		{
			this.preventBackspaceOff();
		}
		else if (this.shown())
		{
			this.preventBackspaceOn();
		}
	}, this);
	
	this.minimizedTitle = ko.computed(function () {
		return this.subject() || TextUtils.i18n('COMPOSE/TITLE_MINIMIZED_NEW_MESSAGE');
	}, this);
}

_.extendOwn(CComposePopup.prototype, CComposeView.prototype);

CComposePopup.prototype.PopupTemplate = 'Mail_ComposePopup';

CComposePopup.prototype.preventBackspaceOn = function ()
{
	$(document).on('keydown', this.fPreventBackspace);
};

CComposePopup.prototype.preventBackspaceOff = function ()
{
	$(document).off('keydown', this.fPreventBackspace);
};

CComposePopup.prototype.onHide = function ()
{
	this.preventBackspaceOff();
};

/**
 * @param {Array} aParams
 */
CComposePopup.prototype.onShow = function (aParams)
{
	aParams = aParams || [];
	
	if (aParams.length === 1 && aParams[0] === 'close')
	{
		this.closeCommand();
	}
	else
	{
		var
			bOpeningSameDraft = aParams.length === 3 && aParams[0] === 'drafts' && aParams[2] === this.draftUid(),
			bWasMinimized = this.minimized()
		;
		
		this.maximize();
		if (this.shown())
		{
			if (aParams.length > 0 && !bOpeningSameDraft)
			{
				if (this.hasSomethingToSave())
				{
					this.stopAutosaveTimer();
					Popups.showPopup(ConfirmAnotherMessageComposedPopup, [_.bind(function (sAnswer) {
						switch (sAnswer)
						{
							case Enums.AnotherMessageComposedAnswer.Discard:
								this.onRoute(aParams);
								break;
							case Enums.AnotherMessageComposedAnswer.SaveAsDraft:
								if (this.hasSomethingToSave())
								{
									this.executeSave(true, false);
								}
								this.onRoute(aParams);
								break;
							case Enums.AnotherMessageComposedAnswer.Cancel:
								break;
						}
						this.startAutosaveTimer();
					}, this)]);
				}
				else
				{
					this.onRoute(aParams);
				}
			}
			else if (!bWasMinimized)
			{
				this.onRoute(aParams);
			}

			this.oHtmlEditor.oCrea.clearUndoRedo();
		}
		else
		{
			CComposeView.prototype.onShow.call(this);
			this.onRoute(aParams);
		}
		this.preventBackspaceOn();
	}
};

CComposePopup.prototype.minimize = function ()
{
	this.minimized(true);
	this.$viewModel.addClass('minimized');
	this.minHeightRemoveTrigger(true);
};

CComposePopup.prototype.maximize = function ()
{
	this.minimized(false);
	this.$viewModel.removeClass('minimized');
	this.minHeightAdjustTrigger(true);
};

CComposePopup.prototype.saveAndClose = function ()
{
	if (this.hasSomethingToSave())
	{
		this.saveCommand();
	}

	this.closeCommand();
};

CComposePopup.prototype.onCancelClick = function ()
{
	if (this.hasSomethingToSave())
	{
		this.minimize();
	}
	else
	{
		this.closeCommand();
	}
};

/**
 * @param {Object} oEvent
 */
CComposePopup.prototype.onEscHandler = function (oEvent)
{
	var
		bHtmlEditorHasOpenedPopup = this.oHtmlEditor.hasOpenedPopup(),
		bOnFileInput = !Browser.ie && oEvent.target && (oEvent.target.tagName.toLowerCase() === 'input') && (oEvent.target.type.toLowerCase() === 'file')
	;
	
	if (bOnFileInput)
	{
		oEvent.target.blur();
	}
	
	if (Popups.hasOnlyOneOpenedPopup() && !bHtmlEditorHasOpenedPopup && !bOnFileInput)
	{
		this.minimize();
	}
	
	if (bHtmlEditorHasOpenedPopup)
	{
		this.oHtmlEditor.closeAllPopups();
	}
};

module.exports = new CComposePopup();