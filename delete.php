<?php

require_once "includes.php";

$wiki = $_GET[ 'wiki' ];

$creator = get_creator( $wiki );

if ( !can_delete( $creator ) ) {
	die( '<p>You are not allowed to delete this wiki.</p>' );
}

if ( !isset( $_POST['confirm' ] ) ) {
	echo '<form method="POST">' .
		'<p>Are you sure you want to delete this wiki: <a href="wikis/' . $wiki . '/w">' . $wiki . '</a>?</p>' .
		'<p>This cannot be undone.</p>' .
		new OOUI\ButtonInputWidget( [
			'type' => 'submit',
			'name' => 'confirm',
			'label' => 'Delete',
			'flags' => [ 'primary', 'destructive' ]
		] ) .
	'</form>';
	die();
}

ob_implicit_flush( true );

$error = delete_wiki( $wiki );
if ( $error ) {
	die( "Wiki not cleanly deleted, may have not been fully setup." );
}

echo "Wiki deleted.";
