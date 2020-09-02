<?php

class DetailsFieldLayout extends OOUI\FieldLayout {
	public function __construct( $fieldWidget, array $config = [] ) {
		// Parent constructor
		parent::__construct( $fieldWidget, array_merge( $config, [
			// <summary> tag is no longer clickable if there's a <label> inside,
			// use a <span> instead
			'labelElement' => new OOUI\Tag( 'span' )
		] ) );

		// HACK: Replace header with an identical <summary> tag
		// and body with an identical <details> tag
		$this->removeContent( $this->body );

		$this->header = new OOUI\Tag( 'summary' );
		$this->header->addClasses( [ 'oo-ui-fieldLayout-header' ] );
		$this->body = new OOUI\Tag( 'details' );
		$this->body->addClasses( [ 'oo-ui-fieldLayout-body' ] );
		// This adds the content back
		$this->align = null;
		$this->setAlignment( $config['align'] );

		$this->prependContent( $this->body );
	}
}
