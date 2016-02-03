'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	App = require('core/js/App.js'),
	
	CAbstractHeaderItemView = require('core/js/views/CHeaderItemView.js'),
			
	AccountList = require('modules/Mail/js/AccountList.js'),
	Cache = require('modules/Mail/js/Cache.js')
;

function CHeaderItemView()
{
	CAbstractHeaderItemView.call(this, TextUtils.i18n('TITLE/MAILBOX_TAB'));
	
	this.unseenCount = Cache.newMessagesCount;
	
	this.inactiveTitle = ko.computed(function () {
		return TextUtils.i18n('TITLE/HAS_UNSEEN_MESSAGES_PLURAL', {'COUNT': this.unseenCount()}, null, this.unseenCount()) + ' - ' + AccountList.getEmail();
	}, this);
	
	this.accounts = AccountList.collection;
	
	this.linkText = ko.computed(function () {
		return AccountList.getEmail();
	});
}

_.extendOwn(CHeaderItemView.prototype, CAbstractHeaderItemView.prototype);

CHeaderItemView.prototype.ViewTemplate = App.isMobile() ? 'Mail_HeaderItemMobileView' : 'Mail_HeaderItemView';

var HeaderItemView = new CHeaderItemView();

HeaderItemView.allowChangeTitle(true);

module.exports = HeaderItemView;
