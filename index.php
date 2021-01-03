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
		return [
			'label' => preg_replace( '/^origin\//', '', $branch ),
			'data' => $branch,
		];
	}, $branches );

	$repoBranches = [];
	$repoOptions = [];
	$repoData = get_repo_data();
	ksort( $repoData );
	foreach ( $repoData as $repo => $path ) {
		$repoBranches[$repo] = get_branches( $repo );
		$repo = htmlspecialchars( $repo );
		$repoOptions[] = [
			'data' => $repo,
			'label' => $repo,
			'disabled' => ( $repo === 'mediawiki/core' ),
		];
	}
	$repoBranches = htmlspecialchars( json_encode( $repoBranches ), ENT_NOQUOTES );
	echo "<script>window.repoBranches = $repoBranches;</script>\n";

	$presets = get_repo_presets();
	$reposValid = array_keys( $repoData );
	foreach ( $presets as $name => $repos ) {
		$presets[$name] = array_values( array_intersect( $repos, $reposValid ) );
	}
	$presets = htmlspecialchars( json_encode( $presets ), ENT_NOQUOTES );
	echo "<script>window.presets = $presets;</script>\n";

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
							'classes' => [ 'form-branch' ],
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
							'classes' => [ 'form-patches' ],
							'name' => 'patches',
							'rows' => 4,
							'placeholder' => "e.g. 456123",
						] ),
						[
							'label' => 'Then, apply patches:',
							'help' => 'Gerrit changeset number or Change-Id, one per line',
							'helpInline' => true,
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
					new OOUI\FieldLayout(
						new OOUI\RadioSelectInputWidget( [
							'classes' => [ 'form-preset' ],
							'name' => 'preset',
							'options' => [
								[
									'data' => 'all',
									'label' => 'All',
								],
								[
									'data' => 'wikimedia',
									'label' => new OOUI\HtmlSnippet( '<abbr title="Most skins and extensions installed on most Wikimedia wikis, based on MediaWiki.org">Wikimedia</abbr>' ),
								],
								[
									'data' => 'tarball',
									'label' => new OOUI\HtmlSnippet( '<abbr title="Skins and extensions included in the official MediaWiki release">Tarball</abbr>' ),
								],
								[
									'data' => 'minimal',
									'label' => new OOUI\HtmlSnippet( '<abbr title="Only MediaWiki and default skin">Minimal</abbr>' ),
								],
								[
									'data' => 'custom',
									'label' => 'Custom',
								],
							],
							'value' => 'wikimedia',
						] ),
						[
							'label' => 'Choose configuration preset:',
							'align' => 'left',
						]
					),
					new DetailsFieldLayout(
						new OOUI\CheckboxMultiselectInputWidget( [
							'classes' => [ 'form-repos' ],
							'name' => 'repos[]',
							'options' => $repoOptions,
							'value' => get_repo_presets()[ 'wikimedia' ],
						] ),
						[
							'label' => 'Choose included repos:',
							'helpInline' => true,
							'align' => 'left',
							'classes' => [ 'form-repos-field' ],
						]
					),
					new OOUI\FieldLayout(
						new OOUI\ButtonInputWidget( [
							'classes' => [ 'form-submit' ],
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
			'infusable' => true,
			'classes' => [ 'myWikis' ]
		] ),
		[
			'align' => 'inline',
			'label' => 'Show only my wikis',
		]
	);
	echo new OOUI\FieldLayout(
		new OOUI\CheckboxInputWidget( [
			'infusable' => true,
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
						global $config;
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
						return '<a href="' . $config['gerritUrl'] . '/r/c/' . $r . '/' . $p . '" title="' . htmlspecialchars( $t ) . '" class="status-' . $status . '">' .
							htmlspecialchars( $t ) .
						'</a>';
					}, $matches[ 1 ], $matches[ 2 ], $matches[ 0 ] ) );
					$taskDescs = [];
					foreach ( $linkedTaskList as $task ) {
						$taskDesc = 'T' . $task;
						if ( $config['conduitApiKey'] ) {
							$api = new \Phabricator\Phabricator( $config['phabricatorUrl'], $config['conduitApiKey'] );
							$taskDesc .= ': ' . htmlspecialchars( $api->Maniphest( 'info', [
								'task_id' => $task
							] )->getResult()['title'] );
						}
						$taskDesc = '<a href="' . $config['phabricatorUrl'] . '/T' . $task . '" title="' . $taskDesc . '">' . $taskDesc . '</a>';
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
		'<td data-label="Wiki" class="wiki"><a href="wikis/' . $wiki . '/w" title="' . $wiki . '">' . $wiki . '</a></td>' .
		'<td data-label="Patches" class="patches">' . ( $title ?: '<em>No patches</em>' ) . '</td>' .
		'<td data-label="Linked tasks" class="linkedTasks">' . ( $linkedTasks ?: '<em>No tasks</em>' ) . '</td>' .
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
				'infusable' => true,
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
		'<th>Wiki</th>' .
		'<th>Patches</th>' .
		'<th>Linked tasks</th>' .
		'<th>Time</th>' .
		( $useOAuth ? '<th>Creator</th>' : '' ) .
		( $anyCanDelete ? '<th>Actions</th>' : '' ) .
	'</tr>' .
	$rows .
'</table>';

?>
<script src="DetailsFieldLayout.js"></script>
<script src="index.js"></script>
<?php
include "footer.html";
