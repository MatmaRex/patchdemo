<?php
require_once "includes.php";

if ( $useOAuth && !$user ) {
	echo oauth_signin_prompt();
} else {
	$branches = get_branches( 'mediawiki/core' );

	$branches = array_filter( $branches, function ( $branch ) {
		return preg_match( '/^origin\/(master|wmf|REL)/', $branch );
	} );
	natcasesort( $branches );

	// Put newest branches first
	$branches = array_reverse( array_values( $branches ) );

	// Move master to the top
	array_unshift( $branches, array_pop( $branches ) );

	$branchesOptions = array_map( function ( $branch ) {
		return [ 'data' => $branch ];
	}, $branches );

	$repoBranches = [];
	$repoOptions = [];
	$repoData = get_repo_data();
	ksort( $repoData );
	foreach ( $repoData as $repo => $path ) {
		if ( $repo === 'mediawiki/core' ) {
			continue;
		}
		$repoBranches[$repo] = get_branches( $repo );
		$repo = htmlspecialchars( $repo );
		$repoOptions[] = [
			'data' => $repo,
			'label' => $repo,
		];
	}
	$repoBranches = htmlspecialchars( json_encode( $repoBranches ), ENT_NOQUOTES );
	echo "<script>window.repoBranches = $repoBranches;</script>\n";

	include_once 'DetailsFieldLayout.php';

	echo new OOUI\FormLayout( [
		'infusable' => true,
		'method' => 'POST',
		'action' => 'new.php',
		'id' => 'new-form',
		'items' => [
			new OOUI\FieldsetLayout( [
				'label' => null,
				'items' => array_filter( [
					new OOUI\FieldLayout(
						new OOUI\DropdownInputWidget( [
							'name' => 'branch',
							'options' => $branchesOptions,
						] ),
						[
							'label' => 'Start with version:',
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\MultilineTextInputWidget( [
							'name' => 'patches',
							'placeholder' => 'Gerrit changeset number or Change-Id, one per line',
							'rows' => 4,
						] ),
						[
							'label' => 'Then, apply patches:',
							'align' => 'left',
						]
					),
					$config['conduitApiKey'] ?
						new OOUI\FieldLayout(
							new OOUI\CheckboxInputWidget( [
								'name' => 'announce',
								'value' => 1,
								'selected' => true
							] ),
							[
								'label' => 'Announce wiki on Phabricator:',
								'help' => 'Any tasks linked to from patches applied will get a comment announcing this wiki.',
								'helpInline' => true,
								'align' => 'left',
							]
						) :
						null,
					new DetailsFieldLayout(
						new OOUI\CheckboxMultiselectInputWidget( [
							'name' => 'repos[]',
							'options' => $repoOptions,
							'value' => array_keys( $repoData ),
						] ),
						[
							'label' => 'Choose extensions to enable (default: all):',
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\ButtonInputWidget( [
							'label' => 'Create demo',
							'type' => 'submit',
							// 'disabled' => true,
							'flags' => [ 'progressive', 'primary' ]
						] ),
						[
							'label' => ' ',
							'align' => 'left',
						]
					),
				] )
			] ),
		]
	] );

	$banner = banner_html();
	if ( $banner ) {
		echo "<p class='banner'>$banner</p>";
	}
}
?>
<br/>
<h3>Previously generated wikis</h3>
<?php
if ( $user ) {
	echo new OOUI\FieldLayout(
		new OOUI\CheckboxInputWidget( [
			'classes' => [ 'myWikis' ]
		] ),
		[
			'align' => 'inline',
			'label' => 'Show only my wikis',
		]
	);
	echo new OOUI\FieldLayout(
		new OOUI\CheckboxInputWidget( [
			'classes' => [ 'closedWikis' ]
		] ),
		[
			'align' => 'inline',
			'label' => 'Show only wikis where all patches are merged or abandoned',
		]
	);
}
?>
<p><em>✓=Merged ✗=Abandoned</em></p>
<?php

$dirs = array_filter( scandir( 'wikis' ), function ( $dir ) {
	return substr( $dir, 0, 1 ) !== '.';
} );

$usecache = false;
$cache = load_wikicache();
if ( $cache ) {
	$wikis = json_decode( $cache, true );
	$wikilist = array_keys( $wikis );
	sort( $wikilist );
	sort( $dirs );
	if ( $wikilist === $dirs ) {
		$usecache = true;
	}
}

if ( !$usecache ) {
	$wikis = [];
	foreach ( $dirs as $dir ) {
		if ( substr( $dir, 0, 1 ) !== '.' ) {
			$statuses = [];
			$title = '?';
			$linkedTasks = '';
			$settings = get_if_file_exists( 'wikis/' . $dir . '/w/LocalSettings.php' );
			if ( $settings ) {
				preg_match( '`wgSitename = "(.*)";`', $settings, $matches );
				$title = $matches[ 1 ];

				preg_match( '`Patch Demo \((.*)\)`', $title, $matches );
				if ( count( $matches ) ) {
					$linkedTaskList = [];
					preg_match_all( '`([0-9]+),([0-9]+)`', $matches[ 1 ], $matches );
					$title = implode( '<br>', array_map( function ( $r, $p, $t ) use ( &$statuses, &$linkedTaskList ) {
						$changeData = gerrit_query( "changes/$r" );
						$status = 'UNKNOWN';
						if ( $changeData ) {
							$status = $changeData['status'];
						}
						$statuses[] = $status;
						$commitData = gerrit_query( "changes/$r/revisions/$p/commit" );
						if ( $commitData ) {
							$t = $t . ': ' . $commitData[ 'subject' ];
							get_linked_tasks( $commitData[ 'message' ], $linkedTaskList );
						}
						return '<a href="https://gerrit.wikimedia.org/r/c/' . $r . '/' . $p . '" title="' . htmlspecialchars( $t, ENT_QUOTES ) . '" class="status-' . $status . '">' .
							htmlspecialchars( $t ) .
						'</a>';
					}, $matches[ 1 ], $matches[ 2 ], $matches[ 0 ] ) );
					$taskDescs = [];
					foreach ( $linkedTaskList as $task ) {
						$taskDesc = 'T' . $task;
						if ( $config['conduitApiKey'] ) {
							$api = new \Phabricator\Phabricator( 'https://phabricator.wikimedia.org', $config['conduitApiKey'] );
							$taskDesc .= ': ' . htmlspecialchars( $api->Maniphest( 'info', [
								'task_id' => $task
							] )->getResult()['title'] );
						}
						$taskDesc = '<a href="https://phabricator.wikimedia.org/T' . $task . '">' . $taskDesc . '</a>';
						$taskDescs[] = $taskDesc;
					}
					$linkedTasks = implode( '<br>', $taskDescs );
				}

			}
			$creator = get_creator( $dir );
			$created = get_created( $dir );

			if ( !$created ) {
				// Add created.txt to old wikis
				$created = file_exists( 'wikis/' . $dir . '/w/LocalSettings.php' ) ?
					filemtime( 'wikis/' . $dir . '/w/LocalSettings.php' ) :
					filemtime( 'wikis/' . $dir );
				file_put_contents( 'wikis/' . $dir . '/created.txt', $created );
			}

			$wikis[ $dir ] = [
				'mtime' => $created,
				'title' => $title,
				'linkedTasks' => $linkedTasks,
				'creator' => $creator,
				'statuses' => $statuses,
			];
		}
	}
	uksort( $wikis, function ( $a, $b ) use ( $wikis ) {
		return $wikis[ $a ][ 'mtime' ] < $wikis[ $b ][ 'mtime' ];
	} );

	save_wikicache( $wikis );
}

function all_closed( $statuses ) {
	foreach ( $statuses as $status ) {
		if ( $status !== 'MERGED' && $status !== 'ABANDONED' ) {
			return false;
		}
	}
	return true;
}

$rows = '';
$anyCanDelete = false;
$closedWikis = 0;
foreach ( $wikis as $wiki => $data ) {
	$title = $data[ 'title' ];
	$linkedTasks = $data[ 'linkedTasks' ];
	$creator = $data[ 'creator' ] ?? '';
	$username = $user ? $user->username : null;
	$canDelete = can_delete( $creator );
	$anyCanDelete = $anyCanDelete || $canDelete;
	$closed = all_closed( $data['statuses'] );

	$classes = [];
	if ( $creator !== $username ) {
		$classes[] = 'other';
	}
	if ( !$closed ) {
		$classes[] = 'open';
	}

	$rows .= '<tr class="' . implode( ' ', $classes ) . '">' .
		'<td data-label="Patches" class="patches">' . ( $title ?: '<em>No patches</em>' ) . '</td>' .
		'<td data-label="Linked tasks" class="linkedTasks">' . ( $linkedTasks ?: '<em>No tasks</em>' ) . '</td>' .
		'<td data-label="Link" class="wiki"><a href="wikis/' . $wiki . '/w">' . $wiki . '</a></td>' .
		'<td data-label="Time" class="date">' . date( 'c', $data[ 'mtime' ] ) . '</td>' .
		( $useOAuth ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
		( $canDelete ?
			'<td data-label="Actions"><a href="delete.php?wiki=' . $wiki . '">Delete</a></td>' :
			''
		) .
	'</tr>';

	if ( $username && $username === $creator && $closed ) {
		$closedWikis++;
	}
}

if ( $closedWikis ) {
	echo new OOUI\MessageWidget( [
		'type' => 'warning',
		'label' => new OOUI\HtmlSnippet(
			new OOUI\ButtonWidget( [
				'label' => 'Show',
				'classes' => [ 'showClosed' ],
			] ) .
			'You have created ' . $closedWikis . ' ' . ( $closedWikis > 1 ? 'wikis' : 'wiki' ) . ' where all the patches ' .
			'have been merged or abandoned and therefore can be deleted.'
		)
	] );
}

echo '<table class="wikis">' .
	'<tr>' .
		'<th>Patches</th>' .
		'<th>Linked tasks</th>' .
		'<th>Link</th>' .
		'<th>Time</th>' .
		( $useOAuth ? '<th>Creator</th>' : '' ) .
		( $anyCanDelete ? '<th>Actions</th>' : '' ) .
	'</tr>' .
	$rows .
'</table>';

?>
<script src="index.js"></script>
<?php
include "footer.html";
