'use strict';

(function (window) {
	
	function CScreen ()
	{
		this.usersList = ko.observableArray([]);
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
		//$.ajax();
//		if (_.isArray(window.staticData['accounts_list']))
//		{
			var aListData= [];
			
			_.each(window.staticData['accounts_list'], function (oItem, iIndex) {
				aListData.push({
					'id': iIndex,
					'login': oItem[0],
					'password': oItem[1],
					'user_id': oItem[2],
					'disabled': oItem[3],
					'active': ko.observable(false)
				});
			});
			this.usersList(aListData);
//		}
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
		ko.applyBindings(new CScreen(), document.getElementById('accounts-screen'));
	});
})(window);


