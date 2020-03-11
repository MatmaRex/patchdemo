<?php

require_once "includes.php";

ob_implicit_flush(true);

$branch = trim( $_POST['branch'] );
$patches = $_POST['patches'];

$namePath = md5( $branch . $patches . time() );

echo "Your wiki will be available at:";
echo "<br>";
echo "<a href='wikis/$namePath/w/'>$namePath</a>";
echo "<br>";
echo "You can log in as user 'Patch Demo', password 'patchdemo'.";
echo "<br>";

$patches = explode( "\n", trim( $patches ) );
$patches = array_map( 'trim', $patches );

echo "Updating repositories...";

$cmd = make_shell_command( [
	'PATCHDEMO' => __DIR__,
], __DIR__ . '/updaterepos.sh' );

echo '<pre>';
echo "$cmd\n";
system( $cmd, $error );
echo '</pre>';
if ( $error ) {
	die( "Could not update repositories." );
}

echo "Querying patch metadata...";

$patchesApplied = [];
$commands = [];

// Iterate by reference, so that we can modify the $patches array to add new entries
foreach ( $patches as &$patch ) {
	$patchSafe = preg_replace( '/^I?[^0-9a-f]$/', '', $patch );
	$url = "https://gerrit.wikimedia.org/r/changes/?q=change:$patchSafe&o=LABELS&o=CURRENT_REVISION";
	echo "<pre>$url</pre>";
	$resp = file_get_contents( $url );
	$data = json_decode( substr( $resp, 4 ), true );

	if ( count( $data ) === 0 ) {
		die( "Could not find patch $patchSafe" );
	}
	if ( count( $data ) !== 1 ) {
		die( "Ambiguous query $patchSafe" );
	}

	// get the info
	$repo = $data[0]['project'];
	$base = 'origin/' . $data[0]['branch'];
	$hash = $data[0]['current_revision'];
	$ref = $data[0]['revisions'][$hash]['ref'];

	$repos = get_repo_data();
	if ( !isset( $repos[ $repo ] ) ) {
		die( "Repository $repo not supported" );
	}
	$path = $repos[ $repo ];

	if ( ( $data[0]['labels']['Verified']['approved']['_account_id'] ?? null ) !== 75 ) {
		die( "Patch must be approved (Verified+2) by jenkins-bot" );
	}

	$patchesApplied[] = $data[0]['_number'] . ',' . $data[0]['revisions'][$hash]['_number'];
	$commands[] = [
		[
			'PATCHDEMO' => __DIR__ . '/',
			'REPO' => $path,
			'REF' => $ref,
			'BASE' => $base,
			'HASH' => $hash,
		],
		__DIR__ . '/applypatch.sh'
	];

	$relatedChanges = [];
	$relatedChanges[] = [ $data[0]['_number'], $data[0]['revisions'][$hash]['_number'] ];

	// Look at all commits in this patch's tree for cross-repo dependencies to add
	$url = "https://gerrit.wikimedia.org/r/changes/{$data[0]['id']}/revisions/$hash/related";
	echo "<pre>$url</pre>";
	$resp = file_get_contents( $url );
	$data = json_decode( substr( $resp, 4 ), true );
	// Ancestor commits only, not descendants
	$foundCurr = false;
	foreach ( $data['changes'] as $change ) {
		if ( $foundCurr ) {
			// Querying by change number is allegedly deprecated, but the /related API doesn't return the 'id'
			$relatedChanges[] = [ $change['_change_number'], $change['_revision_number'] ];
		}
		$foundCurr = $foundCurr || $change['commit']['commit'] === $hash;
	}

	foreach ( $relatedChanges as [ $id, $rev ] ) {
		$url = "https://gerrit.wikimedia.org/r/changes/$id/revisions/$rev/commit";
		echo "<pre>$url</pre>";
		$resp = file_get_contents( $url );
		$data = json_decode( substr( $resp, 4 ), true );

		preg_match_all( '/^Depends-On: (.+)$/m', $data['message'], $m );
		foreach ( $m[1] as $changeid ) {
			if ( !in_array( $changeid, $patches, true ) ) {
				// The entry we add here will be processed by the topmost foreach
				$patches[] = $changeid;
			}
		}
	}
}

$patchesAppliedText = implode( ' ', $patchesApplied );

$wikiName = "Patch Demo ($patchesAppliedText)";

$baseEnv = [
	'PATCHDEMO' => __DIR__,
	'NAME' => $namePath,
];

echo "Creating your wiki...";

$cmd = make_shell_command( $baseEnv + [
	'NAME' => $namePath,
	'BRANCH' => $branch,
	'WIKINAME' => $wikiName,
	'SERVER' => "http://" . $_SERVER['HTTP_HOST'],
	'SERVERPATH' => preg_replace( '`/[^/]*$`', '', $_SERVER['REQUEST_URI'] ),
	'COMPOSER_HOME' => __DIR__ . '/composer',
], __DIR__ . '/createwiki.sh' );

echo '<pre>';
echo "$cmd\n";
system( $cmd, $error );
echo '</pre>';
if ( $error ) {
	die( "Could not install the wiki." );
}

echo "Fetching and applying patches $patchesAppliedText...";

foreach ( $commands as $i => $command ) {
	$cmd = make_shell_command( $baseEnv + $command[0], $command[1] );
	echo '<pre>';
	echo "$cmd\n";
	system( $cmd, $error );
	echo '</pre>';
	if ( $error ) {
		die( "Could not apply patch $i." );
	}
}

echo "Deduplicating files...";

$cmd = make_shell_command( $baseEnv, __DIR__ . '/deduplicate.sh' );

echo '<pre>';
echo "$cmd\n";
system( $cmd, $error );
echo '</pre>';
if ( $error ) {
	die( "Could not deduplicate." );
}

echo "Seems good!";
echo "<br>";
echo "Your wiki is available at:";
echo "<br>";
echo "<a href='wikis/$namePath/w/'>$namePath</a>";
echo "<br>";
echo "You can log in as user 'Patch Demo', password 'patchdemo'.";
echo "<br>";
