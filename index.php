<?php
require_once "includes.php";

include "header.php";

if ( $useOAuth && !$user ) {
	echo oauth_signin_prompt();
} else {
	$branches = get_branches( 'mediawiki/core' );

	$branches = array_filter( $branches, static function ( $branch ) {
		return preg_match( '/^origin\/(master|wmf|REL)/', $branch );
	} );
	natcasesort( $branches );

	// Put newest branches first
	$branches = array_reverse( array_values( $branches ) );

	// Move master to the top
	array_unshift( $branches, array_pop( $branches ) );

	$branchesOptions = array_map( static function ( $branch ) {
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
	include_once 'PatchSelectWidget.php';

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
						new PatchSelectWidget( [
							'classes' => [ 'form-patches' ],
							'name' => 'patches',
							'rows' => 2,
							'placeholder' => "e.g. 456123",
						] ),
						[
							'classes' => [ 'form-patches-layout' ],
							'infusable' => true,
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
							'help' => new OOUI\HtmlSnippet( 'If your extension is not listed, please create a <a href="https://github.com/MatmaRex/patchdemo/issues/new">new issue</a>.' ),
							'helpInline' => true,
							'align' => 'left',
							'classes' => [ 'form-repos-field' ],
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'instantCommons',
							'value' => 1,
							'selected' => true
						] ),
						[
							'label' => 'Load images from Commons',
							'help' => 'Any images not local to the wiki will be pulled from Wikimedia Commons.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'proxy',
							'value' => 1,
							'selected' => false
						] ),
						[
							'label' => 'Proxy articles from en.wikipedia.org',
							'help' => 'Any articles not local to the wiki will be pulled from English Wikipedia.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						// Placeholder, will be replaced by a ToggleButtonWidget in JS
						new OOUI\ButtonWidget( [
							'icon' => 'bell',
							'disabled' => 'true'
						] ),
						[
							'align' => 'inline',
							'classes' => [ 'enableNotifications' ],
							'label' => 'Get a browser notification when your wikis are ready',
							'infusable' => true,
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
					new OOUI\FieldLayout(
						new OOUI\HiddenInputWidget( [
							'name' => 'csrf_token',
							'value' => get_csrf_token(),
						] )
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
<?php

$rows = '';
$anyCanDelete = false;
$closedWikis = 0;
$canAdmin = can_admin();
$wikiPatches = [];

$results = $mysqli->query( '
	SELECT wiki, creator, UNIX_TIMESTAMP( created ) created, patches, branch, announcedTasks, timeToCreate, deleted
	FROM wikis
	WHERE !deleted
	ORDER BY created DESC
' );
if ( !$results ) {
	die( $mysqli->error );
}
while ( $data = $results->fetch_assoc() ) {
	$wikiData = get_wiki_data_from_row( $data );
	$wiki = $data['wiki'];

	$wikiPatches[$wiki] = $wikiData['patches'];

	$closed = false;
	$patches = format_patch_list( $wikiData['patchList'], $wikiData['branch'], $closed );
	$linkedTasks = format_linked_tasks( $wikiData['linkedTaskList'] );

	$creator = $wikiData[ 'creator' ] ?? '';
	$username = $user ? $user->username : null;
	$canDelete = can_delete( $creator );
	$anyCanDelete = $anyCanDelete || $canDelete;

	$classes = [];
	if ( $creator !== $username ) {
		$classes[] = 'other';
	}
	if ( !$closed ) {
		$classes[] = 'open';
	}

	$rows .= '<tr class="' . implode( ' ', $classes ) . '">' .
		'<td data-label="Wiki" class="wiki">' .
			'<span class="wikiAnchor" id="' . substr( $wiki, 0, 10 ) . '"></span>' .
			'<a href="wikis/' . $wiki . '/w" title="' . $wiki . '">' . substr( $wiki, 0, 10 ) . '</a>' .
		'</td>' .
		'<td data-label="Patches" class="patches">' . $patches . '</td>' .
		'<td data-label="Linked tasks" class="linkedTasks">' . $linkedTasks . '</td>' .
		'<td data-label="Time" class="date">' . date( 'Y-m-d H:i:s', $wikiData[ 'created' ] ) . '</td>' .
		( $useOAuth ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
		( $canAdmin ? '<td data-label="Time to create">' . ( $wikiData['timeToCreate'] ? $wikiData['timeToCreate'] . 's' : '' ) . '</td>' : '' ) .
		( $canDelete ?
			'<td data-label="Actions"><a href="delete.php?wiki=' . $wiki . '">Delete</a></td>' :
			'<!-- EMPTY ACTIONS -->'
		) .
	'</tr>';

	if ( $username && $username === $creator && $closed ) {
		$closedWikis++;
	}
}

$rows = str_replace( '<!-- EMPTY ACTIONS -->', $anyCanDelete ? '<td></td>' : '', $rows );

if ( $closedWikis ) {
	echo new OOUI\MessageWidget( [
		'classes' => [ 'showClosed' ],
		'type' => 'warning',
		'label' => new OOUI\HtmlSnippet(
			new OOUI\ButtonWidget( [
				'infusable' => true,
				'label' => 'Show',
				'classes' => [ 'showClosedButton' ],
			] ) .
			'You have created ' . $closedWikis . ' ' . ( $closedWikis > 1 ? 'wikis' : 'wiki' ) . ' where all the patches ' .
			'have been merged or abandoned and therefore can be deleted.'
		)
	] );
}

echo '<table class="wikis">' .
	'<tr>' .
		'<th>Wiki</th>' .
		'<th>Patches<br /><em>✓=Merged ✗=Abandoned</em></th>' .
		'<th>Linked tasks<br /><em>✓=Resolved ✗=Declined/Invalid</em></th>' .
		'<th>Time</th>' .
		( $useOAuth ? '<th>Creator</th>' : '' ) .
		( $canAdmin ? '<th><abbr title="Time to create">TTC</abbr></th>' : '' ) .
		( $anyCanDelete ? '<th>Actions</th>' : '' ) .
	'</tr>' .
	$rows .
'</table>';

?>
<script src="js/DetailsFieldLayout.js"></script>
<script src="js/PatchSelectWidget.js"></script>
<script src="js/index.js"></script>
<?php
echo '<script> pd.wikiPatches = ' . json_encode( $wikiPatches ) . '; </script>';
include "footer.html";
