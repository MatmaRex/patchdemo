<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Patch demo</title>
	</head>
	<body>
		<form action="new.php" method="POST">
			<p>Start with version:</p>
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

			<p>Then, apply patches: <small>(Gerrit changeset number or Change-Id, one per line)</small></p>
			<textarea name="patches"></textarea>

			<br>
			<button type="submit">Create demo</button>
			<hr>
			<table>
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
					echo '<tr>' .
						'<td>' . $data[ 'title' ] . '</td>' .
						'<td><a href="wikis/' . $wiki .'/w">' . $wiki .'</a></td>' .
						'<td>' . date( 'c', $data[ 'mtime' ] ) . '</td>' .
					'</tr>';
				}
				?>
			</table>
		</form>
	</body>
</html>