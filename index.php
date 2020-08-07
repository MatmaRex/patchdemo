<?php
require_once "includes.php";
?>
<form action="new.php" method="POST" id="new-form">
	<label>
		<div>Start with version:</div>
		<select name="branch">
		<?php

		$gitcmd = "git --git-dir=" . __DIR__ . "/repositories/mediawiki/core/.git";
		// basically `git branch -r`, but without the silly parts
		$branches = explode( "\n", shell_exec( "$gitcmd for-each-ref refs/remotes/origin/ --format='%(refname:short)'" ) );

		$branches = array_filter( $branches, function ( $branch ) {
			return preg_match( '/^origin\/(master|wmf|REL)/', $branch );
		} );
		natcasesort( $branches );

		// Put newest branches first
		$branches = array_reverse( array_values( $branches ) );

		// Move master to the top
		array_unshift( $branches, array_pop( $branches ) );

		foreach ( $branches as $branch ) {
			echo "<option>" . htmlspecialchars( $branch ) . "</option>\n";
		}

		?>
		</select>
	</label>
	<label>
		<div>Then, apply patches:</div>
		<textarea name="patches" placeholder="Gerrit changeset number or Change-Id, one per line" rows="4" cols="50"></textarea>
	</label>
	<button type="submit">Create demo</button>
</form>
<br/>
<h3>Previously generated wikis</h3>
<?php
if ( $user ) {
	echo '<label><input type="checkbox" class="myWikis"><span> Show only my wikis</span></label>';
}
?>
<table class="wikis">
	<?php

	$dirs = array_filter( scandir( 'wikis' ), function ( $dir ) {
		return substr( $dir, 0, 1 ) !== '.';
	} );

	$usecache = false;
	$cache = get_if_file_exists( 'wikicache.json' );
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
							$data = gerrit_get_commit_info( $r, $p );
							if ( $data ) {
								$t = $t . ': ' . $data[ 'subject' ];
							}
							return '<a href="https://gerrit.wikimedia.org/r/c/' . $r . '/' . $p . '" title="' . htmlspecialchars( $t, ENT_QUOTES ) . '">' .
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

		file_put_contents( 'wikicache.json', json_encode( $wikis ) );
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
			'<td class="title">' . $title . '</td>' .
			'<td><a href="wikis/' . $wiki . '/w">' . $wiki . '</a></td>' .
			'<td class="date">' . date( 'c', $data[ 'mtime' ] ) . '</td>' .
			( $useOAuth ? '<td>' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
			( $canDelete ?
				'<td><a href="delete.php?wiki=' . $wiki . '">Delete</a></td>' :
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
