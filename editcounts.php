<?php
require_once "includes.php";

include "header.php";

if ( !can_admin() ) {
	echo 'Access denied.';
	if ( $useOAuth && !$user ) {
		echo oauth_signin_prompt();
	}
	return;
}

$short_fields = [
	'ss_total_edits' => [
		'label' => 'Edits',
		'link' => 'Special:RecentChanges?days=90',
	],
	'ss_good_articles' => [
		'label' => 'Articles',
		'link' => 'Special:AllPages',
	],
	'ss_total_pages' => [
		'label' => 'Pages',
		// Doesn't appear to be a list of all pages anywhere
		'link' => 'Special:AllPages',
	],
	'ss_users' => [
		'label' => 'Users',
		'link' => 'Special:ListUsers',
	],
	'ss_active_users' => [
		'label' => 'Active users',
		'link' => 'Special:ActiveUsers',
	],
	'ss_images' => [
		'label' => 'Images',
		// Or Special:MediaStatistics?
		'link' => 'Special:ListFiles',
	],
];

$results = $mysqli->query( 'SELECT wiki FROM wikis WHERE !deleted ORDER BY created DESC' );
if ( !$results ) {
	die( $mysqli->error );
}
$wikis = [];
while ( $data = $results->fetch_assoc() ) {
	$wiki = $data['wiki'];
	$wikis[$wiki] = $data;
	foreach ( $mysqli->query( "SELECT * FROM patchdemo_$wiki.site_stats LIMIT 1" ) as $row ) {
		$wikis[$wiki] += $row;
	}
}

uksort( $wikis, function ( $a, $b ) use ( $wikis ) {
	return ( $wikis[ $a ][ 'ss_total_edits' ] ?? -1 ) < ( $wikis[ $b ][ 'ss_total_edits' ] ?? -1 );
} );

echo '<table class="wikis"><tr><th>Wiki</th>';
foreach ( $short_fields as $field => $fieldMeta ) {
	echo '<th>' . $fieldMeta['label'] . '</th>';
}
foreach ( $wikis as $wiki => $data ) {
	echo '<tr>' .
		'<td data-label="Wikis" class="wiki"><a href="wikis/' . $wiki . '/w/index.php" title="' . $wiki . '">' . substr( $wiki, 0, 10 ) . '</a></td>';

	foreach ( $short_fields as $field => $fieldMeta ) {
		echo '<td data-label="' . $fieldMeta['label'] . '">' .
			'<a href="wikis/' . $wiki . '/w/index.php/' . $fieldMeta['link'] . '">' .
				( isset( $data[$field] ) ? $data[$field] : '<em>?</em>' ) .
			'</a>' .
		'</td>';
	}
	echo '</tr>';
}
echo '</table>';

include "footer.html";
