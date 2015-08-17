
/**
 * @todo
 * @param {Object} oOpenPgp
 * @param {string=} sPrefix
 * @constructor
 */
function OpenPgp(oOpenPgp, sPrefix)
{
	this.pgp = oOpenPgp;
	this.pgpKeyring = new this.pgp.Keyring(new this.pgp.Keyring.localstore(sPrefix));
	
	this.keys = ko.observableArray([]);

	this.reloadKeysFromStorage();
}

OpenPgp.prototype.pgp = null;
OpenPgp.prototype.pgpKeyring = null;
OpenPgp.prototype.keys = [];

/**
 * @return {Array}
 */
OpenPgp.prototype.getKeys = function ()
{
	return this.keys();
};

/**
 * @return {mixed}
 */
OpenPgp.prototype.getKeysObservable = function ()
{
	return this.keys;
};

/**
 * @private
 */
OpenPgp.prototype.reloadKeysFromStorage = function ()
{
	var
		aKeys = [],
		oOpenpgpKeys = this.pgpKeyring.getAllKeys()
	;

	_.each(oOpenpgpKeys, function (oItem) {
		if (oItem && oItem.primaryKey)
		{
			aKeys.push(new OpenPgpKey(oItem));
		}
	});

	this.keys(aKeys);
};

/**
 * @private
 * @param {Array} aKeys
 * @return {Array}
 */
OpenPgp.prototype.convertToNativeKeys = function (aKeys)
{
	return _.map(aKeys, function (oItem) {
		return (oItem && oItem.pgpKey) ? oItem.pgpKey : oItem;
	});
};

/**
 * @private
 */
OpenPgp.prototype.cloneKey = function (oKey)
{
	var oPrivateKey = null;
	if (oKey)
	{
		oPrivateKey = this.pgp.key.readArmored(oKey.armor());
		if (oPrivateKey && !oPrivateKey.err && oPrivateKey.keys && oPrivateKey.keys[0])
		{
			oPrivateKey = oPrivateKey.keys[0];
			if (!oPrivateKey || !oPrivateKey.primaryKey)
			{
				oPrivateKey = null;
			}
		}
		else
		{
			oPrivateKey = null;
		}
	}

	return oPrivateKey;
};

/**
 * @private
 */
OpenPgp.prototype.decryptKeyHelper = function (oResult, oKey, sPassword, sKeyEmail)
{
	if (oKey)
	{
		try
		{
			oKey.decrypt(Utils.pString(sPassword));
			if (!oKey || !oKey.primaryKey || !oKey.primaryKey.isDecrypted)
			{
				oResult.addError(OpenPgpResult.Enum.KeyIsNotDecodedError, sKeyEmail || '');
			}
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.KeyIsNotDecodedError, sKeyEmail || '');
		}
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.KeyIsNotDecodedError, sKeyEmail || '');
	}
};

/**
 * @private
 */
OpenPgp.prototype.verifyMessageHelper = function (oResult, sFromEmail, oDecryptedMessage)
{
	var
		bResult = false,
		oValidKey = null,
		aVerifyResult = [],
		aVerifyKeysId = [],
		aPublicKeys = []
	;

	if (oDecryptedMessage && oDecryptedMessage.getSigningKeyIds)
	{
		aVerifyKeysId = oDecryptedMessage.getSigningKeyIds();
		if (aVerifyKeysId && 0 < aVerifyKeysId.length)
		{
			aPublicKeys = this.findKeysByEmails([sFromEmail], true);
			if (!aPublicKeys || 0 === aPublicKeys.length)
			{
				oResult.addNotice(OpenPgpResult.Enum.PublicKeyNotFoundNotice, sFromEmail);
			}
			else
			{
				aVerifyResult = [];
				try
				{
					aVerifyResult = oDecryptedMessage.verify(this.convertToNativeKeys(aPublicKeys));
				}
				catch (e)
				{
					oResult.addNotice(OpenPgpResult.Enum.VerifyErrorNotice, sFromEmail);
				}

				if (aVerifyResult && 0 < aVerifyResult.length)
				{
					oValidKey = _.find(aVerifyResult, function (oItem) {
						return oItem && oItem.keyid && oItem.valid;
					});

					if (oValidKey && oValidKey.keyid && 
						aPublicKeys && aPublicKeys[0] &&
						oValidKey.keyid.toHex().toLowerCase() === aPublicKeys[0].getId())
					{
						bResult = true;
					}
					else
					{
						oResult.addNotice(OpenPgpResult.Enum.VerifyErrorNotice, sFromEmail);
					}
				}
			}
		}
		else
		{
			oResult.addNotice(OpenPgpResult.Enum.NoSignDataNotice);
		}
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.UnknownError);
	}

	if (!bResult && !oResult.hasNotices())
	{
		oResult.addNotice(OpenPgpResult.Enum.VerifyErrorNotice);
	}

	return bResult;
};

/**
 * @param {string} sUserID
 * @param {string} sPassword
 * @param {number} nKeyLength
 *
 * @return {OpenPgpResult}
 */
OpenPgp.prototype.generateKey = function (sUserID, sPassword, nKeyLength)
{
	var 
		oResult = new OpenPgpResult(),
		mKeyPair = null
	;

	try
	{
//		mKeyPair = this.pgp.generateKeyPair(1, Utils.pInt(nKeyLength), sUserID, Utils.trim(sPassword));
		mKeyPair = this.pgp.generateKeyPair({
			'userId': sUserID,
			'numBits': Utils.pInt(nKeyLength),
			'passphrase': Utils.trim(sPassword)
		});
	}
	catch (e)
	{
		oResult.addExceptionMessage(e);
	}

	if (mKeyPair && mKeyPair.privateKeyArmored)
	{
		try
		{
			this.pgpKeyring.privateKeys.importKey(mKeyPair.privateKeyArmored);
			this.pgpKeyring.publicKeys.importKey(mKeyPair.publicKeyArmored);
			this.pgpKeyring.store();
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.GenerateKeyError);
		}
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.GenerateKeyError);
	}

	this.reloadKeysFromStorage();

	return oResult;
};

/**
 * @private
 * @param {string} sArmor
 * @return {Array}
 */
OpenPgp.prototype.splitKeys = function (sArmor)
{
	var
		aResult = [],
		iCount = 0,
		iLimit = 30,
		aMatch = null,
		sKey = Utils.trim(sArmor),
		oReg = /[\-]{3,6}BEGIN[\s]PGP[\s](PRIVATE|PUBLIC)[\s]KEY[\s]BLOCK[\-]{3,6}[\s\S]+?[\-]{3,6}END[\s]PGP[\s](PRIVATE|PUBLIC)[\s]KEY[\s]BLOCK[\-]{3,6}/gi
	;

	sKey = sKey.replace(/[\r\n]([a-zA-Z0-9]{2,}:[^\r\n]+)[\r\n]+([a-zA-Z0-9\/\\+=]{10,})/g, '\n$1---xyx---$2')
		.replace(/[\n\r]+/g, '\n').replace(/---xyx---/g, '\n\n');

	do
	{
		aMatch = oReg.exec(sKey);
		if (!aMatch || 0 > iLimit)
		{
			break;
		}

		if (aMatch[0] && aMatch[1] && aMatch[2] && aMatch[1] === aMatch[2])
		{
			if ('PRIVATE' === aMatch[1] || 'PUBLIC' === aMatch[1])
			{
				aResult.push([aMatch[1], aMatch[0]]);
				iCount++;
			}
		}

		iLimit--;
	}
	while (true);

	return aResult;
};

/**
 * @param {string} sArmor
 * @return {OpenPgpResult}
 */
OpenPgp.prototype.importKeys = function (sArmor)
{
	sArmor = Utils.trim(sArmor);

	var
		iIndex = 0,
		iCount = 0,
		oResult = new OpenPgpResult(),
		aData = null,
		aKeys = []
	;

	if (!sArmor)
	{
		return oResult.addError(OpenPgpResult.Enum.InvalidArgumentErrors);
	}

	aKeys = this.splitKeys(sArmor);

	for (iIndex = 0; iIndex < aKeys.length; iIndex++)
	{
		aData = aKeys[iIndex];
		if ('PRIVATE' === aData[0])
		{
			try
			{
				this.pgpKeyring.privateKeys.importKey(aData[1]);
				iCount++;
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.ImportKeyError, 'private');
			}
		}
		else if ('PUBLIC' === aData[0])
		{
			try
			{
				this.pgpKeyring.publicKeys.importKey(aData[1]);
				iCount++;
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.ImportKeyError, 'public');
			}
		}
	}

	if (0 < iCount)
	{
		this.pgpKeyring.store();
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.ImportNoKeysFoundError);
	}

	this.reloadKeysFromStorage();

	return oResult;
};

/**
 * @param {string} sArmor
 * @return {Array|boolean}
 */
OpenPgp.prototype.getArmorInfo = function (sArmor)
{
	sArmor = Utils.trim(sArmor);

	var
		iIndex = 0,
		iCount = 0,
		oKey = null,
		aResult = [],
		aData = null,
		aKeys = []
	;

	if (!sArmor)
	{
		return false;
	}

	aKeys = this.splitKeys(sArmor);

	for (iIndex = 0; iIndex < aKeys.length; iIndex++)
	{
		aData = aKeys[iIndex];
		if ('PRIVATE' === aData[0])
		{
			try
			{
				oKey = this.pgp.key.readArmored(aData[1]);
				if (oKey && !oKey.err && oKey.keys && oKey.keys[0])
				{
					aResult.push(new OpenPgpKey(oKey.keys[0]));
				}
				
				iCount++;
			}
			catch (e)
			{
				aResult.push(null);
			}
		}
		else if ('PUBLIC' === aData[0])
		{
			try
			{
				oKey = this.pgp.key.readArmored(aData[1]);
				if (oKey && !oKey.err && oKey.keys && oKey.keys[0])
				{
					aResult.push(new OpenPgpKey(oKey.keys[0]));
				}

				iCount++;
			}
			catch (e)
			{
				aResult.push(null);
			}
		}
	}

	return aResult;
};

/**
 * @param {string} sID
 * @param {boolean} bPublic
 * @return {OpenPgpKey|null}
 */
OpenPgp.prototype.findKeyByID = function (sID, bPublic)
{
	bPublic = !!bPublic;
	sID = sID.toLowerCase();
	
	var oKey = _.find(this.keys(), function (oKey) {
		
		var
			oResult = false,
			aKeys = null
		;
		
		if (oKey && bPublic === oKey.isPublic())
		{
			aKeys = oKey.pgpKey.getKeyIds();
			if (aKeys)
			{
				oResult = _.find(aKeys, function (oKey) {
					return oKey && oKey.toHex && sID === oKey.toHex().toLowerCase();
				});
			}
		}
		
		return !!oResult;
	});

	return oKey ? oKey : null;
};

/**
 * @param {Array} aEmail
 * @param {boolean} bIsPublic
 * @param {OpenPgpResult=} oResult
 * @return {Array}
 */
OpenPgp.prototype.findKeysByEmails = function (aEmail, bIsPublic, oResult)
{
	bIsPublic = !!bIsPublic;
	
	var
		aResult = [],
		aKeys = this.keys()
	;
	_.each(aEmail, function (sEmail) {

		var oKey = _.find(aKeys, function (oKey) {
			return oKey && bIsPublic === oKey.isPublic() && sEmail === oKey.getEmail();
		});

		if (oKey)
		{
			aResult.push(oKey);
		}
		else
		{
			if (oResult)
			{
				oResult.addError(bIsPublic ?
					OpenPgpResult.Enum.PublicKeyNotFoundError : OpenPgpResult.Enum.PrivateKeyNotFoundError, sEmail);
			}
		}
	});

	return aResult;
};

/**
 * @param {string} sData
 * @param {string} sAccountEmail
 * @param {string} sFromEmail
 * @param {string=} sPrivateKeyPassword = ''
 * @return {string}
 */
OpenPgp.prototype.decryptAndVerify = function (sData, sAccountEmail, sFromEmail, sPrivateKeyPassword)
{
	var
		self = this,
		oMessage = null,
		oPrivateEmailKey = null,
		oPrivateKey = null,
		oPrivateKeyClone = null,
		oMessageDecrypted = null,
		oResult = new OpenPgpResult(),
		aEncryptionKeyIds = []
	;

	oMessage = this.pgp.message.readArmored(sData);
	if (oMessage && oMessage.decrypt)
	{
		aEncryptionKeyIds = oMessage.getEncryptionKeyIds();
		if (aEncryptionKeyIds)
		{
			oPrivateKey = null;
			oPrivateEmailKey = null;
			
			_.each(aEncryptionKeyIds, function (oKey) {
				if (!oPrivateEmailKey)
				{
					oPrivateEmailKey = self.findKeyByID(oKey.toHex(), false);
					if (oPrivateEmailKey && sAccountEmail !== oPrivateEmailKey.getEmail())
					{
						oPrivateEmailKey = null;
					}
				}
			});

			if (oPrivateEmailKey)
			{
				oPrivateKey = oPrivateEmailKey;
			}

			if (!oPrivateKey)
			{
				_.each(aEncryptionKeyIds, function (oKey) {
					if (!oPrivateKey)
					{
						oPrivateKey = self.findKeyByID(oKey.toHex(), false);
					}
				});
			}
		}

		if (!oPrivateKey)
		{
			oResult.addError(OpenPgpResult.Enum.PrivateKeyNotFoundError);
		}
		else
		{
			oPrivateKeyClone = this.cloneKey(this.convertToNativeKeys([oPrivateKey])[0]);
			
			this.decryptKeyHelper(oResult, oPrivateKeyClone, sPrivateKeyPassword, oPrivateKey.getEmail());

			if (oPrivateKeyClone && !oResult.hasErrors())
			{
				try
				{
					oMessageDecrypted = oMessage.decrypt(oPrivateKeyClone);
				}
				catch (e)
				{
					oResult.addExceptionMessage(e, OpenPgpResult.Enum.DecryptError);
					oMessageDecrypted = null;
				}
			}

			if (oMessageDecrypted && !oResult.hasErrors())
			{
				this.verifyMessageHelper(oResult, sFromEmail, oMessageDecrypted);

				oResult.result = oMessageDecrypted.getText();
			}
		}
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @return {string}
 */
OpenPgp.prototype.verify = function (sData, sFromEmail)
{
	var
		oMessageDecrypted = null,
		oResult = new OpenPgpResult()
	;

	oMessageDecrypted = this.pgp.cleartext.readArmored(sData);
	if (oMessageDecrypted && oMessageDecrypted.getText && oMessageDecrypted.verify)
	{
		this.verifyMessageHelper(oResult, sFromEmail, oMessageDecrypted);

		oResult.result = oMessageDecrypted.getText();
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.CanNotReadMessage);
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {Array} aPrincipalsEmail
 * @return {string}
 */
OpenPgp.prototype.encrypt = function (sData, aPrincipalsEmail)
{
	var
		oResult = new OpenPgpResult(),
		aPublicKeys = this.findKeysByEmails(aPrincipalsEmail, true, oResult)
	;

	if (!oResult.hasErrors())
	{
		try
		{
			oResult.result = this.pgp.encryptMessage(
				this.convertToNativeKeys(aPublicKeys), sData);
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.EncryptError);
		}
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @param {string=} sPrivateKeyPassword
 * @return {string}
 */
OpenPgp.prototype.sign = function (sData, sFromEmail, sPrivateKeyPassword)
{
	var
		oResult = new OpenPgpResult(),
		oPrivateKey = null,
		oPrivateKeyClone = null,
		aPrivateKeys = this.findKeysByEmails([sFromEmail], false, oResult)
	;

	if (!oResult.hasErrors())
	{
		oPrivateKey = this.convertToNativeKeys(aPrivateKeys)[0];
		oPrivateKeyClone = this.cloneKey(oPrivateKey);

		this.decryptKeyHelper(oResult, oPrivateKeyClone, sPrivateKeyPassword, sFromEmail);

		if (oPrivateKeyClone && !oResult.hasErrors())
		{
			try
			{
				oResult.result = this.pgp.signClearMessage([oPrivateKeyClone], sData);
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.SignError, sFromEmail);
			}
		}
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @param {Array} aPrincipalsEmail
 * @param {string=} sPrivateKeyPassword
 * @return {string}
 */
OpenPgp.prototype.signAndEncrypt = function (sData, sFromEmail, aPrincipalsEmail, sPrivateKeyPassword)
{
	var
		oPrivateKey = null,
		oPrivateKeyClone = null,
		oResult = new OpenPgpResult(),
		aPrivateKeys = this.findKeysByEmails([sFromEmail], false, oResult),
		aPublicKeys = this.findKeysByEmails(aPrincipalsEmail, true, oResult)
	;

	if (!oResult.hasErrors())
	{
		oPrivateKey = this.convertToNativeKeys(aPrivateKeys)[0];
		oPrivateKeyClone = this.cloneKey(oPrivateKey);

		this.decryptKeyHelper(oResult, oPrivateKeyClone, sPrivateKeyPassword, sFromEmail);
		
		if (oPrivateKeyClone && !oResult.hasErrors())
		{
			try
			{
				oResult.result = this.pgp.signAndEncryptMessage(
					this.convertToNativeKeys(aPublicKeys), oPrivateKeyClone, sData);
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.SignAndEncryptError);
			}
		}
	}
	
	return oResult;
};

/**
 * @param {OpenPgpKey} oKey
 */
OpenPgp.prototype.deleteKey = function (oKey)
{
	var oResult = new OpenPgpResult();
	if (oKey)
	{
		try
		{
			this.pgpKeyring[oKey.isPrivate() ? 'privateKeys' : 'publicKeys'].removeForId(oKey.getFingerprint());
			this.pgpKeyring.store();
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.DeleteError);
		}
	}
	else
	{
		oResult.addError(oKey ? OpenPgpResult.Enum.UnknownError : OpenPgpResult.Enum.InvalidArgumentError);
	}

	this.reloadKeysFromStorage();

	return oResult;
};
