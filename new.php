<?php

require_once "includes.php";

ob_implicit_flush( true );

$branch = trim( $_POST['branch'] );
$patches = trim( $_POST['patches'] );

$namePath = md5( $branch . $patches . time() );
$server = ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
$serverPath = preg_replace( '`/[^/]*$`', '', $_SERVER['REQUEST_URI'] );

echo "Your wiki will be available at:";
echo "<br>";
echo "<a href='wikis/$namePath/w/'>$namePath</a>";
echo "<br>";
echo "You can log in as user 'Patch Demo', password 'patchdemo1'.";
echo "<br>";

if ( $patches ) {
	$patches = array_map( 'trim', explode( "\n", $patches ) );
} else {
	$patches = [];
}

echo "Updating repositories...";

$cmd = make_shell_command( [
	'PATCHDEMO' => __DIR__,
], __DIR__ . '/updaterepos.sh' );

$error = shell_echo( $cmd );
if ( $error ) {
	die( "Could not update repositories." );
}

echo "Querying patch metadata...";

$patchesApplied = [];
$commands = [];

// Iterate by reference, so that we can modify the $patches array to add new entries
foreach ( $patches as &$patch ) {
	$patchSafe = preg_replace( '/^I?[^0-9a-f]$/', '', $patch );
	$url = "changes/?q=change:$patchSafe&o=LABELS&o=CURRENT_REVISION";
	$data = gerrit_query_echo( $url );

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

	if (
		$config[ 'requireVerified' ] &&
		( $data[0]['labels']['Verified']['approved']['_account_id'] ?? null ) !== 75
	) {
		// The patch doesn't have V+2, check if the uploader is trusted
		$uploaderId = $data[0]['revisions'][$hash]['uploader']['_account_id'];
		$uploader = gerrit_query_echo( 'accounts/' . $uploaderId );
		if ( !is_trusted_user( $uploader['email'] ) ) {
			die( "Patch must be approved (Verified+2) by jenkins-bot, or uploaded by a trusted user" );
		}
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
	$url = "changes/{$data[0]['id']}/revisions/$hash/related";
	$data = gerrit_query_echo( $url );
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
		$url = "changes/$id/revisions/$rev/commit";
		$data = gerrit_query_echo( $url );

		preg_match_all( '/^Depends-On: (.+)$/m', $data['message'], $m );
		foreach ( $m[1] as $changeid ) {
			if ( !in_array( $changeid, $patches, true ) ) {
				// The entry we add here will be processed by the topmost foreach
				$patches[] = $changeid;
			}
		}
	}
}

$branchDesc = preg_replace( '/^origin\//', '', $branch );

$wikiName = "Patch Demo (" . trim(
	// Add branch name if it's not master, or if there are no patches
	( $branchDesc !== 'master' || !$patchesApplied ? $branchDesc : '' ) . ' ' .
	// Add list of patches
	implode( ' ', $patchesApplied )
) . ")";

$mainPage = "This wiki was generated on [$server$serverPath Patch Demo] at ~~~~~.

Branch: $branchDesc

Applied patches:\n";

if ( !$patchesApplied ) {
	$mainPage .= "(none)";
}
foreach ( $patchesApplied as $patch ) {
	preg_match( '`([0-9]+),([0-9]+)`', $patch, $matches );
	list( $t, $r, $p ) = $matches;

	$data = gerrit_get_commit_info( $r, $p );
	if ( $data ) {
		$t = $t . ': ' . $data[ 'subject' ];
	}

	$mainPage .= "* [https://gerrit.wikimedia.org/r/c/$r/$p <nowiki>$t</nowiki>]\n";
}

// Choose repositories to enable
$repos = get_repo_data();

foreach ( array_keys( $repos ) as $repo ) {
	// Unchecked the checkbox
	if ( $repo !== 'mediawiki/core' && !in_array( $repo, $_POST["repos"] ) ) {
		unset( $repos[$repo] );
	}
	// This branch doesn't exist for this repo
	if ( !in_array( $branch, get_branches( $repo ) ) ) {
		unset( $repos[$repo] );
	}
}

$reposString = implode( "\n", array_map( function ( $k, $v ) {
	return "$k $v";
}, array_keys( $repos ), array_values( $repos ) ) );

$baseEnv = [
	'PATCHDEMO' => __DIR__,
	'NAME' => $namePath,
];

echo "Creating your wiki...";

$cmd = make_shell_command( $baseEnv + [
	'NAME' => $namePath,
	'BRANCH' => $branch,
	'WIKINAME' => $wikiName,
	'CREATOR' => $user ? $user->username : '',
	'MAINPAGE' => $mainPage,
	'SERVER' => $server,
	'SERVERPATH' => $serverPath,
	'COMPOSER_HOME' => __DIR__ . '/composer',
	'REPOSITORIES' => $reposString,
], __DIR__ . '/createwiki.sh' );

$error = shell_echo( $cmd );
if ( $error ) {
	die( "Could not install the wiki." );
}

echo "Fetching and applying patches...";

foreach ( $commands as $i => $command ) {
	$cmd = make_shell_command( $baseEnv + $command[0], $command[1] );
	$error = shell_echo( $cmd );
	if ( $error ) {
		die( "Could not apply patch $i." );
	}
}

echo "Deduplicating files...";

$cmd = make_shell_command( $baseEnv, __DIR__ . '/deduplicate.sh' );

$error = shell_echo( $cmd );
if ( $error ) {
	die( "Could not deduplicate." );
}

echo "Seems good!";
echo "<br>";
echo "Your wiki is available at:";
echo "<br>";
echo "<a href='wikis/$namePath/w/'>$namePath</a>";
echo "<br>";
echo "You can log in as user 'Patch Demo', password 'patchdemo1'.";
echo "<br>";
