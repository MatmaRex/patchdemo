<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function str_begins($haystack, $needle) {
	return 0 === substr_compare($haystack, $needle, 0, strlen($needle));
}

function make_shell_command( $env, $cmd ) {
	$prefix = '';
	foreach ($env as $key => $value) {
		$value = escapeshellarg( $value );
		$prefix .= "$key=$value ";
	}

	return "$prefix$cmd 2>&1";
}
