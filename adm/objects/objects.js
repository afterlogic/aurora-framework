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
		
		this.switchTab = _.bind(this.switchTab, this),
		this.ajaxResponse = _.bind(this.ajaxResponse, this),
		
		this.init();
	}
	
	CScreen.prototype.init = function () {
		this.objectTypes = window.staticData['objects'];

		var aListData= [];

//		_.each(window.staticData['objects_list'], function (oItem, iIndex) {
//
//			aListData.push(_.map(oItem, function (oItem1) {
//				return oItem1;
//			}));
//		});
//		this.propsList(window.staticData['objects_props']);

//		this.objectsList(aListData);
	};
	
	CScreen.prototype.fillData = function (aData)
	{
		if (aData && aData.length > 0)
		{
			this.propsList(_.keys(aData[0]));
			this.objectsList(_.map(aData, function (oItem) {
				return _.values(oItem);
			}));
		}
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
	
	
	CScreen.prototype.switchTab = function (sTabName)
	{
		var 
			self = this,
			oRequest = {
				'ObjectName': sTabName
			}
		;
		
		$.ajax({
			url: '/adm/ajax.php',
			context: this,
			type: 'POST',
			data: oRequest,
			complete: self.ajaxResponse,
			timeout: 1000
		});
	};
	
	CScreen.prototype.ajaxResponse = function (jqXHR, textStatus) {
		if (textStatus === "success")
		{
			var oResponse = JSON.parse(jqXHR.responseText);
			this.fillData(oResponse.result);
		}
		else
		{
			console.log('ajaxResponse', textStatus);
		}
	};
	
	$(function () {
		ko.applyBindings(new CScreen(), document.getElementById('objects-screen'));
	});
})(window);


