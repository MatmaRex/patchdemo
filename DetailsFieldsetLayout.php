<?php

class DetailsFieldsetLayout extends OOUI\FieldsetLayout {
	public static $tagName = 'details';

	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// HACK: Replace header with an identical <summary> tag
		$this->removeContent( $this->header );

		$this->header = new OOUI\Tag( 'summary' );
		$this->header
			->addClasses( [ 'oo-ui-fieldsetLayout-header' ] )
			->appendContent( $this->icon, $this->label );

		$this->prependContent( $this->header );
	}
}
