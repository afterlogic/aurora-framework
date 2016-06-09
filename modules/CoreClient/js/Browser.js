'use strict';

var Types = require('modules/CoreClient/js/utils/Types.js');

/**
 * @constructor
 */
function CBrowser()
{
	this.ie11 = !!navigator.userAgent.match(/Trident.*rv[ :]*11\./);
	this.ie = (/msie/.test(navigator.userAgent.toLowerCase()) && !window.opera) || this.ie11;
	this.ieVersion = this.getIeVersion();
	this.ie8AndBelow = this.ie && this.ieVersion <= 8;
	this.ie9AndBelow = this.ie && this.ieVersion <= 9;
	this.ie10AndAbove = this.ie && this.ieVersion >= 10;
	this.opera = !!window.opera || /opr/.test(navigator.userAgent.toLowerCase());
	this.firefox = /firefox/.test(navigator.userAgent.toLowerCase());
	this.chrome = /chrome/.test(navigator.userAgent.toLowerCase()) && !/opr/.test(navigator.userAgent.toLowerCase());
	this.chromeIos = /crios/.test(navigator.userAgent.toLowerCase());
	this.safari = /safari/.test(navigator.userAgent.toLowerCase()) && !this.chromeIos;
	
	this.windowsPhone = -1 < navigator.userAgent.indexOf('Windows Phone');
	this.iosDevice = !this.windowsPhone && (-1 < navigator.userAgent.indexOf('iPhone') ||
		-1 < navigator.userAgent.indexOf('iPod') ||
		-1 < navigator.userAgent.indexOf('iPad'));
	this.androidDevice = !this.windowsPhone && (-1 < navigator.userAgent.toLowerCase().indexOf('android')),
	this.mobileDevice = this.windowsPhone || this.iosDevice || this.androidDevice;
}

CBrowser.prototype.getIeVersion = function ()
{
	var
		sUa = navigator.userAgent.toLowerCase(),
		iVersion = Types.pInt(sUa.slice(sUa.indexOf('msie') + 4, sUa.indexOf(';', sUa.indexOf('msie') + 4)))
	;
	
	if (this.ie11)
	{
		iVersion = 11;
	}
	
	return iVersion;
};

module.exports = new CBrowser();
