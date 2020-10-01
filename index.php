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
				'items' => [
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
				]
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
}
?>
<p><em>✓=Merged ✗=Abandoned</em></p>
<table class="wikis">
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
				$title = '?';
				$settings = get_if_file_exists( 'wikis/' . $dir . '/w/LocalSettings.php' );
				if ( $settings ) {
					preg_match( '`wgSitename = "(.*)";`', $settings, $matches );
					$title = $matches[ 1 ];

					preg_match( '`Patch Demo \((.*)\)`', $title, $matches );
					if ( count( $matches ) ) {
						preg_match_all( '`([0-9]+),([0-9]+)`', $matches[ 1 ], $matches );
						$title = implode( '<br>', array_map( function ( $r, $p, $t ) {
							$changeData = gerrit_query( "changes/$r" );
							$status = 'UNKNOWN';
							if ( $changeData ) {
								$status = $changeData['status'];
							}
							$commitData = gerrit_query( "changes/$r/revisions/$p/commit" );
							if ( $commitData ) {
								$t = $t . ': ' . $commitData[ 'subject' ];
							}
							return '<a href="https://gerrit.wikimedia.org/r/c/' . $r . '/' . $p . '" title="' . htmlspecialchars( $t, ENT_QUOTES ) . '" class="status-' . $status . '">' .
								htmlspecialchars( $t ) .
							'</a>';
						}, $matches[ 1 ], $matches[ 2 ], $matches[ 0 ] ) );
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
					'creator' => $creator
				];
			}
		}
		uksort( $wikis, function ( $a, $b ) use ( $wikis ) {
			return $wikis[ $a ][ 'mtime' ] < $wikis[ $b ][ 'mtime' ];
		} );

		save_wikicache( $wikis );
	}

	$rows = '';
	$anyCanDelete = false;
	foreach ( $wikis as $wiki => $data ) {
		$title = $data[ 'title' ];
		$creator = $data[ 'creator' ] ?? '';
		$username = $user ? $user->username : null;
		$canDelete = can_delete( $creator );
		$anyCanDelete = $anyCanDelete || $canDelete;
		$rows .= '<tr' . ( $creator !== $username ? ' class="other"' : '' ) . '>' .
			'<td data-label="Patches" class="title">' . ( $title ?: '<em>No patches</em>' ) . '</td>' .
			'<td data-label="Link"><a href="wikis/' . $wiki . '/w">' . $wiki . '</a></td>' .
			'<td data-label="Time" class="date">' . date( 'c', $data[ 'mtime' ] ) . '</td>' .
			( $useOAuth ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
			( $canDelete ?
				'<td data-label="Actions"><a href="delete.php?wiki=' . $wiki . '">Delete</a></td>' :
				''
			) .
		'</tr>';
	}

	echo '<tr>' .
			'<th>Patches</th>' .
			'<th>Link</th>' .
			'<th>Time</th>' .
			( $useOAuth ? '<th>Creator</th>' : '' ) .
			( $anyCanDelete ? '<th>Actions</th>' : '' ) .
		'</tr>' .
		$rows;

	?>
</table>
<script src="index.js"></script>
<?php
include "footer.html";
