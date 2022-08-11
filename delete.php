<?php

require_once "includes.php";

include "header.php";

$wiki = $_GET[ 'wiki' ];
$wikiData = get_wiki_data( $wiki );

if ( !can_delete( $wikiData['creator'] ) ) {
	die( '<p>You are not allowed to delete this wiki.</p>' );
}

if ( !isset( $_POST['confirm' ] ) ) {
	$patches = format_patch_list( $wikiData['patchList'], $wikiData['branch'] );
	$linkedTasks = format_linked_tasks( $wikiData['linkedTaskList'] );
	$creator = $wikiData[ 'creator' ] ?? '';

	echo '<table class="wikis">' .
		'<tr class="headerRow">' .
			'<th>Wiki</th>' .
			'<th>Patches<br /><em>✓=Merged ✗=Abandoned</em></th>' .
			'<th>Linked tasks<br /><em>✓=Resolved ✗=Declined/Invalid</em></th>' .
			'<th>Time</th>' .
			( $useOAuth ? '<th>Creator</th>' : '' ) .
		'</tr>' .
		'<tr>' .
			'<td data-label="Wiki" class="wiki">' . get_wiki_link( $wiki, $wikiData['landingPage'] ) . '</td>' .
			'<td data-label="Patches" class="patches">' . $patches . '</td>' .
			'<td data-label="Linked tasks" class="linkedTasks">' . $linkedTasks . '</td>' .
			'<td data-label="Time" class="date">' . date( 'Y-m-d H:i:s', $wikiData[ 'created' ] ) . '</td>' .
			( $useOAuth ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
		'</tr>' .
	'</table><br>';

	echo new OOUI\FormLayout( [
		'infusable' => true,
		'method' => 'POST',
		'items' => [
			new OOUI\FieldsetLayout( [
				'label' => null,
				'items' => [
					new OOUI\FieldLayout(
						new OOUI\ButtonInputWidget( [
							'type' => 'submit',
							'name' => 'confirm',
							'label' => 'Delete',
							'flags' => [ 'primary', 'destructive' ]
						] ),
						[
							'label' => 'Are you sure you want to delete this wiki?',
							'help' => 'This cannot be undone.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\HiddenInputWidget( [
							'name' => 'csrf_token',
							'value' => get_csrf_token(),
						] )
					),
				]
			] )
		]
	] );

	die();
}

if ( !isset( $_POST['csrf_token'] ) || !check_csrf_token( $_POST['csrf_token'] ) ) {
	die( "Invalid session." );
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
