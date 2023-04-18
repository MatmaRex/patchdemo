( function () {
	pd.patchIndex = {};

	// Sort patches for comparison later. It doesn't matter
	// that Array#sort is lexicographical.
	for ( const wiki in pd.wikiPatches ) {
		const patchKey = pd.wikiPatches[ wiki ].sort().join( '|' );
		if ( patchKey ) {
			pd.patchIndex[ patchKey ] = pd.patchIndex[ patchKey ] || [];
			pd.patchIndex[ patchKey ].push( wiki );
		}
	}
}() );

window.PatchSelectWidget = function PatchSelectWidget( config ) {
	this.$formInput = $( '<input>' ).attr( {
		type: 'hidden',
		name: config.name
	} );

	// Parent constructor
	window.PatchSelectWidget.super.call( this, $.extend( {
		allowArbitrary: true,
		allowDisplayInvalidTags: true,
		selected: config.value && config.value.split( '\n' )
	}, config ) );

	// Assume that a whole patch number was pasted
	// TODO: Upstream?
	this.input.$input.on( 'paste', () => {
		setTimeout( this.doInputEnter.bind( this ) );
	} );

	this.$element
		.append( this.$formInput )
		.addClass( 'PatchSelectWidget' );
};

OO.inheritClass( window.PatchSelectWidget, OO.ui.TagMultiselectWidget );

window.PatchSelectWidget.static.patchCache = {};

window.PatchSelectWidget.prototype.getTagInfoFromInput = function ( value ) {
	value = value || this.input.getValue();

	const gerritUrlPattern = new RegExp( pd.config.gerritUrl + '.*?/([0-9]+(?:/[0-9]+)?)/?$' );

	value = value.trim();

	const matches = value.match( gerritUrlPattern );
	if ( matches ) {
		value = matches[ 1 ].replace( '/', ',' );
	}

	return value ?
		{ data: value, label: value } :
		null;
};

window.PatchSelectWidget.prototype.addTagFromInput = function () {
	// Handle multi line inputs, e.g. from paste
	// TODO: Upstream?
	const tagInfos = this.input.getValue().trim().split( /[ \n]/ )
		.map( this.getTagInfoFromInput.bind( this ) );

	tagInfos.forEach( ( tagInfo ) => {
		if ( tagInfo && this.addTag( tagInfo.data, tagInfo.label ) ) {
			this.clearInput();
		}
	} );
};

window.PatchSelectWidget.prototype.onInputKeyPress = function ( e ) {
	if ( !this.isDisabled() ) {
		let stopOrContinue;
		if ( e.which === OO.ui.Keys.SPACE ) {
			const withMetaKey = e.metaKey || e.ctrlKey;
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
	const patchCache = this.constructor.static.patchCache,
		// eslint-disable-next-line max-len
		item = window.PatchSelectWidget.super.prototype.createTagItemWidget.apply( this, arguments ),
		patch = item.getData().trim();

	function linkClick( e ) {
		e.stopPropagation();
	}

	item.setLabel( patch + ': …' );
	item.setData( { input: patch } );

	patchCache[ patch ] = patchCache[ patch ] || (
		patch.match( /^(I[0-9a-f]+|[0-9]+(,[0-9]+)?)$/ ) ?
			$.get( 'api.php', { action: 'patchmeta', patch: patch } ) :
			$.Deferred().reject( 'Invalid patch number' ).promise()
	);
	patchCache[ patch ].then( ( response ) => {
		const data = { input: patch };
		if ( !response.length ) {
			return $.Deferred().reject( 'Could not find patch' ).promise();
		} else if ( response.length > 1 ) {
			return $.Deferred().reject( 'Ambiguous query' ).promise();
		}
		data.r = response[ 0 ].r ||
			// eslint-disable-next-line no-underscore-dangle
			( patch.indexOf( 'I' ) === 0 ? response[ 0 ]._number : patch );
		data.p = response[ 0 ].p ||
			// eslint-disable-next-line no-underscore-dangle
			response[ 0 ].revisions[ response[ 0 ].current_revision ]._number;
		data.linkedTasks = response[ 0 ].linkedTasks;

		item.setFlags( [] );
		item.setData( data );
		item.setLabel(
			$( '<span>' ).append(
				document.createTextNode( data.r + ',' + data.p + ': ' ),
				$( '<a>' )
					.attr( {
						target: '_blank',
						href: pd.config.gerritUrl + '/r/' + data.r
					} )
					.text( response[ 0 ].subject )
					.on( 'click', linkClick )
			)
		);
		// Update input after ID normalization
		this.onChangeTags();
		this.updateInputSize();
	} ).then( null, ( err ) => {
		item.setLabel( item.getData().input + ': ' + err );
		item.toggleValid( false );
		this.updateInputSize();
	} );

	return item;
};

window.PatchSelectWidget.prototype.onChangeTags = function () {
	window.PatchSelectWidget.super.prototype.onChangeTags.apply( this, arguments );

	this.$formInput.val(
		// Join items with a pipe as the hidden input is single line
		this.items.map( ( item ) => {
			const data = item.getData();
			return data.r ?
				// 'latest' means the user didn't specify a patchset, and so wants the latest one.
				( data.latest ? data.r : data.r + ',' + data.p ) :
				data.input;
		} ).join( '|' )
	);

	const linkedTasks = {};
	this.items.forEach( ( item ) => {
		const data = item.getData();
		if ( data.linkedTasks ) {
			data.linkedTasks.forEach( ( task ) => {
				linkedTasks[ task ] = true;
			} );
		}
	} );

	this.emit( 'linkedTasks', Object.keys( linkedTasks ) );

	const patchKey = this.items.map( ( item ) => {
		const data = item.getData();
		return data.r + ',' + data.p;
	} ).sort().join( '|' );
	this.emit( 'matchWikis', pd.patchIndex[ patchKey ] );
};

// Like the parent method, but works with outline, and uses getData instead of getLabel
window.PatchSelectWidget.prototype.doInputBackspace = function ( e, withMetaKey ) {
	if (
		this.inputPosition === 'inline' &&
		this.input.getValue() === '' &&
		!this.isEmpty()
	) {
		// Delete the last item
		const items = this.getItems();
		const item = items[ items.length - 1 ];

		if ( !item.isDisabled() && !item.isFixed() ) {
			this.removeItems( [ item ] );
			// If Ctrl/Cmd was pressed, delete item entirely.
			// Otherwise put it into the text field for editing.
			if ( !withMetaKey ) {
				this.input.setValue( item.getData().input );
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
		this.input.setValue( item.getData().input );
		// 2. Remove the tag
		this.removeItems( [ item ] );
		// 3. Focus the input
		this.focus();
	}
};
