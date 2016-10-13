var
	crlf = '\n',
	cfg = {
		license:
crlf +
'/*!' + crlf +
' * Copyright 2004-2016, AfterLogic Corp.' + crlf +
' * Licensed under AGPLv3 license or AfterLogic license' + crlf +
' * if commerical version of the product was purchased.' + crlf +
' * See the LICENSE file for a full license statement.' + crlf +
' */' + crlf + crlf,
	}
;

require('./gulp-tasks/langs.js');

require('./gulp-tasks/javascript.js');

require('./gulp-tasks/styles.js');

//require('./gulp-tasks/html.js');

//require('./gulp-tasks/convert-langs.js');
