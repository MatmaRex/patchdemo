/* global OO */
window.DetailsFieldLayout = function DetailsFieldLayout( fieldWidget, config ) {
	// Allow passing positional parameters inside the config object
	if ( OO.isPlainObject( fieldWidget ) && config === undefined ) {
		config = fieldWidget;
		fieldWidget = config.fieldWidget;
	}

	config = config || {};

	// Parent constructor
	window.DetailsFieldLayout.super.call( this, fieldWidget, $.extend( {}, config, {
		// <summary> tag is no longer clickable if there's a <label> inside,
		// use a <span> instead
		$label: $( '<span>' )
	} ) );

	// HACK: Replace header with an identical <summary> tag
	// and body with an identical <details> tag
	this.$body.detach();

	this.$header = $( '<summary>' );
	this.$header.addClass( 'oo-ui-fieldLayout-header' );
	this.$body = $( '<details>' );
	this.$body.addClass( 'oo-ui-fieldLayout-body' );
	// This adds the content back
	this.align = null;
	this.setAlignment( config.align );

	this.$element.append( this.$body );
};

OO.inheritClass( window.DetailsFieldLayout, OO.ui.FieldLayout );
