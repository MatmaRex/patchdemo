<?php

$offset = $_GET['offset'];
$wiki = $_GET['wiki'];

if ( !preg_match( '/^[0-9a-f]{10,32}$/', $wiki ) ) {
	die( 'Invalid wiki name.' );
}

$file = 'logs/' . $wiki . '.html';

if ( file_exists( $file ) ) {
	echo file_get_contents(
		$file,
		false,
		null,
		$offset
	);
} else {
	echo "<script>pd.abandon( 'Log not found.' );</script>";
}
