<?php

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedXTermTheme;
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

$ansiConverter = new AnsiToHtmlConverter( new SolarizedXTermTheme() );

$basePath = dirname( $_SERVER['SCRIPT_NAME'] );
if ( $basePath === '/' ) {
	$basePath = '';
}
$is404 = basename( $_SERVER['SCRIPT_NAME'] ) === '404.php';

include_once 'oauth.php';

$mysqli = new mysqli( 'localhost', 'patchdemo', 'patchdemo', 'patchdemo' );
if ( $mysqli->connect_error ) {
	die( $mysqli->connect_error );
}

function insert_wiki_data( string $wiki, string $creator, int $created, string $branch, ?string $landingPage ) {
	global $mysqli;
	$stmt = $mysqli->prepare( '
		INSERT INTO wikis
		(wiki, creator, created, branch, landingPage)
		VALUES(?, ?, FROM_UNIXTIME(?), ?, ?)
	' );
	if ( !$stmt ) {
		echo $mysqli->error;
	}
	$stmt->bind_param( 'ssiss', $wiki, $creator, $created, $branch, $landingPage );
	$stmt->execute();
	$stmt->close();
}

function wiki_add_patches( string $wiki, array $patches ) {
	global $mysqli;
	$stmt = $mysqli->prepare( 'UPDATE wikis SET patches = ? WHERE wiki = ?' );
	$patchesJSON = json_encode_clean( $patches );
	$stmt->bind_param( 'ss', $patchesJSON, $wiki );
	$stmt->execute();
	$stmt->close();
}

/**
 * Store which repos were used to create a wiki
 *
 * @param string $wiki Wiki
 * @param array $repos Array of indexed arrays, each item contains:
 *  'preset': The preset used, and if this is 'custom' then..
 *  'repos': The full list of repos
 */
function wiki_add_repos( string $wiki, array $repos ) {
	global $mysqli;
	$stmt = $mysqli->prepare( 'UPDATE wikis SET repos = ? WHERE wiki = ?' );
	$reposJSON = json_encode_clean( $repos );
	$stmt->bind_param( 'ss', $reposJSON, $wiki );
	$stmt->execute();
	$stmt->close();
}

function wiki_add_announced_tasks( string $wiki, array $announcedTasks ) {
	global $mysqli;
	$stmt = $mysqli->prepare( 'UPDATE wikis SET announcedTasks = ? WHERE wiki = ?' );
	$announcedTasksJSON = json_encode_clean( $announcedTasks );
	$stmt->bind_param( 'ss', $announcedTasksJSON, $wiki );
	$stmt->execute();
	$stmt->close();
}

function wiki_set_ready( string $wiki, int $timeToCreate ) {
	global $mysqli;
	$stmt = $mysqli->prepare( 'UPDATE wikis SET ready = 1, timeToCreate = ? WHERE wiki = ?' );
	$stmt->bind_param( 'is', $timeToCreate, $wiki );
	$stmt->execute();
	$stmt->close();
}

function get_wiki_data( string $wiki ): array {
	global $mysqli;

	$stmt = $mysqli->prepare( '
		SELECT wiki, creator, UNIX_TIMESTAMP( created ) created, patches, branch, repos, announcedTasks, landingPage, timeToCreate, deleted, ready
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

	return get_wiki_data_from_row( $data );
}

function get_wiki_data_from_row( array $data ): array {
	// Decode JSON
	$data['patches'] = json_decode( $data['patches'] ?: '' ) ?: [];
	$data['announcedTasks'] = json_decode( $data['announcedTasks'] ?: '' ) ?: [];
	$data['repos'] = json_decode( $data['repos'] ?: '', true ) ?: [ 'preset' => 'unknown' ];

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

function get_wiki_url( string $wiki, ?string $landingPage ): string {
	return 'wikis/' . $wiki . ( $landingPage ? '/wiki/' . $landingPage : '/w' );
}

function get_wiki_link( string $wiki, ?string $landingPage, bool $ready = true ): string {
	if ( !$ready ) {
		return substr( $wiki, 0, 10 );
	}
	return (
		'<a href="' . htmlspecialchars( get_wiki_url( $wiki, $landingPage ) ) . ' " title="' . $wiki . '">' .
			substr( $wiki, 0, 10 ) .
		'</a>'
	);
}

function get_patch_data( $r, $p ): array {
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

function get_task_data( int $task ): array {
	global $config, $mysqli;

	if ( !$config['conduitApiKey'] ) {
		// No API access means no task metadata
		return [
			'id' => 'T' . $task,
			'task' => $task,
			'title' => '',
			'status' => '',
			'updated' => time(),
		];
	}

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
		$api = new \Phabricator\Phabricator( $config['phabricatorUrl'], $config['conduitApiKey'] );
		$maniphestData = $api->Maniphest( 'info', [
			'task_id' => $task
		] )->getResult();

		if ( $maniphestData ) {
			$title = $maniphestData['title'];
			$status = $maniphestData['status'];
		} else {
			// e.g. security-restricted tasks
			$title = '';
			$status = 'unknown';
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

function all_closed( array $statuses ): bool {
	foreach ( $statuses as $status ) {
		if ( $status !== 'MERGED' && $status !== 'ABANDONED' && $status !== 'DNM' ) {
			return false;
		}
	}
	return true;
}

function format_patch_list( array $patchList, ?string $branch, bool &$closed = false ): string {
	$statuses = [];
	$patches = implode( '<br>', array_map( static function ( $patchData ) use ( &$statuses, &$linkedTaskList ) {
		global $config;

		$status = $patchData['status'];
		if (
			$status === 'NEW' &&
			preg_match( '/(DNM|DO ?NOT ?MERGE)/', $patchData['subject'] )
		) {
			$status = 'DNM';
		}
		$statuses[] = $status;
		$title = $patchData['patch'] . ': ' . $patchData[ 'subject' ];

		return '<a href="' . $config['gerritUrl'] . '/r/c/' . $patchData['r'] . '/' . $patchData['p'] . '" title="' . htmlspecialchars( $title ) . '" class="status-' . $status . '">' .
			htmlspecialchars( $title ) .
		'</a>';
	}, $patchList ) );

	$closed = all_closed( $statuses );

	return ( $patches ?: '<em>No patches</em>' ) .
			( $branch && $branch !== 'master' ? '<br>Branch: ' . $branch : '' );
}

function format_linked_tasks( array $linkedTasks ): string {
	global $config;
	$taskDescs = [];
	foreach ( $linkedTasks as $task => $taskData ) {
		$taskTitle = $taskData['id'] . ( $taskData['title'] ? ': ' . htmlspecialchars( $taskData['title'] ) : '' );
		$taskDescs[] = '<a href="' . $config['phabricatorUrl'] . '/' . $taskData['id'] . '" title="' . $taskTitle . '" class="status-' . $taskData['status'] . '">' . $taskTitle . '</a>';
	}
	$linkedTasks = implode( '<br>', $taskDescs );
	return $linkedTasks ?: '<em>No tasks</em>';
}

function format_duration( int $time ): string {
	return $time > 60 ?
		floor( $time / 60 ) . "m\u{00A0}" . ( $time % 60 ) . 's' :
		$time . 's';
}

function shell_echo( string $cmd, array $env = [] ): int {
	echo '<pre>';

	$prefix = '';
	foreach ( $env as $key => $value ) {
		$value = escapeshellarg( $value );
		$prefix .= "$key=$value ";
	}
	echo htmlspecialchars( "$prefix$cmd\n" );

	$process = Process::fromShellCommandline( $cmd, null, $env );
	$process->setTimeout( null );
	$process->setPty( true );
	$error = $process->run( static function ( $type, $buffer ) {
		global $ansiConverter;
		echo $ansiConverter->convert( $buffer );
	} );
	echo '</pre>';
	return $error;
}

function shell( $cmd, array $env = [] ): ?string {
	$process = Process::fromShellCommandline( $cmd, null, $env );
	$process->setTimeout( null );
	$error = $process->run();
	return $error ? null : $process->getOutput();
}

/**
 * Delete a wiki.
 *
 * @param string $wiki Wiki name
 * @param string|null $serverUri Server path - must be passed in if calling from the CLI
 * @return string|null Error message, null if successful
 */
function delete_wiki( string $wiki, string $serverUri = null ): ?string {
	global $mysqli;

	if ( !$serverUri ) {
		$serverUri = get_server() . get_server_path();
	}

	$wikiData = get_wiki_data( $wiki );

	if ( $wikiData['deleted'] ) {
		return 'Wiki already deleted.';
	}

	$error = shell_echo( __DIR__ . '/deletewiki.sh',
		[
			'PATCHDEMO' => __DIR__,
			'WIKI' => $wiki
		]
	);
	if ( $error ) {
		return 'Could not delete wiki files or database.';
	}

	foreach ( $wikiData['announcedTasks'] as $task ) {
		$creator = $wikiData['creator'];
		post_phab_comment(
			'T' . $task,
			"Test wiki on [[ $serverUri | Patch demo ]] " . ( $creator ? ' by ' . $creator : '' ) . " using patch(es) linked to this task was **deleted**:\n" .
			"\n" .
			"~~[[ $serverUri/wikis/$wiki/w/ ]]~~"
		);
	}

	$stmt = $mysqli->prepare( '
		UPDATE wikis
		SET deleted = 1
		WHERE wiki = ?
	' );
	$stmt->bind_param( 's', $wiki );
	$stmt->execute();
	$stmt->close();

	return $mysqli->error ?: null;
}

$requestCache = [];

function gerrit_query( string $url, $echo = false ): ?array {
	global $config, $requestCache;
	if ( $echo ) {
		echo "<pre>$url</pre>";
	}
	if ( empty( $requestCache[$url] ) ) {
		$url = $config['gerritUrl'] . '/r/' . $url;
		// Suppress warning if request fails
		// phpcs:ignore
		$resp = @file_get_contents( $url );
		$requestCache[$url] = json_decode( substr( $resp, 4 ), true );
	}
	return $requestCache[$url];
}

function get_linked_tasks( string $message, array &$alreadyLinkedTasks = [] ): array {
	preg_match_all( '/^Bug: T([0-9]+)$/m', $message, $m );
	foreach ( $m[1] as $task ) {
		if ( !in_array( $task, $alreadyLinkedTasks, true ) ) {
			$alreadyLinkedTasks[] = $task;
		}
	}
	return $alreadyLinkedTasks;
}

function get_repo_data( string $pathPrefix = 'w/' ): array {
	$data = file_get_contents( __DIR__ . '/repository-lists/all.txt' );
	$repos = [];

	foreach ( explode( "\n", trim( $data ) ) as $line ) {
		[ $repo, $path ] = explode( ' ', $line );
		$repos[ $repo ] = $pathPrefix . $path;
	}

	return $repos;
}

function get_repo_label( string $repo ): string {
	return preg_replace( '`^mediawiki/(extensions/)?`', '', $repo );
}

function get_branches( string $repo ): array {
	$gitcmd = "git --git-dir=" . __DIR__ . "/repositories/$repo/.git";
	// basically `git branch -r`, but without the silly parts
	$branches = explode( "\n", shell_exec( "$gitcmd for-each-ref refs/remotes/origin/ --format='%(refname:short)'" ) ?: '' );
	return $branches;
}

function get_branches_sorted( string $repo ): array {
	$branches = get_branches( $repo );

	$branches = array_filter( $branches, static function ( $branch ) {
		return preg_match( '/^origin\/(master|wmf|REL)/', $branch );
	} );
	natcasesort( $branches );

	// Put newest branches first
	$branches = array_reverse( array_values( $branches ) );

	// Move master to the top
	array_unshift( $branches, array_pop( $branches ) );

	return $branches;
}

function can_delete( string $creator = null ): bool {
	global $user, $useOAuth;
	if ( !$useOAuth ) {
		// Unauthenticated site
		return true;
	}
	$username = $user ? $user->username : null;
	return ( $username && $username === $creator ) || can_admin();
}

function can_admin(): bool {
	global $config, $user, $useOAuth;
	if ( !$useOAuth ) {
		// Unauthenticated site
		return true;
	}
	$username = $user ? $user->username : null;
	$admins = $config[ 'oauth' ][ 'admins' ];
	return $username && in_array( $username, $admins, true );
}

function user_link( string $username ): string {
	global $config;
	$base = preg_replace( '/(.*\/index.php).*/i', '$1', $config[ 'oauth' ][ 'url' ] );
	return '<a href="' . $base . '?title=' . urlencode( 'User:' . $username ) . '" target="_blank">' . $username . '</a>';
}

function is_trusted_user( string $email ): bool {
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

function post_phab_comment( string $id, string $comment ) {
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

function get_repo_presets(): array {
	$presets = [];

	$presets['all'] = array_keys( get_repo_data() );

	$presets['wikimedia'] = Yaml::parse( file_get_contents( __DIR__ . '/repository-lists/wikimedia.yaml' ) );
	$presets['tarball'] = Yaml::parse( file_get_contents( __DIR__ . '/repository-lists/tarball.yaml' ) );
	$presets['minimal'] = Yaml::parse( file_get_contents( __DIR__ . '/repository-lists/minimal.yaml' ) );

	return $presets;
}

function get_ignored_repos(): array {
	return Yaml::parse( file_get_contents( __DIR__ . '/repository-lists/ignored.yaml' ) );
}

function get_known_pages(): array {
	$pages = [
		'Main Page'
	];
	foreach ( [ 'Alice', 'Bob', 'Patch Demo', 'Mallory' ] as $user ) {
		$pages[] = 'User:' . $user;
		$pages[] = 'User talk:' . $user;
	}
	// TODO: Suggest some special pages?
	$files = scandir( __DIR__ . '/pages' );
	foreach ( $files as $file ) {
		if ( str_ends_with( $file, '.txt' ) ) {
			$contents = file_get_contents( __DIR__ . '/pages/' . $file );
			if ( $contents ) {
				$lines = explode( "\n",
					str_replace( '_', ' ', trim( $contents ) )
				);
				$lines = array_filter( $lines, static function ( $line ) {
					return $line !== '';
				} );
				$pages = array_merge( $pages, $lines );
			}
		}
	}
	sort( $pages );

	return $pages;
}

function is_cli(): bool {
	return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
}

function detect_protocol(): string {
	if ( is_cli() ) {
		throw new Error( 'Can\'t access server variables from CLI.' );
	}
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

function get_server(): string {
	if ( is_cli() ) {
		throw new Error( 'Can\'t access server variables from CLI.' );
	}
	return detect_protocol() . '://' . $_SERVER['HTTP_HOST'];
}

function get_server_path(): string {
	if ( is_cli() ) {
		throw new Error( 'Can\'t access server variables from CLI.' );
	}
	return preg_replace( '`/[^/]*$`', '', $_SERVER['REQUEST_URI'] );
}

function get_csrf_token(): string {
	global $useOAuth;
	if ( !$useOAuth ) {
		return '';
	}
	if ( empty( $_SESSION['csrf_token'] ) ) {
		$_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
	}
	return $_SESSION['csrf_token'];
}

function check_csrf_token( string $token ): bool {
	global $useOAuth;
	if ( !$useOAuth ) {
		return true;
	}
	if ( empty( $_SESSION['csrf_token'] ) ) {
		return false;
	}
	return $_SESSION['csrf_token'] === $token;
}

function json_encode_clean( $value ) {
	return json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}
