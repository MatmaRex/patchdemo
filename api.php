<?php

require_once "includes.php";

header( 'Content-Type: application/json' );

$patch = $_GET['patch'];

preg_match( '/^(I[0-9a-f]+|(?<r>[0-9]+)(,(?<p>[0-9]+))?)$/', $patch, $matches );
if ( isset( $matches['p'] ) ) {
	$data = gerrit_query( "changes/{$matches['r']}/revisions/{$matches['p']}/commit" );
	$data['r'] = $matches['r'];
	$data['p'] = $matches['p'];
	$data = [ $data ];
} elseif ( isset( $matches['r'] ) ) {
	$data = gerrit_query( "changes/?q=change:{$matches['r']}&o=LABELS&o=CURRENT_REVISION" );
} else {
	$data = gerrit_query( "changes/?q=change:$patch&o=LABELS&o=CURRENT_REVISION" );
}

echo json_encode( $data ?: [] );
