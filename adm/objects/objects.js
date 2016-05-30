'use strict';

(function (window) {
	
	function CScreen ()
	{
		this.objectTypes = [];
		
		this.objectsList = ko.observableArray([]);
		this.propsList = ko.observableArray([]);
		
		this.selectedItem = ko.observable(null);
		this.selectedObjectName = ko.observable(null);
		this.checkedItems = ko.observableArray([]);
		
		this.switchTab = _.bind(this.switchTab, this);
		this.ajaxResponse = _.bind(this.ajaxResponse, this);
		this.selectItem = _.bind(this.selectItem, this);
		this.checkItem = _.bind(this.checkItem, this);
		
		this.init();
	}
	
	CScreen.prototype.init = function () {
		this.objectTypes = window.staticData['objects'];

//		var aListData= [];

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
		if (this.selectedItem() === oItem)
		{
			this.selectedItem(null);
			
			if (this.checkedItems().length === 1)
			{
				this.checkedItems([]);
			}
		}
		else
		{
			this.selectedItem(oItem);
			
			if (this.checkedItems().length === 0)
			{
				this.checkedItems.push(oItem[0]);
			}
			else if (this.checkedItems().length === 1)
			{
				this.checkedItems([oItem[0]]);
			}
		}
		
		return true;
	};
	
	CScreen.prototype.checkItem = function (oItem, oEvent)
	{
		oEvent.stopPropagation();
//		$(oEvent.target).prop('checked', true);
//		$(oEvent.target).attr('checked', true);
//		oEvent.target.checked = true;
//		console.log(oItem, oEvent.target);

		if (_.contains(this.checkedItems(), oItem[0]))
		{
			this.checkedItems.remove(oItem[0]);
		}
		else
		{
			this.checkedItems.push(oItem[0]);
		}
		
		if (!this.selectedItem())
		{
			this.selectedItem(oItem);
		}
		
		return true;
	};
	
	CScreen.prototype.switchTab = function (sTabName)
	{
		var 
			self = this,
			oRequest = {
				'ObjectName': sTabName
			}
		;
		
		this.selectedItem(null);
		this.checkedItems([]);
		
		this.selectedObjectName(sTabName);
		
		$.ajax({
			url: 'ajax.php',
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


