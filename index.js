( function () {
	var form = document.getElementById( 'new-form' );
	form.addEventListener( 'submit', function () {
		form.querySelector( 'button[type=submit]' ).disabled = true;
	} );
}() );
