( function () {
	// TODO: Use infuse to control OOUI widgets
	var myWikis, wikisTable, branchSelect, form, submit;

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

	myWikis = document.querySelector( '.myWikis > input' );
	if ( myWikis ) {
		wikisTable = document.getElementsByClassName( 'wikis' )[ 0 ];
		myWikis.addEventListener( 'change', function () {
			if ( myWikis.checked ) {
				wikisTable.classList.add( 'hideOthers' );
			} else {
				wikisTable.classList.remove( 'hideOthers' );
			}
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
