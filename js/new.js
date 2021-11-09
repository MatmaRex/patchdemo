/* global OO, pd */
( function () {
	window.pd = window.pd || {};

	pd.installProgressField = OO.ui.infuse(
		document.getElementsByClassName( 'installProgressField' )[ 0 ]
	);

	pd.installProgressField.fieldWidget.pushPending();

	pd.openWiki = OO.ui.infuse(
		document.getElementsByClassName( 'openWiki' )[ 0 ]
	);

	pd.notify = function ( message, body ) {
		if ( 'Notification' in window && +localStorage.getItem( 'patchdemo-notifications' ) ) {
			// eslint-disable-next-line no-new
			new Notification(
				message,
				{
					icon: './images/favicon-32x32.png',
					body: body
				}
			);
		}
	};

}() );
