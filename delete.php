<?php

require_once "includes.php";

include "header.php";

$wiki = $_GET[ 'wiki' ];
$wikiData = get_wiki_data( $wiki );

if ( !can_delete( $wikiData['creator'] ) ) {
	die( '<p>You are not allowed to delete this wiki.</p>' );
}

if ( !$wikiData['deleted'] ) {
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

		echo new OOUI\FormLayout( [
			'method' => 'POST',
			'items' => [
				new OOUI\FieldsetLayout( [
					'label' => new OOUI\HtmlSnippet(
						'<br>Are you sure you want to delete this wiki? This cannot be undone.'
					),
					'items' => array_filter( [
						new OOUI\FieldLayout(
							new OOUI\ButtonInputWidget( [
								'type' => 'submit',
								'name' => 'confirm',
								'label' => 'Delete',
								'flags' => [ 'primary', 'destructive' ]
							] ),
							[
								'label' => ' ',
								'align' => 'inline',
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
		}

		// Refresh wiki data
		$wikiData = get_wiki_data( $wiki );
	}
}

if ( $wikiData['deleted'] ) {
	echo '<p>Wiki deleted.</p>';
}
