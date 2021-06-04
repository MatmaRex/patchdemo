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
		'<tr>' .
			'<th>Wiki</th>' .
			'<th>Patches<br /><em>✓=Merged ✗=Abandoned</em></th>' .
			'<th>Linked tasks<br /><em>✓=Resolved ✗=Declined/Invalid</em></th>' .
			'<th>Time</th>' .
			( $useOAuth ? '<th>Creator</th>' : '' ) .
		'</tr>' .
		'<tr>' .
			'<td data-label="Wiki" class="wiki"><a href="wikis/' . $wiki . '/w" title="' . $wiki . '">' . substr( $wiki, 0, 10 ) . '</a></td>' .
			'<td data-label="Patches" class="patches">' . $patches . '</td>' .
			'<td data-label="Linked tasks" class="linkedTasks">' . $linkedTasks . '</td>' .
			'<td data-label="Time" class="date">' . date( 'Y-m-d H:i:s', $wikiData[ 'created' ] ) . '</td>' .
			( $useOAuth ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
		'</tr>' .
	'</table>';

	$wikilist = [
		[
			'data' => '',
			'label' => 'None',
		]
	];
	$results = $mysqli->query( 'SELECT wiki, creator, UNIX_TIMESTAMP( created ) created FROM wikis WHERE !deleted ORDER BY created DESC' );
	if ( !$results ) {
		die( $mysqli->error );
	}
	while ( $data = $results->fetch_assoc() ) {
		if ( $data[ 'wiki' ] === $wiki ) {
			continue;
		}
		$wikilist[] = [
			'data' => $data[ 'wiki' ],
			'label' => substr( $data[ 'wiki' ], 0, 10 ) . ' - ' . $data[ 'creator' ] . ' (' . date( 'Y-m-d H:i:s', $data[ 'created' ] ) . ')',
		];
	}
	echo new OOUI\FormLayout( [
		'method' => 'POST',
		'items' => [
			new OOUI\FieldsetLayout( [
				'label' => new OOUI\HtmlSnippet(
					'<br>Are you sure you want to delete this wiki? This cannot be undone.'
				),
				'items' => array_filter( [
					count( $wikilist ) > 1 ?
						new OOUI\FieldLayout(
							new OOUI\DropdownInputWidget( [
								'name' => 'redirect',
								'options' => $wikilist,
							] ),
							[
								'label' => 'Leave a redirect another wiki (optional):',
								'align' => 'left',
							]
						) :
						null,
					new OOUI\FieldLayout(
						new OOUI\ButtonInputWidget( [
							'type' => 'submit',
							'name' => 'confirm',
							'label' => 'Delete',
							'flags' => [ 'primary', 'destructive' ]
						] ),
						[
							'label' => ' ',
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\HiddenInputWidget( [
							'name' => 'csrf_token',
							'value' => get_csrf_token(),
						] )
					),
				] )
			] )
		]
	] );

} else {
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

	function isValidHash( $hash ) {
		return preg_match( '/^[0-9a-f]{10,32}$/', $hash );
	}

	$redirect = $_POST['redirect'] ?? null;

	if (
		$redirect &&
		isValidHash( $redirect ) &&
		isValidHash( $wiki )
	) {
		// TODO: Avoid duplication in redirect file
		file_put_contents(
			'redirects.txt',
			$wiki . ' ' . $redirect . "\n",
			FILE_APPEND | LOCK_EX
		);
		echo ' Redirected to <a href="wikis/' . $redirect . '/w">' . $redirect . '</a>.';
	}
}
