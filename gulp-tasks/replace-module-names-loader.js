module.exports = function (sSource) {
	this.cacheable();

	var
		aPath = this.resourcePath.split(/[\/\\]{1}modules[\/\\]{1}/),
		aPath2 = aPath[1] && aPath[1].split(/[\/\\]{1}/),
		sModule = aPath2 && aPath2[0]
	;
	
	if (sModule)
	{
		sSource = sSource.replace(new RegExp('modules/%ModuleName%', 'g'), 'modules/' + sModule).replace(new RegExp('\'\%MODULENAME\%/', 'g'), '\'' + sModule.toUpperCase() + '/');
	}
	
	return sSource;
};
