
/**
 * @todo
 * @constructor
 */
function OpenPgpResult()
{
	this.result = true;
	this.errors = null;
	this.notices = null;
	this.exceptions = null;
}

OpenPgpResult.Enum = {
	'UnknownError': 0,
	'UnknownNotice': 1,
	'InvalidArgumentError': 2,
	'GenerateKeyError': 10,
	'ImportKeyError': 20,
	'ImportNoKeysFoundError': 21,
	'PrivateKeyNotFoundError': 30,
	'PublicKeyNotFoundError': 31,
	'KeyIsNotDecodedError': 32,
	'SignError': 40,
	'VerifyError': 41,
	'EncryptError': 42,
	'DecryptError': 43,
	'SignAndEncryptError': 44,
	'VerifyAndDecryptError': 45,
	'CanNotReadMessage': 50,
	'CanNotReadKey': 51,
	'DeleteError': 60,
	'PublicKeyNotFoundNotice': 70,
	'PrivateKeyNotFoundNotice': 71,
	'VerifyErrorNotice': 72,
	'NoSignDataNotice': 73
};

/**
 * @type {mixed}
 */
OpenPgpResult.prototype.result = false;

/**
 * @type {Array|null}
 */
OpenPgpResult.prototype.errors = null;

/**
 * @type {Array|null}
 */
OpenPgpResult.prototype.notices = null;

/**
 * @param {number} iCode
 * @param {string} sValue
 * @return {OpenPgpResult}
 */
OpenPgpResult.prototype.addError = function (iCode, sValue)
{
	this.result = false;
	this.errors = this.errors || [];
	this.errors.push([iCode || OpenPgpResult.Enum.UnknownError, sValue || '']);

	return this;
};

/**
 * @param {number} iCode
 * @param {string} sValue
 * @return {OpenPgpResult}
 */
OpenPgpResult.prototype.addNotice = function (iCode, sValue)
{
	this.notices = this.notices || [];
	this.notices.push([iCode || OpenPgpResult.Enum.UnknownNotice, sValue || '']);

	return this;
};

/**
 * @param {Error} e
 * @param {number=} iErrorCode
 * @param {string=} sErrorMessage
 * @return {OpenPgpResult}
 */
OpenPgpResult.prototype.addExceptionMessage = function (e, iErrorCode, sErrorMessage)
{
	if (e)
	{
		this.result = false;
		this.exceptions = this.exceptions || [];
		this.exceptions.push('' + (e.name || 'unknown') + ': ' + (e.message || ''));
	}

	if (!Utils.isUnd(iErrorCode))
	{
		this.addError(iErrorCode, sErrorMessage);
	}

	return this;
};

/**
 *  @return {boolean}
 */
OpenPgpResult.prototype.hasErrors = function ()
{
	return this.errors && 0 < this.errors.length;
};

/**
 *  @return {boolean}
 */
OpenPgpResult.prototype.hasNotices = function ()
{
	return this.notices && 0 < this.notices.length;
};
