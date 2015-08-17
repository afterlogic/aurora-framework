/**
 * @constructor
 * @extends ComposePopup
 */
function ComposePopup()
{
	CComposeViewModel.call(this);
	
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
		return this.subject() || Utils.i18n('COMPOSE/TITLE_MINIMIZED_NEW_MESSAGE');
	}, this);
}

Utils.extend(ComposePopup, CComposeViewModel);

ko.bindingHandlers.singleOrDoubleClick = {
    init: function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
        var singleHandler = valueAccessor().click,
            doubleHandler = valueAccessor().dblclick,
            delay = valueAccessor().delay || 200,
            clicks = 0;

        $(element).click(function (event) {
            clicks++;
            if (clicks === 1) {
                setTimeout(function () {
                    if (clicks === 1) {
                        if (singleHandler !== undefined) {
                            singleHandler.call(viewModel, bindingContext.$data, event);
                        }
                    } else {
                        if (doubleHandler !== undefined) {
                            doubleHandler.call(viewModel, bindingContext.$data, event);
                        }
                    }
                    clicks = 0;
                }, delay);
            }
        });
    }
};

/**
 * @return {string}
 */
ComposePopup.prototype.popupTemplate = function ()
{
	return 'Popups_ComposePopupViewModel';
};

ComposePopup.prototype.preventBackspaceOn = function ()
{
	$(document).on('keydown', this.fPreventBackspace);
};

ComposePopup.prototype.preventBackspaceOff = function ()
{
	$(document).off('keydown', this.fPreventBackspace);
};

ComposePopup.prototype.onHide = function ()
{
	this.preventBackspaceOff();
};

/**
 * @param {Array} aParams
 */
ComposePopup.prototype.onShow = function (aParams)
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
					App.Screens.showPopup(ConfirmAnotherMessageComposedPopup, [_.bind(function (sAnswer) {
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
			CComposeViewModel.prototype.onShow.call(this);
			this.onRoute(aParams);
		}
		this.preventBackspaceOn();
	}
};

ComposePopup.prototype.minimize = function ()
{
	this.minimized(true);
	ComposePopup.__dom.addClass('minimized');
	this.minHeightRemoveTrigger(true);
};

ComposePopup.prototype.maximize = function ()
{
	this.minimized(false);
	ComposePopup.__dom.removeClass('minimized');
	this.minHeightAdjustTrigger(true);
};

ComposePopup.prototype.saveAndClose = function ()
{
	if (this.hasSomethingToSave())
	{
		this.saveCommand();
	}

	this.closeCommand();
};

ComposePopup.prototype.onCancelClick = function ()
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
ComposePopup.prototype.onEscHandler = function (oEvent)
{
	var
		bHasOpenedPopup = this.oHtmlEditor.hasOpenedPopup(),
		bOnFileInput = !App.browser.ie && oEvent.target && (oEvent.target.tagName.toLowerCase() === 'input') && (oEvent.target.type.toLowerCase() === 'file')
	;
	
	if (bOnFileInput)
	{
		oEvent.target.blur();
	}
	
	if (App.Screens.popups.length === 1 && !bHasOpenedPopup && !bOnFileInput)
	{
		this.minimize();
	}
	
	if (bHasOpenedPopup)
	{
		this.oHtmlEditor.closeAllPopups();
	}
};
