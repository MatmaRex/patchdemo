<?php

use Symfony\Component\Process\Process;

require_once "includes.php";

include "header.php";

if ( $useOAuth && !$user ) {
	echo oauth_signin_prompt();
	die();
}

if ( isset( $_GET['wiki'] ) ) {
	$wiki = $_GET['wiki'];
	if ( !preg_match( '/^[0-9a-f]{10,32}$/', $wiki ) ) {
		die( 'Invalid wiki name.' );
	}
} else {
	if ( !isset( $_POST['csrf_token'] ) || !check_csrf_token( $_POST['csrf_token'] ) ) {
		die( "Invalid session." );
	}

	$wiki = substr( md5( $_POST['branch'] . $_POST['patches'] . time() ), 0, 10 );
	$creator = $user ? $user->username : '';
	$branchDesc = preg_replace( '/^origin\//', '', $_POST['branch'] );

	// Start creatig the wiki
	$env = [
		'wiki' => $wiki,
		'creator' => $creator,
		'canAdmin' => can_admin(),
		'branchDesc' => $branchDesc,

		'announce' => $_POST['announce'] ?? '',
		'branch' => $_POST['branch'],
		'instantCommons' => $_POST['instantCommons'],
		'instantCommonsMethod' => $_POST['instantCommonsMethod'],
		'language' => $_POST['language'],
		'patches' => $_POST['patches'],
		'preset' => $_POST['preset'],
		'proxy' => $_POST['proxy'] ?? '',
		'repos' => implode( '|', $_POST['repos'] ),

		'adminVerified' => $_POST['adminVerified'] ?? '',

		'server' => get_server(),
		'serverPath' => get_server_path(),
	];

	$process = Process::fromShellCommandline(
		'php new.php >> logs/' . $wiki . '.html',
		null,
		$env
	);
	$process->setTimeout( null );
	$process->start();

	// Create an entry for the wiki before we have resolved patches.
	// Will be updated later.
	insert_wiki_data( $wiki, $creator, time(), $branchDesc );

	// If we terminate this script (start.php) immediately, the process above can stop (?)
	sleep( 1 );
}

echo new OOUI\FieldsetLayout( [
	'label' => null,
	'classes' => [ 'installForm' ],
	'items' => [
		new OOUI\FieldLayout(
			new OOUI\ProgressBarWidget( [ 'progress' => 0 ] ),
			[
				'align' => 'top',
				'label' => 'Installing...',
				'classes' => [ 'installProgressField' ],
				'infusable' => true,
			]
		),
		new OOUI\FieldLayout(
			new OOUI\ButtonWidget( [
				'label' => 'Open wiki',
				'flags' => [ 'progressive', 'primary' ],
				'href' => "wikis/$wiki/w/",
				'disabled' => true,
				'classes' => [ 'openWiki' ],
				'infusable' => true,
			] ),
			[
				'align' => 'inline',
				'classes' => [ 'openWikiField' ],
				'label' => "When complete, use this button to open your wiki ($wiki)",
				'help' => new OOUI\HtmlSnippet( <<<EOT
					You can log in as the following users using the password <code>patchdemo1</code>
					<ul class="userList">
						<li><code>Patch Demo</code> <em>(admin)</em></li>
						<li><code>Alice</code></li>
						<li><code>Bob</code></li>
						<li><code>Mallory</code> <em>(blocked)</em></li>
					</ul>
				EOT ),
				'helpInline' => true,
			]
		),
	]
] );

if ( can_admin() ) {
	echo '<form method="POST" action="" id="resubmit-form"><input type="hidden" name="adminVerified" value="1">';
	foreach ( $_POST as $k => $v ) {
		if ( is_array( $v ) ) {
			foreach ( $v as $part ) {
				echo '<input type="hidden" name="' . htmlentities( $k ) . '[]" value="' . htmlentities( $part ) . '">';
			}
		} else {
			echo '<input type="hidden" name="' . htmlentities( $k ) . '" value="' . htmlentities( $v ) . '">';
		}
	}
	echo '</form>';
}

echo '<div class="consoleLog newWikiLog"></div>';

echo '<script src="' . $basePath . '/js/start.js"></script>';
echo '<script>pd.wiki = ' . json_encode( $wiki ) . ';</script>';
