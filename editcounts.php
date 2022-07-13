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
	'last_edit' => [
		'label' => 'Last edit',
		'link' => 'Special:RecentChanges?days=90',
		'formatter' => static function ( $time ) {
			return ( new DateTime( $time ) )->format( 'Y-m-d H:i:s' );
		}
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

$results = $mysqli->query( 'SELECT wiki, landingPage FROM wikis WHERE !deleted ORDER BY created DESC' );
if ( !$results ) {
	die( $mysqli->error );
}
$wikis = [];
while ( $data = $results->fetch_assoc() ) {
	$wiki = $data['wiki'];
	$wikis[$wiki] = $data;
	$stats = $mysqli->query( "
			SELECT *,
			( SELECT MAX( rev_timestamp ) FROM patchdemo_$wiki.revision ) AS last_edit
			FROM patchdemo_$wiki.site_stats LIMIT 1" );
	if ( $stats ) {
		foreach ( $stats as $row ) {
			$wikis[$wiki] += $row;
		}
	}
}

uksort( $wikis, static function ( $a, $b ) use ( $wikis ) {
	return ( $wikis[ $b ][ 'ss_total_edits' ] ?? -1 ) <=> ( $wikis[ $a ][ 'ss_total_edits' ] ?? -1 );
} );

echo '<table class="wikis"><tr class="headerRow"><th>Wiki</th>';
foreach ( $short_fields as $field => $fieldMeta ) {
	echo '<th>' . $fieldMeta['label'] . '</th>';
}
foreach ( $wikis as $wiki => $data ) {
	echo '<tr>' .
		'<td data-label="Wikis" class="wiki">' . get_wiki_link( $wiki, $data['landingPage'] ) . '</td>';

	foreach ( $short_fields as $field => $fieldMeta ) {
		echo '<td data-label="' . $fieldMeta['label'] . '">' .
			'<a href="wikis/' . $wiki . '/w/index.php/' . $fieldMeta['link'] . '">' .
				( isset( $data[$field] ) ?
					( isset( $fieldMeta['formatter'] ) ? $fieldMeta['formatter']( $data[$field] ) : $data[$field] ) :
					'<em>?</em>'
				) .
			'</a>' .
		'</td>';
	}
	echo '</tr>';
}
echo '</table>';

include "footer.html";
