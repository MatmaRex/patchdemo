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

$cmd = make_shell_command( [
	'PATCHDEMO' => __DIR__,
	'WIKI' => $wiki
], __DIR__ . '/deletewiki.sh' );

$error = shell_echo( $cmd );
if ( $error ) {
	die( "Could not delete." );
}

echo "Wiki deleted.";
