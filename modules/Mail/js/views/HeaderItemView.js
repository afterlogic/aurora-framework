'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	TextUtils = require('core/js/utils/Text.js'),
	App = require('core/js/App.js'),
	CAbstractHeaderItemView = require('core/js/views/CHeaderItemView.js'),
			
	Accounts = require('modules/Mail/js/AccountList.js'),
	Cache = require('modules/Mail/js/Cache.js')
;

function CHeaderItemView()
{
	CAbstractHeaderItemView.call(this, TextUtils.i18n('TITLE/MAILBOX_TAB'));
	
	this.unseenCount = Cache.newMessagesCount;
	
	this.inactiveTitle = ko.computed(function () {
		return TextUtils.i18n('TITLE/HAS_UNSEEN_MESSAGES_PLURAL', {'COUNT': this.unseenCount()}, null, this.unseenCount()) + ' - ' + Accounts.getEmail();
	}, this);
	
	this.accounts = Accounts.collection;
}

_.extendOwn(CHeaderItemView.prototype, CAbstractHeaderItemView.prototype);

CHeaderItemView.prototype.ViewTemplate = App.isMobile() ? 'Mail_HeaderItemMobileView' : 'Mail_HeaderItemView';

var HeaderItemView = new CHeaderItemView();

HeaderItemView.linkText(Accounts.getEmail());
HeaderItemView.allowChangeTitle(true);

module.exports = HeaderItemView;