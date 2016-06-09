//js-in-html-template
ko.bindingHandlers.simpleTemplate = {
	'init': function (oElement, fValueAccessor) {
		var oEl = $(oElement);
		
		if (oEl.length > 0 && oEl.data('replaced') !== 'replaced')
		{
			oEl.html(oEl.html().replace(/&lt;script(.*?)&gt;/i, '<script$1>').replace(/&lt;\/script(.*?)&gt;/i, '</script>'));
			oEl.data('replaced', 'replaced');
		}
	}
};

//gregwar-captcha
ko.bindingHandlers.findFocused = {
	'init': function (oElement) {

		var
			$oEl = $(oElement),
			$oInp = null
		;

		$oInp = $oEl.find('.catch-focus');
		if ($oInp && 1 === $oInp.length && $oInp[0])
		{
			$oInp.on('blur', function () {
				$oEl.removeClass('focused');
			}).on('focus', function () {
				$oEl.addClass('focused');
			});
		}
	}
};

//gregwar-captcha
ko.bindingHandlers.findFilled = {
	'init': function (oElement) {

		var
			$oEl = $(oElement),
			$oInp = null,
			fFunc = null
		;

		$oInp = $oEl.find('.catch-filled');
		if ($oInp && 1 === $oInp.length && $oInp[0])
		{
			fFunc = function () {
				$oEl.toggleClass('filled', '' !== $oInp.val());
			};

			fFunc();
			_.delay(fFunc, 200);
			$oInp.on('change', fFunc);
		}
	}
};

