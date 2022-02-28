<?php

use Symfony\Component\Yaml\Yaml;

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

function abandon( string $errHtml ) {
	die( $errHtml );
}

echo '<p>Updating wiki <a class="wiki" href="wikis/' . $wiki . '/w" title="' . $wiki . '">' . $wiki . '</a>.</p>';

echo '<div class="consoleLog">';

ob_flush();

$baseEnv = [
	'PATCHDEMO' => __DIR__,
	'NAME' => $wiki,
];

$patchesApplied = [];
$patchesToUpdate = [];
$linkedTasks = [];
$commands = [];
$usedRepos = [];

foreach ( $wikiData['patchList'] as $patch => $patchData ) {
	$r = $patchData['r'];
	$data = gerrit_query( "changes/?q=change:$r&o=LABELS&o=CURRENT_REVISION", true );

	// get the info
	$repo = $data[0]['project'];
	$base = 'origin/' . $data[0]['branch'];
	$revision = $data[0]['current_revision'];
	$ref = $data[0]['revisions'][$revision]['ref'];
	$id = $data[0]['id'];

	$repos = get_repo_data( 'w-updating/' );
	if ( !isset( $repos[ $repo ] ) ) {
		$repo = htmlentities( $repo );
		abandon( "Repository <em>$repo</em> not supported" );
	}
	$path = $repos[ $repo ];
	$usedRepos[] = $repo;

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

	$patch = $data[0]['_number'] . ',' . $data[0]['revisions'][$revision]['_number'];
	$patchesApplied[] = $patch;

	$r = $patchData['r'];
	$pOld = (int)$patchData['p'];
	$pNew = $data[0]['revisions'][$revision]['_number'];
	if ( $pNew > $pOld ) {
		echo "<strong>Updating change $r from patchset $pOld to $pNew.</strong>";
	} else {
		echo "<strong>Change $r is already using the latest patchset ($pOld).</strong>";
		continue;
	}

	$patchesToUpdate[] = $patch;

	$commands[] = [
		[
			'REPO' => $path,
			'REF' => $ref,
			'BASE' => $base,
			'HASH' => $revision,
		],
		__DIR__ . '/new/applypatch.sh'
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
	ob_flush();
}
$usedRepos = array_unique( $usedRepos );

if ( !count( $commands ) ) {
	abandon( 'No patches to update.' );
}

$error = shell_echo( __DIR__ . '/new/unlinkbefore.sh', $baseEnv );
if ( $error ) {
	abandon( "Could not copy wiki files to update." );
}

// The working directory is now w-updating/, which is ignored by the rdfind cron job (deduplicate.sh)
// This means $wgScriptPath is incorrect, so don't try to run any MW scripts.

foreach ( $commands as $i => $command ) {
	$error = shell_echo( $command[1], $baseEnv + $command[0] );
	if ( $error ) {
		abandon( "Could not apply patch {$patchesToUpdate[$i]}" );
	}
	ob_flush();
}

$composerInstallRepos = Yaml::parse( file_get_contents( __DIR__ . '/repository-lists/composerinstall.yaml' ) );
foreach ( $usedRepos as $repo ) {
	if ( in_array( $repo, $composerInstallRepos, true ) ) {
		$error = shell_echo( __DIR__ . '/new/composerinstall.sh',
			$baseEnv + [
				// Variable used by composer itself, not our script
				'COMPOSER_HOME' => __DIR__ . '/composer',
				'REPO_TARGET' => $repos[$repo],
			]
		);
		if ( $error ) {
			abandon( "Could not fetch dependencies for <em>$repo</em>" );
		}
		ob_flush();
	}
}

$error = shell_echo( __DIR__ . '/new/unlinkafter.sh', $baseEnv );
if ( $error ) {
	abandon( "Could not overwrite wiki files after updating." );
}

// The working directory is back to w/. Scripts beyond this point should not modify the filesystem.

$mainPage = "\n\nThis wiki was updated on ~~~~~ with the following newer patches:";
foreach ( $patchesToUpdate as $patch ) {
	preg_match( '`([0-9]+),([0-9]+)`', $patch, $matches );
	list( $t, $r, $p ) = $matches;

	$data = gerrit_query( "changes/$r/revisions/$p/commit", true );
	if ( $data ) {
		$t = $t . ': ' . $data[ 'subject' ];
		get_linked_tasks( $data['message'], $linkedTasks );
	}

	$t = htmlentities( $t );

	$mainPage .= "\n:* [{$config['gerritUrl']}/r/c/$r/$p <nowiki>$t</nowiki>]";
}

$error = shell_echo( __DIR__ . '/new/postupdate.sh',
	$baseEnv + [
		'MAINPAGE' => $mainPage,
	]
);
if ( $error ) {
	abandon( "Could not update wiki content" );
}

// Update DB record with _all_ patches applied (include those which weren't updated)
wiki_add_patches( $wiki, $patchesApplied );
wiki_update_timestamp( $wiki );

echo "Done!";

echo '</div>';
