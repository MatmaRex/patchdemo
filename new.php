<?php

require_once "includes.php";

ob_implicit_flush(true);

$branch = trim( $_POST['branch'] );
$patches = $_POST['patches'];

$namePath = md5( $branch . $patches . time() );

echo "Your wiki will be available at:";
echo "<br>";
echo "<a href='/$namePath/w/'>$namePath</a>";
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

foreach ( $patches as $patch ) {
	$patchSafe = preg_replace( '/^I?[^0-9a-f]$/', '', $patch );
	$url = "https://gerrit.wikimedia.org/r/changes/?q=$patchSafe&o=LABELS&o=CURRENT_REVISION";
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
	$hash = $data[0]['current_revision'];
	$ref = $data[0]['revisions'][$hash]['ref'];


	if ( $repo === 'mediawiki/core' ) {
		$path = 'w';
	} elseif ( str_begins( $repo, 'mediawiki/extensions/' ) || str_begins( $repo, 'mediawiki/skins/' ) ) {
		$path = 'w/' . substr( $repo, 10 );
	} else {
		die( "Repository $repo not supported" );
	}

	if ( ( $data[0]['labels']['Verified']['approved']['_account_id'] ?? null ) !== 75 ) {
		die( "Patch must be approved (Verified+2) by jenkins-bot" );
	}

	$patchesApplied[] = $data[0]['_number'] . ',' . $data[0]['revisions'][$hash]['_number'];
	$commands[] = [
		[
			'PATCHDEMO' => __DIR__ . '/',
			'REPO' => $path,
			'REF' => $ref,
			'HASH' => $hash,
		],
		__DIR__ . '/applypatch.sh'
	];
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
echo "<a href='/$namePath/w/'>$namePath</a>";
echo "<br>";
echo "You can log in as user 'Patch Demo', password 'patchdemo'.";
echo "<br>";
