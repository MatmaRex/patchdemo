( function () {
	var myWikis, wikisTable, branchSelect,
		form = document.getElementById( 'new-form' );

	form.addEventListener( 'submit', function () {
		form.querySelector( 'button[type=submit]' ).disabled = true;
	} );

	if ( document.getElementsByClassName( 'myWikis' ).length ) {
		myWikis = document.getElementsByClassName( 'myWikis' )[ 0 ];
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
	branchSelect.addEventListener( 'change', function () {
		var branch, repo, validBranch;
		branch = branchSelect.value;
		for ( repo in window.repoBranches ) {
			validBranch = window.repoBranches[ repo ].indexOf( branch ) !== -1;
			document.querySelector( 'input[name="repos[' + repo + ']"]' ).disabled = !validBranch;
		}
	} );

}() );
