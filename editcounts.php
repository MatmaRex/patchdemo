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

$fields = [
	'total_edits' => 'Total edits',
	'good_articles' => 'Number of articles',
	'total_pages' => 'Total pages',
	'users' => 'Number of users',
	'active_users' => 'Active users',
	'images' => 'Number of images',
];

$short_fields = [
	'total_edits' => 'Edits',
	'good_articles' => 'Articles',
	'total_pages' => 'Pges',
	'users' => 'Users',
	'active_users' => 'Active users',
	'images' => 'Images',
];

foreach ( $wikis as $wiki => $data ) {
	$cmd = 'php wikis/' . $wiki . '/w/maintenance/showSiteStats.php';
	$stats = shell( $cmd );
	if ( $stats ) {
		foreach ( $fields as $field => $label ) {
			preg_match( '/' . $label . ' *: *([0-9]+)/', $stats, $matches );
			$wikis[$wiki][$field] = $matches[1];
		}
	}
}

uksort( $wikis, function ( $a, $b ) use ( $wikis ) {
	return ( $wikis[ $a ][ 'total_edits' ] ?? -1 ) < ( $wikis[ $b ][ 'total_edits' ] ?? -1 );
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
