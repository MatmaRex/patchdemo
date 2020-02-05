<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Patch demo</title>
		<link rel="stylesheet" href="index.css">
	</head>
	<body>
		<h1>Patch demo</h1>
		<form action="new.php" method="POST">
			<label>
				<div>Start with version:</div>
				<select name="branch">
				<?php
				require_once "includes.php";

				$gitcmd = "git --git-dir=" . __DIR__ . "/repositories/mediawiki/core/.git";
				// basically `git branch -r`, but without the silly parts
				$branches = explode( "\n", shell_exec( "$gitcmd for-each-ref refs/remotes/origin/ --format='%(refname:short)'" ) );

				$branches = array_filter( $branches, function ( $branch ) {
					return preg_match( '/^origin\/(master|wmf|REL)/', $branch );
				} );
				natcasesort( $branches );

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
		<table class="wikis">
			<caption>Previously generated wikis</caption>
			<tr><th>Name</th><th>Link</th><th>Time</th></tr>
			<?php
			$dirs = array_filter( scandir( 'wikis' ), function ( $dir ) {
				return substr( $dir, 0, 1 ) !== '.';
			} );

			$usecache = false;
			$cache = @file_get_contents( 'wikicache.json' );
			if ( $cache ) {
				$wikis = json_decode( $cache, true );
				$wikilist = array_keys( $wikis );
				if ( sort( $wikilist ) === sort( $dirs ) ) {
					$usecache = true;
				}
			}

			if ( !$usecache ) {
				$wikis = [];
				foreach ( $dirs as $dir ) {
					if ( substr( $dir, 0, 1 ) !== '.' ) {
						$title = '?';
						$settings = @file_get_contents( 'wikis/' . $dir . '/w/LocalSettings.php' );
						if ( $settings ) {
							preg_match( '`wgSitename = "(.*)";`', $settings, $matches );
							$title = $matches[ 1 ];
						}
						$wikis[ $dir ] = [
							'mtime' => filemtime( 'wikis/' . $dir ),
							'title' => $title
						];
					}
				}
				uksort( $wikis, function ( $a, $b ) use ( $wikis ) {
					return $wikis[ $a ][ 'mtime' ] < $wikis[ $b ][ 'mtime' ];
				} );

				file_put_contents( 'wikicache.json', json_encode( $wikis ) );
			}

			foreach ( $wikis as $wiki => $data ) {
				preg_match( '`Patch Demo \(([0-9]*),([0-9]*)\)`', $data[ 'title' ], $matches );
				$gerritlink = null;
				if ( count( $matches ) ) {
					$gerritlink = 'https://gerrit.wikimedia.org/r/' . $matches[ 1 ];
				}
				echo '<tr>' .
					'<td>' . ( $gerritlink ? '<a href="' . $gerritlink . '">' . $data[ 'title' ] . '</a>' : $data[ 'title' ] ) . '</td>' .
					'<td><a href="wikis/' . $wiki .'/w">' . $wiki .'</a></td>' .
					'<td>' . date( 'c', $data[ 'mtime' ] ) . '</td>' .
				'</tr>';
			}
			?>
		</table>
	</body>
</html>