'use strict';

module.exports = {
	'OpenPgpErrors': {
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
	},
	'PgpAction': {
		'Import': 'import',
		'Generate': 'generate',
		'Encrypt': 'encrypt',
		'Sign': 'sign',
		'EncryptSign': 'encrypt-sign',
		'Verify': 'ferify',
		'DecryptVerify': 'decrypt-ferify'
	}
};
