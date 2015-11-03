(function ($) {

/**
 * extend jQuery autocomplete
 */

	// styling results
	$.ui.autocomplete.prototype._renderItem = function (ul, item) {

		var aEmail = item.label.match(/[a-zA-Z0-9.\-_]+@[a-zA-Z0-9.\-]+/g);
		if (aEmail) {
			item.label = item.label.replace('<' + aEmail[0] + '>', "<span style='opacity: 0.5'>" + '&lt;' + aEmail[0] + '&gt' + "</span>"); //highlight <email>
		}

		return $('<li>')
			.append('<a>' + item.label + (item.global ? '' : '<span class="del"></span>') + '</a>')
			.appendTo(ul);
	};

	// add categories
	$.ui.autocomplete.prototype._renderMenu = function(ul, items) {
		
		var
			self = this,
			currentCategory = ''
		;

		$.each(items, function(index, item) {
			
			if (item && item.category && item.category !== currentCategory)
			{
				currentCategory = item.category;
				ul.append('<li class="ui-autocomplete-category">' + item.category + '</li>');
			}

			self._renderItemData(ul, item);
		});
	};

	// Prevent blur then you reach last/first element in list of suggestions
	$.ui.autocomplete.prototype._move = function(direction, event) {

		if ( !this.menu.element.is( ":visible" ) ) {
			this.search( null, event );
			return;
		}
		if ( this.menu.isFirstItem() && /^previous/.test( direction ))
		{
			this._value( this.term );
			this.menu._move( "first", "first", event );
		}
		else if ( this.menu.isLastItem() && /^next/.test( direction ))
		{
			this._value( this.term );
			this.menu._move( "last", "last", event );
		}

		this.menu[ direction ]( event );
	};
})(jQuery);