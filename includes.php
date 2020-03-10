<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = json_decode( file_get_contents( 'config.default.json' ), true );
$localConfig = get_if_file_exists( 'config.json' );

if ( $localConfig ) {
	$config = array_merge( $config, json_decode( $localConfig, true ) );
}

function make_shell_command( $env, $cmd ) {
	$prefix = '';
	foreach ($env as $key => $value) {
		$value = escapeshellarg( $value );
		$prefix .= "$key=$value ";
	}

	return "$prefix$cmd 2>&1";
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
