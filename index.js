( function () {
	// TODO: Use infuse to control OOUI widgets
	var myWikis, closedWikis, branchSelect, form, submit, showClosed,
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

}() );
