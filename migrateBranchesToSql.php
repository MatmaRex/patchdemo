<?php

require_once "includes.php";

if ( !is_cli() ) {
	echo "This script must be run from the command line\n";
	exit( 1 );
}

$results = $mysqli->query( 'SELECT wiki FROM wikis WHERE !deleted' );

while ( $data = $results->fetch_assoc() ) {
	$wiki = $data['wiki'];
	$results2 = $mysqli->query( "SELECT old_text FROM patchdemo_$wiki.text WHERE old_text LIKE 'This wiki was generated on%'" );
	if ( $data2 = $results2->fetch_assoc() ) {
		preg_match( '/Branch: (.*)/', $data2['old_text'], $matches );
		if ( count( $matches ) ) {
			$branch = $matches[1];
			$stmt = $mysqli->prepare( 'UPDATE wikis SET branch = ? WHERE wiki = ?' );
			$stmt->bind_param( 'ss', $branch, $wiki );
			$stmt->execute();
			$stmt->close();
		}
	}
}
