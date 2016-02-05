'use strict';

var
	$ = require('jquery'),
	ko = require('knockout')
;

/**
 * @constructor
 * 
 * @param {number} iDefaultPort
 * @param {number} iDefaultSslPort
 * @param {string} sId
 * @param {string} sLabel
 * @param {function} fGetDefaultServerValue
 */
function CServerPropertiesView(iDefaultPort, iDefaultSslPort, sId, sLabel, fGetDefaultServerValue)
{
	this.server = ko.observable('');
	this.label = sLabel;
	this.focused = ko.observable(false);
	this.defaultPort = ko.observable(iDefaultPort);
	this.defaultSslPort = ko.observable(iDefaultSslPort);
	this.port = ko.observable(iDefaultPort);
	this.ssl = ko.observable(false);
	this.isEnabled = ko.observable(true);
	this.id = sId;

	if ($.isFunction(fGetDefaultServerValue))
	{
		this.focused.subscribe(function () {
			if (this.focused() && this.server() === '')
			{
				this.server(fGetDefaultServerValue());
			}
		}, this);
	}
	
	this.ssl.subscribe(function () {
		if (this.ssl())
		{
			if (this.port() === this.defaultPort())
			{
				this.port(this.defaultSslPort());
			}
		}
		else
		{
			if (this.port() === this.defaultSslPort())
			{
				this.port(this.defaultPort());
			}
		}
	}, this);
}

/**
 * @param {string} sServer
 * @param {number} iPort
 * @param {boolean} bSsl
 */
CServerPropertiesView.prototype.set = function (sServer, iPort, bSsl)
{
	this.server(sServer);
	this.port(iPort);
	this.ssl(bSsl);
};

CServerPropertiesView.prototype.clear = function ()
{
	this.server('');
	this.port(this.defaultPort());
	this.ssl(false);
};

CServerPropertiesView.prototype.getIntPort = function ()
{
	return parseInt(this.port(), 10);
};

CServerPropertiesView.prototype.getIntSsl = function ()
{
	return this.ssl() ? 1 : 0;
};

module.exports = CServerPropertiesView;
