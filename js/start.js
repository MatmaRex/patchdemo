/* global OO, pd */
( function () {
	window.pd = window.pd || {};

	var installProgressField = OO.ui.infuse(
		document.getElementsByClassName( 'installProgressField' )[ 0 ]
	);

	installProgressField.fieldWidget.pushPending();

	var openWiki = OO.ui.infuse(
		document.getElementsByClassName( 'openWiki' )[ 0 ]
	);

	function endProgress() {
		installProgressField.fieldWidget.popPending();
		pd.finished = true;
	}

	pd.abandon = function ( html ) {
		installProgressField.fieldWidget.setDisabled( true );
		installProgressField.setErrors( [ new OO.ui.HtmlSnippet( html ) ] );
		pd.notify( 'Your PatchDemo wiki failed to build', html );
		endProgress();
	};

	pd.setProgress = function ( pc, label ) {
		installProgressField.fieldWidget.setProgress( pc );
		installProgressField.setLabel( label );
		if ( pc === 100 ) {
			openWiki.setDisabled( false );
			pd.notify( 'Your PatchDemo wiki is ready!' );
			endProgress();
		}
	};

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

	$( function () {
		// eslint-disable-next-line no-jquery/no-global-selector
		var $log = $( '.newWikiLog' );
		var log = '';
		var offset = 0;
		function poll() {
			$.get( 'log.php', {
				wiki: pd.wiki,
				offset: offset
			} ).then( function ( result ) {
				if ( result ) {
					// result can be unbalanced HTML, so store it in
					// a string and rewrite the whole thing each time
					log += result;
					$log.html( log );
					offset += result.length;
				}
				if ( !pd.finished ) {
					setTimeout( poll, 1000 );
				}
			} );
		}

		poll();

		// Add wiki to URL so that page can be shared/reloaded
		history.replaceState( null, '', 'start.php?wiki=' + pd.wiki );
	} );

}() );
