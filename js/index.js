/* global OO, $ */
( function () {
	window.pd = window.pd || {};

	var form = document.getElementById( 'new-form' );
	if ( form ) {
		var submit = OO.ui.infuse( $( '.form-submit' ) );
		var patchesInput = OO.ui.infuse( $( '.form-patches' ) );
		var patchesLayout = OO.ui.infuse( $( '.form-patches-layout' ) );

		form.addEventListener( 'submit', function ( e ) {
			// Blur is not fired on patchesInput, so call manually
			patchesInput.doInputEnter();

			if ( !patchesInput.getValue().length ) {
				OO.ui.confirm(
					'Are you sure you want to create a demo with no patches applied?'
				).then( function ( confirmed ) {
					if ( confirmed ) {
						form.submit();
					}
				} );
				e.preventDefault();
				return;
			}

			submit.setDisabled( true );
			return false;
		} );

		patchesInput.on( 'matchWikis', function ( wikis ) {
			patchesLayout.setWarnings(
				( wikis || [] ).map( function ( wiki ) {
					wiki = wiki.slice( 0, 10 );
					return $( '<span>' ).append(
						document.createTextNode( 'A wiki with these patches already exists: ' ),
						$( '<a>' ).addClass( 'wiki' ).attr( 'href', '#' + wiki ).text( wiki )
					);
				} )
			);
		} );

		if ( $( '.myWikis' ).length ) {
			var $wikisTable = $( '.wikis' );
			var myWikis = OO.ui.infuse( $( '.myWikis' ) );
			var closedWikis = OO.ui.infuse( $( '.closedWikis' ) );

			// eslint-disable-next-line no-inner-declarations
			function updateTableClasses() {
				$wikisTable.toggleClass( 'hideOthers', !!myWikis.isSelected() );
				$wikisTable.toggleClass( 'hideOpen', !!closedWikis.isSelected() );
			}

			myWikis.on( 'change', updateTableClasses );
			closedWikis.on( 'change', updateTableClasses );

			if ( $( '.showClosedButton' ).length ) {
				var showClosedButton = OO.ui.infuse( $( '.showClosedButton' ) );
				showClosedButton.on( 'click', function () {
					myWikis.setSelected( true );
					closedWikis.setSelected( true );
					updateTableClasses();
				} );
			}
		}

		var presetInput = OO.ui.infuse( $( '.form-preset' ) );
		var reposInput = OO.ui.infuse( $( '.form-repos' ) );
		var reposField = OO.ui.infuse( $( '.form-repos-field' ) );
		var branchSelect = OO.ui.infuse( $( '.form-branch' ) );

		branchSelect.on( 'change', function () {
			var branch, repo, validBranch;
			branch = branchSelect.value;
			for ( repo in window.repoBranches ) {
				validBranch = window.repoBranches[ repo ].indexOf( branch ) !== -1;
				reposInput.checkboxMultiselectWidget
					.findItemFromData( repo )
					.setDisabled( !validBranch || repo === 'mediawiki/core' );
			}
			reposInput.emit( 'change' );
		} );

		var reposFieldLabel = reposField.getLabel();

		presetInput.on( 'change', OO.ui.debounce( function () {
			var val = presetInput.getValue();
			if ( val === 'custom' ) {
				reposField.$body[ 0 ].open = true;
			}
			if ( val !== 'custom' ) {
				reposInput.setValue( window.presets[ val ] );
			}
		} ) );
		reposInput.on( 'change', OO.ui.debounce( function () {
			var selected = 0, enabled = 0,
				val, presetName, matchingPresetName;

			val = reposInput.getValue();
			matchingPresetName = 'custom';
			for ( presetName in window.presets ) {
				if ( window.presets[ presetName ].sort().join( '|' ) === val.sort().join( '|' ) ) {
					matchingPresetName = presetName;
					break;
				}
			}
			if ( presetInput.getValue() !== matchingPresetName ) {
				presetInput.setValue( matchingPresetName );
			}

			reposInput.checkboxMultiselectWidget.items.forEach( function ( option ) {
				if ( !option.isDisabled() ) {
					enabled++;
					if ( option.isSelected() ) {
						selected++;
					}
				}
			} );

			reposField.setLabel( reposFieldLabel + ' (' + selected + '/' + enabled + ')' );
		} ) );

		reposInput.emit( 'change' );
	}

	var $lastMatch = $( [] );
	$( window ).on( 'hashchange', function () {
		if ( location.hash.match( /^#[0-9a-f]{10}$/ ) ) {
			$lastMatch.removeClass( 'highlight' );
			$lastMatch = $( location.hash ).closest( 'tr' );
			$lastMatch.addClass( 'highlight' );
		}
	} );
	$( window ).trigger( 'hashchange' );

}() );
