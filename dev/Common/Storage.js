/**
 * Object for saving and restoring data in local storage or cookies.
 * 
 * @constructor
 */
function CStorage()
{
	this.bHtml5 = true;
	
	this.init();
}

/**
 * Returns **true** if data with specified key exists in the storage.
 * 
 * @param {string} sKey
 * @returns {boolean}
 */
CStorage.prototype.hasData = function (sKey)
{
	var sValue = this.bHtml5 ? localStorage.getItem(sKey) : $.cookie(sKey);
	
	return !!sValue;
};

/**
 * Returns value of data with specified key from the storage.
 * 
 * @param {string} sKey
 * @returns {string|number|Object}
 */
CStorage.prototype.getData = function (sKey)
{
	var sValue = this.bHtml5 ? localStorage.getItem(sKey) : $.cookie(sKey);
	
	return $.parseJSON(sValue);
};

/**
 * Sets value of data with specified key to the storage.
 * 
 * @param {string} sKey
 * @param {string|number|Object} mValue
 */
CStorage.prototype.setData = function (sKey, mValue)
{
	var sValue = JSON.stringify(mValue);
	
	if (this.bHtml5)
	{
		localStorage.setItem(sKey, sValue);
	}
	else
	{
		$.cookie(sKey, sValue, { expires: 30 });
	}
};

/**
 * Removes data with specified key from the storage.
 * 
 * @param {srting} sKey
 */
CStorage.prototype.removeData = function (sKey)
{
	if (this.bHtml5)
	{
		localStorage.removeItem(sKey);
	}
	else
	{
		$.cookie(sKey, null);
	}
};

/**
 * Initializes the object for work with local storage or cookie.
 */
CStorage.prototype.init = function ()
{
	if (typeof Storage === 'undefined')
	{
		this.bHtml5 = false;
	}
	else
	{
		try
		{
			localStorage.setItem('check', 'val');
			localStorage.removeItem('check');
		}
		catch (err)
		{
			this.bHtml5 = false;
		}
	}
};
