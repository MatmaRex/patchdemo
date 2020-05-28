( function () {
	var myWikis, wikisTable,
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

}() );
