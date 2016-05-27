'use strict';

(function (window) {
	
	function CScreen ()
	{
		this.objectTypes = [];
		
		this.objectsList = ko.observableArray([]);
		this.propsList = ko.observableArray([]);
		
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
			this.objectTypes = window.staticData['objects'];

			var aListData= [];
			
			_.each(window.staticData['objects_list'], function (oItem, iIndex) {
				
				aListData.push(_.map(oItem, function (oItem1) {
					return oItem1;
				}));
				
				
/*				
				aListData.push({
					'id': iIndex,
					'login': oItem[0],
					'password': oItem[1],
					'user_id': oItem[2],
					'disabled': oItem[3],
					'active': ko.observable(false)
				});
*/				
			});
			
			console.log(aListData);
			
			this.objectsList(aListData);
			this.propsList(window.staticData['objects_props']);
			

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
		ko.applyBindings(new CScreen(), document.getElementById('objects-screen'));
	});
})(window);


