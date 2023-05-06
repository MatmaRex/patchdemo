/* eslint-disable no-jquery/no-global-selector */

( function () {
	window.pd = window.pd || {};

	const form = document.getElementById( 'new-form' );
	if ( form ) {
		const submit = OO.ui.infuse( $( '.form-submit' ) );
		const patchesInput = OO.ui.infuse( $( '.form-patches' ) );
		const patchesLayout = OO.ui.infuse( $( '.form-patches-layout' ) );

		form.addEventListener( 'submit', ( e ) => {
			// Blur is not fired on patchesInput, so call manually
			patchesInput.doInputEnter();

			if ( !patchesInput.getValue().length ) {
				OO.ui.confirm(
					'Are you sure you want to create a demo with no patches applied?'
				).then( ( confirmed ) => {
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

		patchesInput.on( 'matchWikis', ( wikis ) => {
			patchesLayout.setWarnings(
				( wikis || [] ).map( ( wiki ) => {
					wiki = wiki.slice( 0, 10 );
					return $( '<span>' ).append(
						document.createTextNode( 'A wiki with these patches already exists: ' ),
						$( '<a>' ).addClass( 'wiki' ).attr( 'href', '#' + wiki ).text( wiki )
					);
				} )
			);
		} );

		const landingPageInput = OO.ui.infuse( $( '.form-landingPage' ) );

		if ( $( '.form-announce' ).length ) {
			const announceLayout = OO.ui.infuse( $( '.form-announce-layout' ) );
			const taskLabel = new OO.ui.LabelWidget( { classes: [ 'form-announce-taskList' ] } );
			announceLayout.$field.append( taskLabel.$element );

			const updateLinkedTasks = ( linkedTasks ) => {
				let $label = $( [] );
				if ( !linkedTasks.length ) {
					$label = $( '<em>' ).text( 'No linked tasks found.' );
				} else {
					linkedTasks.forEach( ( task ) => {
						const id = 'T' + task;
						if ( $label.length ) {
							$label = $label.add( document.createTextNode( ', ' ) );
						}
						$label = $label.add(
							$( '<a>' )
								.attr( {
									href: window.pd.config.phabricatorUrl + '/' + id,
									target: '_blank'
								} )
								.text( id )
						);
					} );
				}
				taskLabel.setLabel( $label );
			};

			patchesInput.on( 'linkedTasks', updateLinkedTasks );
			updateLinkedTasks( [] );
		}

		if ( $( '.closedWikis' ).length ) {
			const $wikisTable = $( '.wikis' );
			const closedWikis = OO.ui.infuse( $( '.closedWikis' ) );

			// eslint-disable-next-line no-inner-declarations
			function updateTableClasses() {
				$wikisTable.toggleClass( 'hideOpen', !!closedWikis.isSelected() );
			}

			closedWikis.on( 'change', updateTableClasses );

			if ( $( '.showClosedButton' ).length ) {
				const showClosedButton = OO.ui.infuse( $( '.showClosedButton' ) );
				showClosedButton.on( 'click', () => {
					closedWikis.setSelected( true );
					updateTableClasses();
				} );
			}
		}

		const presetInput = OO.ui.infuse( $( '.form-preset' ) );
		const reposInput = OO.ui.infuse( $( '.form-repos' ) );
		const reposField = OO.ui.infuse( $( '.form-repos-field' ) );
		const branchSelect = OO.ui.infuse( $( '.form-branch' ) );

		branchSelect.on( 'change', () => {
			const branch = branchSelect.value;
			for ( const repo in window.repoBranches ) {
				const validBranch = window.repoBranches[ repo ].indexOf( branch ) !== -1;
				reposInput.checkboxMultiselectWidget
					.findItemFromData( repo )
					.setDisabled( !validBranch || repo === 'mediawiki/core' );
			}
			reposInput.emit( 'change' );
		} );

		const reposFieldLabel = reposField.getLabel();

		presetInput.on( 'change', OO.ui.debounce( () => {
			const val = presetInput.getValue();
			if ( val === 'custom' ) {
				reposField.$body[ 0 ].open = true;
			}
			if ( val !== 'custom' ) {
				reposInput.setValue( window.presets[ val ] );
			}
		} ) );
		reposInput.on( 'change', OO.ui.debounce( () => {
			const val = reposInput.getValue();
			let matchingPresetName = 'custom';
			for ( const presetName in window.presets ) {
				if ( window.presets[ presetName ].sort().join( '|' ) === val.sort().join( '|' ) ) {
					matchingPresetName = presetName;
					break;
				}
			}
			if ( presetInput.getValue() !== matchingPresetName ) {
				presetInput.setValue( matchingPresetName );
			}

			let selected = 0, enabled = 0;
			reposInput.checkboxMultiselectWidget.items.forEach( ( option ) => {
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

		const instantCommonsCheckbox = OO.ui.infuse( $( '.form-instantCommons' ) );
		const instantCommonsMethodDropdown = OO.ui.infuse( $( '.form-instantCommonsMethod' ) );

		instantCommonsCheckbox.on( 'change', ( value ) => {
			instantCommonsMethodDropdown.setDisabled( !value );
		} );

		const languageInput = OO.ui.infuse( $( '.form-language' ) );
		languageInput.setValidation( /^[a-z-]{2,}$/ );

		$( '.copyWiki' ).on( 'click', function ( e ) {
			const params = new URL( this.href ).searchParams;
			patchesInput.setValue( params.get( 'patches' ) ? params.get( 'patches' ).split( ',' ) : [] );
			branchSelect.setValue( 'origin/' + params.get( 'branch' ) );
			branchSelect.scrollElementIntoView( { padding: { top: $( 'header' ).height() + 10 } } );
			landingPageInput.setValue( params.get( 'landingPage' ) );
			e.preventDefault();
		} );

		if ( 'Notification' in window ) {
			const notifField = OO.ui.infuse( document.getElementsByClassName( 'enableNotifications' )[ 0 ] );
			// Enable placholder widget so field label isn't greyed out
			notifField.fieldWidget.setDisabled( false );
			const notifFieldLabel = notifField.getLabel();

			const notifToggle = new OO.ui.ToggleButtonWidget( {
				icon: 'bellOutline'
			} );

			const onRequestPermission = ( permission ) => {
				notifToggle.setValue( permission === 'granted' );
				if ( permission === 'granted' ) {
					notifField.setLabel( 'You will get a browser notification when your wiki is ready' );
				}
				if ( permission === 'denied' ) {
					notifField.setErrors( [ 'Permission denied' ] );
				}
			};

			const onNotifChange = ( value ) => {
				if ( !value ) {
					localStorage.setItem( 'patchdemo-notifications', '0' );
					notifField.setLabel( notifFieldLabel );
				} else {
					localStorage.setItem( 'patchdemo-notifications', '1' );
					Notification.requestPermission().then( onRequestPermission );
				}
			};

			notifToggle.on( 'change', onNotifChange );
			if ( +localStorage.getItem( 'patchdemo-notifications' ) && Notification.permission ) {
				onRequestPermission( Notification.permission );
			}

			notifField.$field.empty().append( notifToggle.$element );
		}
	}

	let $lastMatch = $( [] );
	$( window ).on( 'hashchange', () => {
		if ( location.hash.match( /^#[0-9a-f]{10}$/ ) ) {
			$lastMatch.removeClass( 'highlight' );
			$lastMatch = $( location.hash ).closest( 'tr' );
			$lastMatch.addClass( 'highlight' );
		}
	} );
	$( window ).trigger( 'hashchange' );

}() );
