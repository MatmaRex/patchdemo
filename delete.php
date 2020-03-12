<?php

require_once "includes.php";

if ( !$config[ 'allowDelete' ] ) {
	die( 'You must set <code>allowDelete</code> to <code>true</code> in <code>config.php</code>' );
}

$wiki = $_GET[ 'wiki' ];

if ( !isset( $_POST['confirm' ] ) ) {
	echo '<form method="POST">' .
		'<p>Are you sure you want to delete this wiki: <a href="wikis/' . $wiki . '/w">' . $wiki . '</a>?</p>' .
		'<p>This cannot be undone.</p>' .
		'<button type="submit" name="confirm">Delete</button>' .
	'</form>';
	die();
}

ob_implicit_flush( true );

$cmd = make_shell_command( [
	'PATCHDEMO' => __DIR__,
	'WIKI' => $wiki
], __DIR__ . '/deletewiki.sh' );

echo '<pre>';
echo "$cmd\n";
system( $cmd, $error );
echo '</pre>';
if ( $error ) {
	die( "Could not delete." );
}

echo "Wiki deleted.";
