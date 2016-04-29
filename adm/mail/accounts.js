'use strict';

(function (window) {
	
	function CScreen ()
	{
		this.mailAccountsList = ko.observableArray([]);
		this.selectedItem = ko.observable(null);
		
		this.reset = function () {
			if (this.selectedItem())
			{
				this.selectedItem().active(false);
			}
			this.selectedItem(null);
		};
		
		this.init();
	}
	
	CScreen.prototype.init = function () {
		var aListData= [];

		_.each(window.staticData['mail_accounts_list'], function (oItem, iIndex) {
			aListData.push({
				'id': iIndex,
				'email': oItem[0],
				'password': oItem[1],
				'server': oItem[2],
				'is_default': oItem[3],
				'user_id': oItem[4],
				'disabled': oItem[5],
				'active': ko.observable(false)
			});
		});
		
		this.mailAccountsList(aListData);
	};
	
	CScreen.prototype.selectItem = function (oItem)
	{
		var oCurrentItem = this.selectedItem();
		if (oCurrentItem)
		{
			oCurrentItem.active(false);
		}
		
		this.selectedItem(oItem);
		this.selectedItem().active(true);
	};
	
	$(function () {
		ko.applyBindings(new CScreen(), document.getElementById('mail-screen'));
	});
})(window);
