
var
	ko = require('knockout'),
	Routing = require('core/js/Routing.js')
;

function CHeaderItemView(sLinkText, sActiveTitle)
{
	this.sName = '';
	
	this.hash = ko.observable('');
	this.linkText = ko.observable(sLinkText);
	this.isCurrent = ko.observable(false);
	
	this.recivedAnim = ko.observable(false);
	this.unseenCount = ko.observable(0);
	
	this.allowChangeTitle = ko.observable(false); // allows to change favicon and browser title when browser is inactive
	this.activeTitle = ko.observable(sActiveTitle || ''); // always allowed to be changed
	this.inactiveTitle = ko.observable('');
	
	this.sTemplateName = 'Common_HeaderItemView';
}

CHeaderItemView.prototype.setName = function (sName)
{
	this.sName = sName;
	this.hash(Routing.buildHashFromArray([sName]));
};

module.exports = CHeaderItemView;