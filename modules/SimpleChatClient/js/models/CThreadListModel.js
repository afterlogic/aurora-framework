'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	moment = require('moment'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js')
;

/**
 * @constructor
 */
function CThreadListModel()
{
	this.Id = null;
	this.ThreadHash = '';
	this.IdOwner = null;
	this.ItsMe = false;
	this.aOwner = [];
	this.sSubject = '';
	this.sEmail = '';
	this.sName = '';
	this.sFrom = '';
	this.sFromFull = '';
	this.time = ko.observable(0);
	this.state = ko.observable(0);
	this.unseen = ko.observable(false);
	this.postsCount = ko.observable(0);

	this.date = ko.computed(function () {
		return moment(this.time() * 1000).fromNow(false);
	}, this);

	this.printableState = ko.computed(function () {
		var 
			sText = '',
			sLangSuffix = this.ItsMe ? '_FOR_CLIENT' : ''
		;
		
		switch (this.state())
		{
			case Enums.HelpdeskThreadStates.Pending:
				sText = TextUtils.i18n('HELPDESK/LABEL_PENDING_STATE' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Resolved:
				sText = TextUtils.i18n('HELPDESK/LABEL_RESOLVED_STATE' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Waiting:
				sText = TextUtils.i18n('HELPDESK/LABEL_WAITING_STATE' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Answered:
				sText = TextUtils.i18n('HELPDESK/LABEL_ANSWERED_STATE' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Deferred:
				sText = TextUtils.i18n('HELPDESK/LABEL_DEFERRED_STATE' + sLangSuffix);
				break;
		}
		
		return sText;
	}, this);

	this.deleted = ko.observable(false);
	this.checked = ko.observable(false);
	this.selected = ko.observable(false);
}

/**
 * @param {Object} oData
 */
CThreadListModel.prototype.parse = function (oData)
{
	this.Id = oData.IdHelpdeskThread;
	this.ThreadHash = Types.pString(oData.ThreadHash);
	this.IdOwner = oData.IdOwner;
	this.ItsMe = !!oData.ItsMe;
	this.sSubject = Types.pString(oData.Subject);
	this.time(Types.pInt(oData.Updated));
	this.aOwner = Types.isNonEmptyArray(oData.Owner) ? oData.Owner : ['', ''];
	this.sEmail = this.aOwner[0] || '';
	this.sName = this.aOwner[1] || '';
	this.sFrom = this.sName || this.sEmail;
	this.sFromFull = $.trim('' === this.sName ? this.sEmail :
		(this.sName + ('' !== this.sEmail ? ' (' + this.sEmail  + ')' : '')));

	this.postsCount(oData.PostCount);
	this.state(oData.Type);
	this.unseen(!oData.IsRead);
};

/**
 * @return {string}
 */
CThreadListModel.prototype.Name = function ()
{
	return this.sName;
};

/**
 * @return {string}
 */
CThreadListModel.prototype.Email = function ()
{
	return this.sEmail;
};

CThreadListModel.prototype.updateMomentDate = function ()
{
	this.time.valueHasMutated();
};

module.exports = CThreadListModel;
