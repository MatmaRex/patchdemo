/* global OO, pd */
( function () {
	window.pd = window.pd || {};

	pd.installProgressField = OO.ui.infuse(
		document.getElementsByClassName( 'installProgressField' )[ 0 ]
	);

	pd.openWiki = OO.ui.infuse(
		document.getElementsByClassName( 'openWiki' )[ 0 ]
	);
}() );
