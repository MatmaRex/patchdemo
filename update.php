<?php

require_once "includes.php";

include "header.php";

ob_implicit_flush( true );

if ( $useOAuth && !$user ) {
	echo oauth_signin_prompt();
	die();
}

$wiki = $_GET['wiki'];
$wikiData = get_wiki_data( $wiki );

if ( !can_delete( $wikiData['creator'] ) ) {
	die( '<p>You are not allowed to update this wiki.</p>' );
}

echo '<div class="consoleLog">';

$patchesApplied = [];
$linkedTasks = [];
$commands = [];

foreach ( $wikiData['patchList'] as $patch => $patchData ) {
	$r = $patchData['r'];
	$data = gerrit_query( "changes/?q=change:$r&o=LABELS&o=CURRENT_REVISION", true );

	// get the info
	$repo = $data[0]['project'];
	$base = 'origin/' . $data[0]['branch'];
	$revision = $data[0]['current_revision'];
	$ref = $data[0]['revisions'][$revision]['ref'];
	$id = $data[0]['id'];

	$repos = get_repo_data();
	if ( !isset( $repos[ $repo ] ) ) {
		$repo = htmlentities( $repo );
		abandon( "Repository <em>$repo</em> not supported" );
	}
	$path = $repos[ $repo ];

	if (
		$config[ 'requireVerified' ] &&
		( $data[0]['labels']['Verified']['approved']['_account_id'] ?? null ) !== 75
	) {
		// The patch doesn't have V+2, check if the uploader is trusted
		$uploaderId = $data[0]['revisions'][$revision]['uploader']['_account_id'];
		$uploader = gerrit_query( 'accounts/' . $uploaderId, true );
		if ( !is_trusted_user( $uploader['email'] ) ) {
			abandon( "Patch must be approved (Verified+2) by jenkins-bot, or uploaded by a trusted user" );
		}
	}

	$patchesApplied[] = $data[0]['_number'] . ',' . $data[0]['revisions'][$revision]['_number'];

	$r = $patchData['r'];
	$pOld = (int)$patchData['p'];
	$pNew = $data[0]['revisions'][$revision]['_number'];
	if ( $pNew > $pOld ) {
		echo "<strong>Updating change $r from patchset $pOld to $pNew.</strong>";
	} else {
		echo "<strong>Change $r is already using the latest patchset ($pOld).</strong>";
		continue;
	}

	$commands[] = [
		[
			'PATCHDEMO' => __DIR__ . '/',
			'REPO' => $path,
			'REF' => $ref,
			'BASE' => $base,
			'HASH' => $revision,
		],
		__DIR__ . '/applypatch.sh'
	];

	$relatedChanges = [];
	$relatedChanges[] = [ $data[0]['_number'], $data[0]['revisions'][$revision]['_number'] ];

	// Look at all commits in this patch's tree for cross-repo dependencies to add
	$data = gerrit_query( "changes/$id/revisions/$revision/related", true );
	// Ancestor commits only, not descendants
	$foundCurr = false;
	foreach ( $data['changes'] as $change ) {
		if ( $foundCurr ) {
			// Querying by change number is allegedly deprecated, but the /related API doesn't return the 'id'
			$relatedChanges[] = [ $change['_change_number'], $change['_revision_number'] ];
		}
		$foundCurr = $foundCurr || $change['commit']['commit'] === $revision;
	}

	foreach ( $relatedChanges as [ $c, $r ] ) {
		$data = gerrit_query( "changes/$c/revisions/$r/commit", true );

		preg_match_all( '/^Depends-On: (.+)$/m', $data['message'], $m );
		foreach ( $m[1] as $changeid ) {
			if ( !in_array( $changeid, $patches, true ) ) {
				// The entry we add here will be processed by the topmost foreach
				$patches[] = $changeid;
			}
		}
	}
}

$baseEnv = [
	'PATCHDEMO' => __DIR__,
	'NAME' => $wiki,
];

foreach ( $commands as $i => $command ) {
	$cmd = make_shell_command( $baseEnv + $command[0], $command[1] );
	$error = shell_echo( $cmd );
	if ( $error ) {
		abandon( "Could not update patch $i." );
	}
}

// Update DB record with patches applied
wiki_add_patches( $wiki, $patchesApplied );

echo "Done!";

echo '</div>';
