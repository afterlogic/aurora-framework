
/**
 * @constructor
 */
function CRouting()
{
	this.defaultScreen = Enums.Screens.Mailbox;
	this.currentScreen = Enums.Screens.Mailbox;
	this.lastMailboxHash = ko.observable(Enums.Screens.Mailbox);
	this.lastHelpdeskHash = ko.observable(Enums.Screens.Helpdesk);
	this.lastSettingsHash = ko.observable(Enums.Screens.Settings);

	this.currentHash = ko.observable('');
	this.previousHash = ko.observable('');
}

/**
 * Initializes object.
 * 
 * @param {string} sDefaultScreen
 */
CRouting.prototype.init = function (sDefaultScreen)
{
	this.defaultScreen = sDefaultScreen;
	hasher.initialized.removeAll();
	hasher.changed.removeAll();
	hasher.initialized.add(this.parseRouting, this);
	hasher.changed.add(this.parseRouting, this);
	hasher.init();
	hasher.initialized.removeAll();
};

/**
 * Finalizes the object and puts an empty hash.
 */
CRouting.prototype.finalize = function ()
{
	hasher.dispose();
};

/**
 * Sets a new hash.
 * 
 * @param {string} sNewHash
 * 
 * @return {boolean}
 */
CRouting.prototype.setHashFromString = function (sNewHash)
{
	var bSame = (location.hash === decodeURIComponent(sNewHash));
	
	if (!bSame)
	{
		location.hash = sNewHash;
	}
	
	return bSame;
};

/**
 * Sets a new hash without part.
 * 
 * @param {string} sUid
 */
CRouting.prototype.replaceHashWithoutMessageUid = function (sUid)
{
	if (typeof sUid === 'string' && sUid !== '')
	{
		var sNewHash = location.hash.replace('/msg' + sUid, '');
		this.replaceHashFromString(sNewHash);
	}
};

/**
 * Sets a new hash.
 * 
 * @param {string} sNewHash
 */
CRouting.prototype.replaceHashFromString = function (sNewHash)
{
	if (location.hash !== sNewHash)
	{
		location.replace(sNewHash);
	}
};

/**
 * Sets a new hash made ​​up of an array.
 * 
 * @param {Array} aRoutingParts
 * 
 * @return boolean
 */
CRouting.prototype.setHash = function (aRoutingParts)
{
	return this.setHashFromString(this.buildHashFromArray(aRoutingParts));
};

/**
 * @param {Array} aRoutingParts
 */
CRouting.prototype.replaceHash = function (aRoutingParts)
{
	this.replaceHashFromString(this.buildHashFromArray(aRoutingParts));
};

/**
 * @param {Array} aRoutingParts
 */
CRouting.prototype.replaceHashDirectly = function (aRoutingParts)
{
	hasher.stop();
	this.replaceHashFromString(this.buildHashFromArray(aRoutingParts));
	hasher.init();
};

CRouting.prototype.setPreviousHash = function ()
{
	location.hash = this.previousHash();
};

/**
 * Makes a hash of a string array.
 *
 * @param {(string|Array)} aRoutingParts
 * 
 * @return {string}
 */
CRouting.prototype.buildHashFromArray = function (aRoutingParts)
{
	var
		iIndex = 0,
		iLen = 0,
		sHash = ''
	;

	if (_.isArray(aRoutingParts))
	{
		for (iLen = aRoutingParts.length; iIndex < iLen; iIndex++)
		{
			aRoutingParts[iIndex] = encodeURIComponent(aRoutingParts[iIndex]);
		}
	}
	else
	{
		aRoutingParts = [encodeURIComponent(aRoutingParts.toString())];
	}
	
	sHash = aRoutingParts.join('/');
	
	if (sHash !== '')
	{
		sHash = '#' + sHash;
	}

	return sHash;
};

/**
 * Returns the value of the hash string of location.href.
 * location.hash returns the decoded string and location.href - not, so it uses location.href.
 * 
 * @return {string}
 */
CRouting.prototype.getHashFromHref = function ()
{
	var
		iPos = location.href.indexOf('#'),
		sHash = ''
	;

	if (iPos !== -1)
	{
		sHash = location.href.substr(iPos + 1);
	}

	return sHash;
};

CRouting.prototype.isSingleMode = function ()
{
	var
		sScreen = this.getScreenFromHash(),
		bSingleMode = (sScreen === Enums.Screens.SingleMessageView || sScreen === Enums.Screens.SingleCompose || 
			sScreen === Enums.Screens.SingleHelpdesk)
	;
	
	this.currentScreen = sScreen;
	
	return bSingleMode;
};

/**
 * @param {Array} aRoutingParts
 * @param {Array} aAddParams
 */
CRouting.prototype.goDirectly = function (aRoutingParts, aAddParams)
{
	hasher.stop();
	this.setHash(aRoutingParts);
	this.parseRouting(aAddParams);
	hasher.init();
};

/**
 * @param {string} sNeedScreen
 */
CRouting.prototype.historyBackWithoutParsing = function (sNeedScreen)
{
	hasher.stop();
	location.hash = this.currentHash();
	hasher.init();
};

/**
 * @returns {String}
 */
CRouting.prototype.getScreenFromHash = function ()
{
	var
		sHash = this.getHashFromHref(),
		aHash = sHash.split('/')
	;
	return decodeURIComponent(aHash.shift()) || this.defaultScreen;
};

/**
 * @param {Array} aAddParams
 */
CRouting.prototype.parseRouting = function (aAddParams)
{
	var
		oCurrentModel = App.Screens.getCurrentScreenModel(),
		fContinueScreenChanging = _.bind(this.chooseScreen, this, aAddParams)
	;
	
	if (oCurrentModel && Utils.isFunc(oCurrentModel.beforeHide))
	{
		oCurrentModel.beforeHide(fContinueScreenChanging);
	}
	else
	{
		fContinueScreenChanging();
	}
};

/**
 * Parses the hash string and opens the corresponding routing screen.
 * 
 * @param {Array} aAddParams
 */
CRouting.prototype.chooseScreen = function (aAddParams)
{
	var
		sHash = this.getHashFromHref(),
		aHash = sHash.split('/'),
		sScreen = decodeURIComponent(aHash.shift()) || this.defaultScreen,
		bScreenInEnum = !!_.find(Enums.Screens, function (sScreenInEnum) {
			return sScreenInEnum === sScreen;
		}),
		iIndex = 0,
		iLen = aHash.length
	;

	if (sScreen === Enums.Screens.Mailbox)
	{
		this.lastMailboxHash(sHash);
	}
	if (sScreen === Enums.Screens.Helpdesk)
	{
		this.lastHelpdeskHash(sHash);
	}
	if (sScreen === Enums.Screens.Settings)
	{
		this.lastSettingsHash(sHash);
	}
	this.previousHash(this.currentHash());
	this.currentHash(sHash);
	
	for (; iIndex < iLen; iIndex++)
	{
		aHash[iIndex] = decodeURIComponent(aHash[iIndex]);
	}
	
	if ($.isArray(aAddParams))
	{
		aHash = _.union(aHash, aAddParams);
	}
	
	this.currentScreen = sScreen;
	
	switch (sScreen)
	{
		case Enums.Screens.SingleMessageView:
		case Enums.Screens.SingleCompose:
		case Enums.Screens.SingleHelpdesk:
			AppData.SingleMode = true;
			App.Screens.showCurrentScreen(sScreen, aHash);
			break;
		default:
			if (!bScreenInEnum)
			{
				sScreen = this.defaultScreen;
			}
			AppData.SingleMode = false;
			App.Screens.showNormalScreen(Enums.Screens.Header);
			App.Screens.showCurrentScreen(sScreen, aHash);
			break;
		case Enums.Screens.Mailbox:
			AppData.SingleMode = false;
			App.Screens.showNormalScreen(Enums.Screens.Header);
			App.Screens.showCurrentScreen(Enums.Screens.Mailbox, aHash);
			break;
	}
};
