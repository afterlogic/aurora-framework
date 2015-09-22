'use strict';

var AddressUtils = require('core/js/utils/Address.js');

/**
 * @todo
 * @param {Object} oOpenPgpKey
 * @constructor
 */
function COpenPgpKey(oOpenPgpKey)
{
	this.pgpKey = oOpenPgpKey;

	var oPrimaryUser = this.pgpKey.getPrimaryUser();
	
	this.user = (oPrimaryUser && oPrimaryUser.user) ? oPrimaryUser.user.userId.userid :
		(this.pgpKey.users && this.pgpKey.users[0] ? this.pgpKey.users[0].userId.userid : '');

	this.emailParts = AddressUtils.getEmailParts(this.user);
}

/**
 * @type {Object}
 */
COpenPgpKey.prototype.pgpKey = null;

/**
 * @type {Object}
 */
COpenPgpKey.prototype.emailParts = null;

/**
 * @type {string}
 */
COpenPgpKey.prototype.user = '';

/**
 * @return {string}
 */
COpenPgpKey.prototype.getId = function ()
{
	return this.pgpKey.primaryKey.getKeyId().toHex().toLowerCase();
};

/**
 * @return {string}
 */
COpenPgpKey.prototype.getEmail = function ()
{
	return this.emailParts['email'] || this.user;
};

/**
 * @return {string}
 */
COpenPgpKey.prototype.getUser = function ()
{
	return this.user;
};

/**
 * @return {string}
 */
COpenPgpKey.prototype.getFingerprint = function ()
{
	return this.pgpKey.primaryKey.getFingerprint();
};

/**
 * @return {number}
 */
COpenPgpKey.prototype.getBitSize = function ()
{
	return this.pgpKey.primaryKey.getBitSize();
};

/**
 * @return {string}
 */
COpenPgpKey.prototype.getArmor = function ()
{
	return this.pgpKey.armor();
};

/**
 * @return {boolean}
 */
COpenPgpKey.prototype.isPrivate = function ()
{
	return !!this.pgpKey.isPrivate();
};

/**
 * @return {boolean}
 */
COpenPgpKey.prototype.isPublic = function ()
{
	return !this.isPrivate();
};

module.exports = COpenPgpKey;