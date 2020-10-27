/* global OO, $ */
( function () {
	var myWikis, closedWikis, branchSelect, form, submit, showClosed,
		presetInput, reposField, reposInput, reposFieldLabel,
		$wikisTable = $( '.wikis' );

	function updateTableClasses() {
		$wikisTable.toggleClass( 'hideOthers', !!myWikis.isSelected() );
		$wikisTable.toggleClass( 'hideOpen', !!closedWikis.isSelected() );
	}

	form = document.getElementById( 'new-form' );
	if ( form ) {
		submit = OO.ui.infuse( $( '.form-submit' ) );
		form.addEventListener( 'submit', function () {
			submit.setDisabled( true );
			return false;
		} );

		myWikis = OO.ui.infuse( $( '.myWikis' ) );
		myWikis.on( 'change', updateTableClasses );

		closedWikis = OO.ui.infuse( $( '.closedWikis' ) );
		closedWikis.on( 'change', updateTableClasses );

		if ( $( '.showClosed' ).length ) {
			showClosed = OO.ui.infuse( $( '.showClosed' ) );
			showClosed.on( 'click', function () {
				myWikis.setSelected( true );
				closedWikis.setSelected( true );
				updateTableClasses();
			} );
		}

		branchSelect = OO.ui.infuse( $( '.form-branch' ) );
		branchSelect.on( 'change', function () {
			var branch, repo, validBranch;
			branch = branchSelect.value;
			for ( repo in window.repoBranches ) {
				validBranch = window.repoBranches[ repo ].indexOf( branch ) !== -1;
				reposInput.checkboxMultiselectWidget
					.findItemFromData( repo )
					.setDisabled( !validBranch || repo === 'mediawiki/core' );
			}
		} );

		presetInput = OO.ui.infuse( $( '.form-preset' ) );
		reposInput = OO.ui.infuse( $( '.form-repos' ) );
		reposField = OO.ui.infuse( $( '.form-repos-field' ) );

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
