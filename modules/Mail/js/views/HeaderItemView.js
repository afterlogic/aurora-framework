'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	TextUtils = require('core/js/utils/Text.js'),
	CAbstractHeaderItemView = require('core/js/views/CHeaderItemView.js'),
			
	Accounts = require('modules/Mail/js/AccountList.js'),
	Cache = require('modules/Mail/js/Cache.js')
;

function CHeaderItemView()
{
	CAbstractHeaderItemView.call(this, TextUtils.i18n('TITLE/MAILBOX_TAB'));
	
	this.sTemplateName = 'Mail_HeaderItemView';
	
	this.unseenCount = Cache.newMessagesCount;
	
	this.inactiveTitle = ko.computed(function () {
		return TextUtils.i18n('TITLE/HAS_UNSEEN_MESSAGES_PLURAL', {'COUNT': this.unseenCount()}, null, this.unseenCount()) + ' - ' + Accounts.getEmail();
	}, this);
	
	this.accounts = Accounts.collection;
}

_.extend(CHeaderItemView.prototype, CAbstractHeaderItemView.prototype);

var HeaderItemView = new CHeaderItemView();

HeaderItemView.linkText(Accounts.getEmail());
HeaderItemView.activeTitle(Accounts.getEmail() + ' - ' + TextUtils.i18n('TITLE/MAILBOX'));
HeaderItemView.allowChangeTitle(true);

module.exports = HeaderItemView;