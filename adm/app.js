'use strict';

(function (window) {
	
	function CScreen () {

		
		this.usersList = ko.observableArray([]);
		this.selectedItem = ko.observable(null);
		this.selectedItem.subscribe(function (oValue) {
			console.log(oValue);
		});
		
		this.init();
	}
	
	CScreen.prototype.init = function () {
		//$.ajax();
//		if (_.isArray(window.staticData['list']))
//		{
			var aListData= [];
			
			_.each(window.staticData['list'], function (oItem, iIndex) {
				aListData.push({
					'id': iIndex,
					'name': oItem[0],
					'description': oItem[1]
				});
			});
			this.usersList(aListData);
			console.log(this.usersList());
//		}
		
	};
	
	CScreen.prototype.selectItem = function (oItem)
	{
		this.selectedItem(oItem);
	};
	
	$(function () {
		ko.applyBindings(new CScreen());
	});
})(window);


