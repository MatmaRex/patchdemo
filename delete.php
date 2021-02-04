<?php

require_once "includes.php";

$wiki = $_GET[ 'wiki' ];
$wikiData = get_wiki_data( $wiki );

if ( !can_delete( $wikiData['creator'] ) ) {
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

echo '<div class="consoleLog">';
$error = delete_wiki( $wiki );
echo '</div>';

if ( $error ) {
	die( '<p>Error deleting wiki:<br>' . htmlentities( $error ) . '</p>' );
} else {
	echo '<p>Wiki deleted.</p>';
}
