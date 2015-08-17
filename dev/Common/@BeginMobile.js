bMobileApp = true;

if (window.Modernizr && navigator)
{
	window.Modernizr.addTest('native-android-browser', function() {
		var ua = navigator.userAgent;
		return (ua.indexOf('Mozilla/5.0') > -1 && ua.indexOf('Android ') > -1 && ua.indexOf('534') > -1 && ua.indexOf('AppleWebKit') > -1);
		//return navigator.userAgent === 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.34 Safari/534.24';
	});
}
