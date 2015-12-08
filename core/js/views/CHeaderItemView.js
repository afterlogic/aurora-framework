
var
	ko = require('knockout'),
	
	App = require('core/js/App.js'),
	Routing = require('core/js/Routing.js')
;

function CHeaderItemView(sLinkText)
{
	this.sName = '';
	
	this.hash = ko.observable('');
	this.linkText = ko.observable(sLinkText);
	this.isCurrent = ko.observable(false);
	
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	this.unseenCount = ko.observable(0);
	
	this.allowChangeTitle = ko.observable(false); // allows to change favicon and browser title when browser is inactive
	this.inactiveTitle = ko.observable('');
}

CHeaderItemView.prototype.ViewTemplate = App.isMobile() ? 'Core_HeaderItemMobileView' : 'Core_HeaderItemView';

CHeaderItemView.prototype.setName = function (sName)
{
	this.sName = sName.toLowerCase();
	this.hash(Routing.buildHashFromArray([sName.toLowerCase()]));
};

module.exports = CHeaderItemView;