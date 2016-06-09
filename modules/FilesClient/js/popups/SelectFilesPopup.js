'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js'),
	
	CFilesView = require('modules/%ModuleName%/js/views/CFilesView.js'),
	CFileModel = require('modules/%ModuleName%/js/models/CFileModel.js')
;

/**
 * @constructor
 */
function CSelectFilesPopup()
{
	CAbstractPopup.call(this);
	
	this.oFilesView = new CFilesView(true);
	this.oFilesView.onSelectClickPopupBinded = _.bind(this.onSelectClick, this);
	this.fCallback = null;
}

_.extendOwn(CSelectFilesPopup.prototype, CAbstractPopup.prototype);

CSelectFilesPopup.prototype.PopupTemplate = '%ModuleName%_SelectFilesPopup';

/**
 * @param {Function} fCallback
 */
CSelectFilesPopup.prototype.onShow = function (fCallback)
{
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
	this.oFilesView.onShow();
};

CSelectFilesPopup.prototype.onBind = function ()
{
	this.oFilesView.onBind(this.$popupDom);
};

CSelectFilesPopup.prototype.onSelectClick = function ()
{
	var
		aItems = this.oFilesView.selector.listCheckedAndSelected(),
		aFileItems = _.filter(aItems, function (oItem) {
			return oItem instanceof CFileModel;
		}, this)
	;
	
	if (this.fCallback)
	{
		this.fCallback(aFileItems);
	}
	
	this.closePopup();
};

module.exports = new CSelectFilesPopup();