<?php

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

echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Patch demo</title>
		<link rel="stylesheet" href="index.css">
	</head>
	<body>
		<h1>Patch demo</h1>';

include_once 'oauth.php';

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
	echo "$cmd\n";
	system( $cmd, $error );
	echo '</pre>';
	return $error;
}

function gerrit_query_echo( $url ) {
	echo "<pre>$url</pre>";
	$resp = file_get_contents( $url );
	$data = json_decode( substr( $resp, 4 ), true );
	return $data;
}

function gerrit_get_commit_info( $change, $rev ) {
	$url = 'https://gerrit.wikimedia.org/r/changes/' . $change . '/revisions/' . $rev . '/commit';
	$resp = file_get_contents( $url );
	$data = json_decode( substr( $resp, 4 ), true );
	return $data;
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

function get_if_file_exists( $file ) {
	return file_exists( $file ) ? file_get_contents( $file ) : null;
}

function get_creator( $wiki ) {
	return trim( get_if_file_exists( 'wikis/' . $wiki . '/creator.txt' ) ?? '' );
}

function can_delete( $creator = null ) {
	global $config, $user;
	$username = $user ? $user->username : null;
	$admins = $config[ 'oauth' ] ? $config[ 'oauth' ][ 'admins' ] : [];
	return $config[ 'allowDelete' ] || ( $username && $username === $creator ) ||
		( $username && in_array( $username, $admins, true ) );
}
