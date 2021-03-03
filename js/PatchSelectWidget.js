/* global OO, $ */

window.PatchSelectWidget = function PatchSelectWidget( config ) {
	var widget = this;

	// Parent constructor
	window.PatchSelectWidget.super.call( this, $.extend( {
		allowArbitrary: true,
		allowDisplayInvalidTags: true
	}, config ) );

	this.$formInput = $( '<input>' ).attr( {
		type: 'hidden',
		name: config.name
	} );

	// Assume that a whole patch number was pasted
	this.input.$input.on( 'paste', function () {
		setTimeout( widget.afterPaste.bind( widget ) );
	} );

	this.$element
		.append( this.$formInput )
		.addClass( 'PatchSelectWidget' );
};

OO.inheritClass( window.PatchSelectWidget, OO.ui.TagMultiselectWidget );

window.PatchSelectWidget.static.patchCache = {};

window.PatchSelectWidget.prototype.afterPaste = function () {
	var widget = this,
		value = this.input.getValue();

	value.trim().split( /[ \n]/ ).forEach( function ( patch ) {
		patch = patch.trim();

		if ( patch && widget.addTag( patch, patch ) ) {
			widget.clearInput();
		}
	} );
};

window.PatchSelectWidget.prototype.onInputKeyPress = function ( e ) {
	var stopOrContinue,
		withMetaKey = e.metaKey || e.ctrlKey;

	if ( !this.isDisabled() ) {
		if ( e.which === OO.ui.Keys.SPACE ) {
			stopOrContinue = this.doInputEnter( e, withMetaKey );
		}

		// Make sure the input gets resized.
		this.updateInputSize();

		if ( stopOrContinue !== undefined ) {
			return stopOrContinue;
		}
	}

	return window.PatchSelectWidget.super.prototype.onInputKeyPress.apply( this, arguments );
};

window.PatchSelectWidget.prototype.createTagItemWidget = function () {
	var widget = this,
		patchCache = this.constructor.static.patchCache,
		// eslint-disable-next-line max-len
		item = window.PatchSelectWidget.super.prototype.createTagItemWidget.apply( this, arguments ),
		patch = item.getData().trim();

	function linkClick( e ) {
		e.stopPropagation();
	}

	item.setLabel( patch + ': …' );

	patchCache[ patch ] = patchCache[ patch ] || (
		patch.match( /^(I[0-9a-f]+|[0-9]+(,[0-9+])?)$/ ) ?
			$.get( 'api.php', { patch: patch } ) :
			$.Deferred().reject( 'Invalid patch number' ).promise()
	);
	patchCache[ patch ].then( function ( response ) {
		if ( !response.length ) {
			return $.Deferred().reject( 'Could not find patch' ).promise();
		} else if ( response.length > 1 ) {
			return $.Deferred().reject( 'Ambiguous query' ).promise();
		}
		item.setFlags( [] );
		// Normalize ID
		// eslint-disable-next-line no-underscore-dangle
		item.setData( patch.indexOf( 'I' ) === 0 ? response[ 0 ]._number : patch );
		item.setLabel(
			$( '<span>' ).append(
				document.createTextNode( item.getData() + ': ' ),
				$( '<a>' )
					.attr( {
						target: '_blank',
						href: 'https://gerrit.wikimedia.org/r/' + item.getData()
					} )
					.text( response[ 0 ].subject )
					.on( 'click', linkClick )
			)
		);
		// Update input after ID normalization
		widget.onChangeTags();
		widget.updateInputSize();
	} ).then( null, function ( err ) {
		item.setLabel( item.getData() + ': ' + err );
		item.toggleValid( false );
		widget.updateInputSize();
	} );

	return item;
};

window.PatchSelectWidget.prototype.onChangeTags = function () {
	window.PatchSelectWidget.super.prototype.onChangeTags.apply( this, arguments );

	this.$formInput.val(
		// Join items with a pipe as the hidden input is single line
		this.items.map( function ( item ) {
			return item.getData();
		} ).join( '|' )
	);
};

// Like the parent method, but works with outline, and uses getData instead of getLabel
window.PatchSelectWidget.prototype.doInputBackspace = function ( e, withMetaKey ) {
	var items, item;

	if (
		this.inputPosition === 'inline' &&
		this.input.getValue() === '' &&
		!this.isEmpty()
	) {
		// Delete the last item
		items = this.getItems();
		item = items[ items.length - 1 ];

		if ( !item.isDisabled() && !item.isFixed() ) {
			this.removeItems( [ item ] );
			// If Ctrl/Cmd was pressed, delete item entirely.
			// Otherwise put it into the text field for editing.
			if ( !withMetaKey ) {
				this.input.setValue( item.getData() );
			}
		}

		return false;
	}
};

window.PatchSelectWidget.prototype.onTagSelect = function ( item ) {
	if ( this.hasInput && this.allowEditTags && !item.isFixed() ) {
		if ( this.input.getValue() ) {
			this.addTagFromInput();
		}
		// 1. Get the label of the tag into the input
		this.input.setValue( item.getData() );
		// 2. Remove the tag
		this.removeItems( [ item ] );
		// 3. Focus the input
		this.focus();
	}
};
