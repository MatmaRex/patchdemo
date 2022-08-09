<?php

class ComboBoxInputWidget extends OOUI\ComboBoxInputWidget {
	public function getConfig( &$config ) {
		$config['menu'] = [ 'filterFromInput' => true ];
		return parent::getConfig( $config );
	}

	/**
	 * @inheritDoc
	 */
	protected function getJavaScriptClassName() {
		return 'OO.ui.ComboBoxInputWidget';
	}
}
