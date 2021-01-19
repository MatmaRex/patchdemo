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
						<a href="https://github.com/MatmaRex/patchdemo">Source code</a> &bullet;
						<a href="https://github.com/MatmaRex/patchdemo/issues">Issues</a>
					</div>
				</div>
			</div>
		</header>
		<main>';

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

function delete_wiki( $wiki ) {
	$cmd = make_shell_command( [
		'PATCHDEMO' => __DIR__,
		'WIKI' => $wiki
	], __DIR__ . '/deletewiki.sh' );
	$error = shell_echo( $cmd );

	// Remove wiki from the cache to avoid a full rebuild
	remove_from_wikicache( $wiki );

	return $error;
}

function load_wikicache() {
	return get_if_file_exists( 'wikicache.json' );
}

function save_wikicache( $wikis ) {
	file_put_contents( 'wikicache.json', json_encode( $wikis, JSON_PRETTY_PRINT ) );
}

function remove_from_wikicache( $wiki ) {
	$cache = load_wikicache();
	if ( $cache ) {
		$wikis = json_decode( $cache, true );
		unset( $wikis[$wiki] );
		save_wikicache( $wikis );
	}
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

function get_if_file_exists( $file ) {
	return file_exists( $file ) ? file_get_contents( $file ) : null;
}

function get_creator( $wiki ) {
	return trim( get_if_file_exists( 'wikis/' . $wiki . '/creator.txt' ) ?? '' );
}

function get_created( $wiki ) {
	return trim( get_if_file_exists( 'wikis/' . $wiki . '/created.txt' ) ?? false );
}

function can_delete( $creator = null ) {
	global $config, $user;
	$username = $user ? $user->username : null;
	$admins = $config[ 'oauth' ] ? $config[ 'oauth' ][ 'admins' ] : [];
	return $config[ 'allowDelete' ] || ( $username && $username === $creator ) ||
		( $username && in_array( $username, $admins, true ) );
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
