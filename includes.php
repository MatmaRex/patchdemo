<?php

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );
session_start();
include_once './vendor/autoload.php';

include 'config.default.php';
if ( file_exists( 'config.php' ) ) {
	include 'config.php';
	$config = array_merge( $config, $localConfig );
}

OOUI\Theme::setSingleton( new OOUI\WikimediaUITheme() );

$basePath = dirname( $_SERVER['SCRIPT_NAME'] );
if ( $basePath === '/' ) {
	$basePath = '';
}
$is404 = basename( $_SERVER['SCRIPT_NAME'] ) === '404.php';

echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Patch demo</title>
		<link rel="stylesheet" href="' . $basePath . '/index.css">
		<link rel="stylesheet" href="' . $basePath . '/node_modules/oojs-ui/dist/oojs-ui-wikimediaui.min.css">
		<script src="' . $basePath . '/node_modules/jquery/dist/jquery.min.js"></script>
		<script src="' . $basePath . '/node_modules/oojs/dist/oojs.jquery.min.js"></script>
		<script src="' . $basePath . '/node_modules/oojs-ui/dist/oojs-ui.min.js"></script>
		<script src="' . $basePath . '/node_modules/oojs-ui/dist/oojs-ui-wikimediaui.min.js"></script>
		<link rel="icon" type="image/png" sizes="32x32" href="' . $basePath . '/images/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="' . $basePath . '/images/favicon-16x16.png">
		<link rel="mask-icon" href="' . $basePath . '/images/safari-pinned-tab.svg" color="#006699">
		<link rel="shortcut icon" href="' . $basePath . '/images/favicon.ico">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<header>
			<div class="headerInner">
				<h1><a class="logo" href="' . $basePath . '/.">Patch demo</a></h1>
				<div class="sourceAndUser">';

include_once 'oauth.php';

if ( $user ) {
	echo "<div class='user'>Logged in as <b>{$user->username}</b> [<a href='?logout'>Log out</a>]</div>";
}
echo '
					<div class="source">
						<a href="https://github.com/MatmaRex/patchdemo">Source code</a>
						&bullet;
						<a href="https://github.com/MatmaRex/patchdemo/issues">Issues</a>' .
						( can_admin() ?
							' &bullet; <a href="editcounts.php">Edit counts</a>' :
							''
							) .
					'</div>
				</div>
			</div>
		</header>
		<main>';

$mysqli = new mysqli( 'localhost', 'patchdemo', 'patchdemo', 'patchdemo' );
if ( $mysqli->connect_error ) {
	die( $mysqli->connect_error );
}

function insert_wiki_data( string $wiki, string $creator, int $created, array $patches = [] ) {
	global $mysqli;
	$stmt = $mysqli->prepare( '
		INSERT INTO wikis
		(wiki, creator, created, patches)
		VALUES(?, ?, FROM_UNIXTIME(?), ?)
		ON DUPLICATE KEY UPDATE
		patches = ?
	' );
	if ( !$stmt ) {
		echo $mysqli->error;
	}
	$patches = json_encode( $patches );
	$stmt->bind_param( 'ssiss', $wiki, $creator, $created, $patches, $patches );
	$stmt->execute();
	$stmt->close();
}

function wiki_add_announced_tasks( string $wiki, array $announcedTasks ) {
	global $mysqli;
	$stmt = $mysqli->prepare( 'UPDATE wikis SET announcedTasks = ? WHERE wiki = ?' );
	$announcedTasks = json_encode( $announcedTasks );
	$stmt->bind_param( 'ss', $announcedTasks, $wiki );
	$stmt->execute();
	$stmt->close();
}

function wiki_set_time_to_create( string $wiki, int $timeToCreate ) {
	global $mysqli;
	$stmt = $mysqli->prepare( 'UPDATE wikis SET timeToCreate = ? WHERE wiki = ?' );
	$stmt->bind_param( 'is', $timeToCreate, $wiki );
	$stmt->execute();
	$stmt->close();
}

function get_wiki_data( string $wiki ) {
	global $mysqli;

	$stmt = $mysqli->prepare( '
		SELECT wiki, creator, UNIX_TIMESTAMP( created ) created, patches, announcedTasks, timeToCreate
		FROM wikis WHERE wiki = ?
	' );
	if ( !$stmt ) {
		echo $mysqli->error;
	}
	$stmt->bind_param( 's', $wiki );
	$stmt->execute();
	$res = $stmt->get_result();
	$data = $res->fetch_assoc();
	$stmt->close();

	if ( !$data ) {
		throw new Error( 'Wiki not found: ' . $wiki );
	}

	// Decode JSON
	$data['patches'] = json_decode( $data['patches'] ) ?: [];
	$data['announcedTasks'] = json_decode( $data['announcedTasks'] ) ?: [];

	// Populate patch list
	$patchList = [];
	$linkedTasks = [];
	if ( $data['patches'] ) {
		foreach ( $data['patches'] as $patch ) {
			[ $r, $p ] = explode( ',', $patch );
			$patchData = get_patch_data( $r, $p );
			$patchList[$patch] = $patchData;

			get_linked_tasks( $patchData[ 'message' ], $linkedTasks );
		}
	}
	$data['patchList'] = $patchList;

	// Populate task list
	$linkedTaskList = [];
	foreach ( $linkedTasks as $task ) {
		$linkedTaskList[$task] = get_task_data( $task );
	}
	$data['linkedTaskList'] = $linkedTaskList;

	return $data;
}

function get_patch_data( $r, $p ) {
	global $mysqli;

	$patch = $r . ',' . $p;

	$stmt = $mysqli->prepare( '
		SELECT patch, status, subject, message, UNIX_TIMESTAMP( updated ) updated
		FROM patches WHERE patch = ?' );
	$stmt->bind_param( 's', $patch );
	$stmt->execute();
	$res = $stmt->get_result();
	$data = $res->fetch_assoc();
	$stmt->close();

	// Patch status can change (if not merged), so re-fetch every 24 hours
	if (
		!$data || (
			$data['status'] !== 'MERGED' &&
			( time() - $data['updated'] > 24 * 60 * 60 )
		)
	) {
		$changeData = gerrit_query( "changes/$r" );
		$status = 'UNKNOWN';
		if ( $changeData ) {
			$status = $changeData['status'];
		}
		$subject = '';
		$message = '';
		$commitData = gerrit_query( "changes/$r/revisions/$p/commit" );
		if ( $commitData ) {
			$subject = $commitData[ 'subject' ];
			$message = $commitData[ 'message' ];
		}

		// Update cache
		$stmt = $mysqli->prepare( '
			INSERT INTO patches
			(patch, status, subject, message, updated)
			VALUES(?, ?, ?, ?, NOW())
			ON DUPLICATE KEY UPDATE
			status = ?, updated = NOW()
		' );
		$stmt->bind_param( 'sssss', $patch, $status, $subject, $message, $status );
		$stmt->execute();
		$stmt->close();

		$data = [
			'patch' => $patch,
			'status' => $status,
			'subject' => $subject,
			'message' => $message,
			'updated' => time(),
		];
	}
	$data['r'] = $r;
	$data['p'] = $p;

	return $data;
}

function get_task_data( int $task ) {
	global $config, $mysqli;

	$stmt = $mysqli->prepare( '
		SELECT task, title, status, UNIX_TIMESTAMP(updated) updated
		FROM tasks WHERE task = ?
	' );
	$stmt->bind_param( 'i', $task );
	$stmt->execute();
	$res = $stmt->get_result();
	$data = $res->fetch_assoc();
	$stmt->close();

	// Task titles & statuses can change, so re-fetch every 24 hours
	if ( !$data || ( time() - $data['updated'] > 24 * 60 * 60 ) || !$data['status'] ) {
		$title = '';
		if ( $config['conduitApiKey'] ) {
			$api = new \Phabricator\Phabricator( $config['phabricatorUrl'], $config['conduitApiKey'] );
			$maniphestData = $api->Maniphest( 'info', [
				'task_id' => $task
			] )->getResult();
			$title = $maniphestData['title'];
			$status = $maniphestData['status'];
		}

		// Update cache
		$stmt = $mysqli->prepare( '
			INSERT INTO tasks (task, title, status, updated)
			VALUES(?, ?, ?, NOW())
			ON DUPLICATE KEY UPDATE
			title = ?, status = ?, updated = NOW()
		' );
		$stmt->bind_param( 'issss', $task, $title, $status, $title, $status );
		$stmt->execute();
		$stmt->close();

		$data = [
			'task' => $task,
			'title' => $title,
			'status' => $status,
			'updated' => time(),
		];
	}
	$data['id'] = 'T' . $data['task'];

	return $data;
}

function make_shell_command( $env, $cmd ) {
	$prefix = '';
	foreach ( $env as $key => $value ) {
		$value = escapeshellarg( $value );
		$prefix .= "$key=$value ";
	}

	return "$prefix$cmd 2>&1";
}

function shell_echo( $cmd ) {
	echo '<pre>';
	echo htmlspecialchars( "$cmd\n" );
	$process = Process::fromShellCommandline( $cmd );
	$process->setTimeout( null );
	$error = $process->run( function ( $type, $buffer ) {
		echo htmlspecialchars( $buffer );
	} );
	echo '</pre>';
	return $error;
}

function shell( $cmd ) {
	$process = Process::fromShellCommandline( $cmd );
	$process->setTimeout( null );
	$error = $process->run();
	return $error ? null : $process->getOutput();
}

function delete_wiki( $wiki ) {
	global $mysqli;

	$wikiData = get_wiki_data( $wiki );

	$cmd = make_shell_command( [
		'PATCHDEMO' => __DIR__,
		'WIKI' => $wiki
	], __DIR__ . '/deletewiki.sh' );
	$error = shell_echo( $cmd );

	foreach ( $wikiData['announcedTasks'] as $task ) {
		// TODO: Deduplicate server/serverPath with variables in new.php
		$server = detectProtocol() . '://' . $_SERVER['HTTP_HOST'];
		$serverPath = preg_replace( '`/[^/]*$`', '', $_SERVER['REQUEST_URI'] );

		$creator = $wikiData['creator'];
		post_phab_comment(
			'T' . $task,
			"Test wiki on [[ $server$serverPath | Patch Demo ]] " . ( $creator ? ' by ' . $creator : '' ) . " using patch(es) linked to this task was **deleted**:\n" .
			"\n" .
			"~~[[ $server$serverPath/wikis/$wiki/w/ ]]~~"
		);
	}

	$stmt = $mysqli->prepare( 'DELETE FROM wikis WHERE wiki = ?' );
	$stmt->bind_param( 's', $wiki );
	$stmt->execute();
	$stmt->close();

	return $error;
}

$requestCache = [];

function gerrit_query( $url, $echo = false ) {
	global $config, $requestCache;
	if ( $echo ) {
		echo "<pre>$url</pre>";
	}
	if ( empty( $requestCache[$url] ) ) {
		$url = $config['gerritUrl'] . '/r/' . $url;
		$resp = file_get_contents( $url );
		$requestCache[$url] = json_decode( substr( $resp, 4 ), true );
	}
	return $requestCache[$url];
}

function get_linked_tasks( $message, &$alreadyLinkedTasks = [] ) {
	preg_match_all( '/^Bug: T([0-9]+)$/m', $message, $m );
	foreach ( $m[1] as $task ) {
		if ( !in_array( $task, $alreadyLinkedTasks, true ) ) {
			$alreadyLinkedTasks[] = $task;
		}
	}
	return $alreadyLinkedTasks;
}

function get_repo_data() {
	$data = file_get_contents( __DIR__ . '/repositories.txt' );
	$repos = [];

	foreach ( explode( "\n", trim( $data ) ) as $line ) {
		[ $repo, $path ] = explode( ' ', $line );
		$repos[ $repo ] = $path;
	}

	return $repos;
}

function get_branches( $repo ) {
	$gitcmd = "git --git-dir=" . __DIR__ . "/repositories/$repo/.git";
	// basically `git branch -r`, but without the silly parts
	$branches = explode( "\n", shell_exec( "$gitcmd for-each-ref refs/remotes/origin/ --format='%(refname:short)'" ) );
	return $branches;
}

function can_delete( $creator = null ) {
	global $config, $user;
	$username = $user ? $user->username : null;
	$admins = $config[ 'oauth' ] ? $config[ 'oauth' ][ 'admins' ] : [];
	return $config[ 'allowDelete' ] || ( $username && $username === $creator ) || can_admin();
}

function can_admin() {
	global $config, $user;
	if ( $config[ 'oauth'] ) {
		$username = $user ? $user->username : null;
		$admins = $config[ 'oauth' ] ? $config[ 'oauth' ][ 'admins' ] : [];
		return $username && in_array( $username, $admins, true );
	}
	// Unauthenticated site, anyone can admin
	return true;
}

function user_link( $username ) {
	global $config;
	$base = preg_replace( '/(.*\/index.php).*/i', '$1', $config[ 'oauth' ][ 'url' ] );
	return '<a href="' . $base . '?title=' . urlencode( 'User:' . $username ) . '" target="_blank">' . $username . '</a>';
}

function banner_html() {
	global $config;
	return $config['banner'];
}

function is_trusted_user( $email ) {
	$config = file_get_contents( 'https://raw.githubusercontent.com/wikimedia/integration-config/master/zuul/layout.yaml' );
	// Hack: Parser doesn't understand this, even using Yaml::PARSE_CUSTOM_TAGS
	$config = str_replace( '!!merge', 'merge', $config );
	$data = Yaml::parse( $config );

	$emailPatterns = $data[ 'pipelines' ][0][ 'trigger' ][ 'gerrit' ][ 0 ][ 'email' ];

	foreach ( $emailPatterns as $pattern ) {
		if ( preg_match( '/' . $pattern . '/', $email ) ) {
			return true;
		}
	}

	return false;
}

function post_phab_comment( $id, $comment ) {
	global $config;
	if ( $config['conduitApiKey'] ) {
		$api = new \Phabricator\Phabricator( $config['phabricatorUrl'], $config['conduitApiKey'] );
		$api->Maniphest( 'edit', [
			'objectIdentifier' => $id,
			'transactions' => [
				[
					'type' => 'comment',
					'value' => $comment,
				]
			]
		] );
	}
}

function get_repo_presets() {
	$presets = [];

	$presets['all'] = array_keys( get_repo_data() );

	$presets['wikimedia'] = explode( "\n", trim(
		file_get_contents( __DIR__ . '/repositories-preset-wikimedia.txt' )
	) );

	$presets['tarball'] = explode( "\n", trim(
		file_get_contents( __DIR__ . '/repositories-preset-tarball.txt' )
	) );

	// Things don't work well without the default skin
	$presets['minimal'] = [ 'mediawiki/core', 'mediawiki/skins/Vector' ];

	return $presets;
}

function detectProtocol() {
	// Copied from MediaWiki's WebRequest::detectProtocol
	if (
		( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ||
		(
			isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
			$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
		)
	) {
		return 'https';
	} else {
		return 'http';
	}
}
