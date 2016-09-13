'use strict';

(function (window) {
	
	function CScreen ()
	{
		this.usersList = ko.observableArray([]);
		this.selectedItem = ko.observable(null);
		this.selectedItem.subscribe(function (v) {
			console.log(v);
		});
		
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
//		if (_.isArray(window.staticData['users_list']))
//		{
			var aListData= [];
			_.each(window.staticData['users_list'], function (oItem, iIndex) {
				aListData.push({
					'id': iIndex,
					'name': oItem[0],
					'description': oItem[1],
					'tenant_id': oItem[3],
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
		ko.applyBindings(new CScreen(), document.getElementById('users-screen'));
	});
})(window);


