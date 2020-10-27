/* global OO, $ */
( function () {
	// TODO: Use infuse to control OOUI widgets
	var myWikis, closedWikis, branchSelect, form, submit, showClosed,
		presetInput, reposField, reposInput, reposFieldLabel,
		wikisTable = document.getElementsByClassName( 'wikis' )[ 0 ];

	function setDisabled( input, disabled ) {
		input.disabled = disabled;
		input.parentNode.classList.toggle( 'oo-ui-widget-disabled', !!disabled );
		input.parentNode.classList.toggle( 'oo-ui-widget-enabled', !disabled );
	}

	form = document.getElementById( 'new-form' );
	if ( form ) {
		submit = form.querySelector( 'button[type=submit]' );
		form.addEventListener( 'submit', function () {
			setDisabled( submit, true );
			return false;
		} );
	}

	function updateTableClasses() {
		if ( myWikis.checked ) {
			wikisTable.classList.add( 'hideOthers' );
		} else {
			wikisTable.classList.remove( 'hideOthers' );
		}
		if ( closedWikis.checked ) {
			wikisTable.classList.add( 'hideOpen' );
		} else {
			wikisTable.classList.remove( 'hideOpen' );
		}
	}

	myWikis = document.querySelector( '.myWikis > input' );
	if ( myWikis ) {
		myWikis.addEventListener( 'change', updateTableClasses );
	}

	closedWikis = document.querySelector( '.closedWikis > input' );
	if ( closedWikis ) {
		closedWikis.addEventListener( 'change', updateTableClasses );
	}

	showClosed = document.querySelector( '.showClosed' );
	if ( showClosed ) {
		showClosed.addEventListener( 'click', function ( e ) {
			myWikis.checked = true;
			closedWikis.checked = true;
			updateTableClasses();
			e.preventDefault();
		} );
	}

	branchSelect = document.querySelector( 'select[name=branch]' );
	if ( branchSelect ) {
		branchSelect.addEventListener( 'change', function () {
			var branch, repo, validBranch;
			branch = branchSelect.value;
			for ( repo in window.repoBranches ) {
				validBranch = window.repoBranches[ repo ].indexOf( branch ) !== -1;
				setDisabled(
					document.querySelector( 'input[name="repos[]"][value="' + repo + '"]' ),
					!validBranch
				);
			}
		} );
	}

	if ( form ) {
		presetInput = OO.ui.infuse( $( '#preset' ) );
		reposInput = OO.ui.infuse( $( '#repos' ) );
		reposField = OO.ui.infuse( $( '#repos-field' ) );

		reposFieldLabel = reposField.getLabel();
		reposField.setLabel( reposFieldLabel + ' (' + reposInput.getValue().length + '/' + reposInput.checkboxMultiselectWidget.items.length + ')' );

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
			var val, presetName, matchingPresetName, numSelected;

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

			numSelected = ' (' + val.length + '/' + reposInput.checkboxMultiselectWidget.items.length + ')';
			reposField.setLabel( reposFieldLabel + numSelected );
		} ) );
	}

}() );

// Hack: The comparison in this method is wrong, and it goes into an infinite loop with our event handlers
// https://gerrit.wikimedia.org/r/c/oojs/ui/+/636187
OO.ui.CheckboxMultiselectInputWidget.prototype.getValue = function () {
	var value = this.$element.find( '.oo-ui-checkboxInputWidget .oo-ui-inputWidget-input:checked' )
		.toArray().map( function ( el ) { return el.value; } );
	if ( !OO.compare( this.value, value ) ) {
		this.setValue( value );
	}
	return this.value;
};
