<?php
require_once "includes.php";

if ( !can_admin() ) {
	echo 'Access denied.';
	if ( $useOAuth && !$user ) {
		echo oauth_signin_prompt();
	}
	return;
}

$cache = load_wikicache();
$wikis = json_decode( $cache, true );

$short_fields = [
	'ss_total_edits' => 'Edits',
	'ss_good_articles' => 'Articles',
	'ss_total_pages' => 'Pges',
	'ss_users' => 'Users',
	'ss_active_users' => 'Active users',
	'ss_images' => 'Images',
];

$mysqli = new mysqli( 'localhost', 'patchdemo', 'patchdemo' );
if ( $mysqli->connect_error ) {
	die( $mysqli->connect_error );
}

foreach ( $wikis as $wiki => $data ) {
	foreach ( $mysqli->query( "select * from patchdemo_$wiki.site_stats limit 1" ) as $row ) {
		$wikis[$wiki] += $row;
	}
	if ( $mysqli->error ) {
		die( $mysqli->error );
	}
}

uksort( $wikis, function ( $a, $b ) use ( $wikis ) {
	return ( $wikis[ $a ][ 'ss_total_edits' ] ?? -1 ) < ( $wikis[ $b ][ 'ss_total_edits' ] ?? -1 );
} );

echo '<table class="wikis"><tr><th>Wiki</th>';
foreach ( $short_fields as $field => $label ) {
	echo '<th>' . $label . '</th>';
}
foreach ( $wikis as $wiki => $data ) {
	echo '<tr>' .
		'<td data-label="Wikis"><a href="wikis/' . $wiki . '/w/index.php/Special:RecentChanges" title="' . $wiki . '">' . $wiki . '</a></td>';

	foreach ( $short_fields as $field => $label ) {
		echo '<td data-label="' . $label . '">' . ( isset( $data[$field] ) ? $data[$field] : '<em>?</em>' ) . '</td>';
	}
	echo '</tr>';
}
echo '</table>';

include "footer.html";
