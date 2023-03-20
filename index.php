<?php
require_once "includes.php";

include "header.php";

$canCreate = !$useOAuth || $user;
if ( !$canCreate ) {
	echo oauth_signin_prompt();
} else {
	$branches = get_branches_sorted( 'mediawiki/core' );

	$branchOptions = array_map( static function ( $branch ) {
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
			'label' => preg_replace( '`^mediawiki/(extensions/)?`', '', $repo ),
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

	include_once 'ComboBoxInputWidget.php';
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
					// Branch select is currently broken (#527)
					// new OOUI\FieldLayout(
					// 	new OOUI\DropdownInputWidget( [
					// 		'classes' => [ 'form-branch' ],
					// 		'name' => 'branch',
					// 		'options' => $branchOptions,
					// 		'value' => !empty( $_GET['branch' ] ) ? 'origin/' . $_GET['branch' ] : null
					// 	] ),
					// 	[
					// 		'label' => 'Start with version:',
					// 		'align' => 'left',
					// 	]
					// ),
					new OOUI\FieldLayout(
						new PatchSelectWidget( [
							'classes' => [ 'form-patches' ],
							'name' => 'patches',
							'rows' => 2,
							'placeholder' => "e.g. 456123",
							'value' => !empty( $_GET['patches'] ) ? str_replace( ',', "\n", $_GET['patches'] ) : null
						] ),
						[
							'classes' => [ 'form-patches-layout' ],
							'infusable' => true,
							'label' => 'Apply patches:',
							'help' => 'Gerrit changeset number or Change-Id, one per line',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					$config['conduitApiKey'] ?
						new OOUI\FieldLayout(
							new OOUI\CheckboxInputWidget( [
								'classes' => [ 'form-announce' ],
								'name' => 'announce',
								'value' => 1,
								'selected' => true
							] ),
							[
								'classes' => [ 'form-announce-layout' ],
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
									'label' => new OOUI\HtmlSnippet( '<abbr title="Only MediaWiki and default skin with anti-spam configuration">Minimal</abbr>' ),
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
							'align' => 'inline',
							'classes' => [ 'form-repos-field' ],
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'classes' => [ 'form-instantCommons' ],
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
						new OOUI\DropdownInputWidget( [
							'classes' => [ 'form-instantCommonsMethod' ],
							'name' => 'instantCommonsMethod',
							'options' => [
								[ 'data' => 'quick', 'label' => 'QuickInstantCommons' ],
								[ 'data' => 'full', 'label' => 'InstantCommons' ],
							]
						] ),
						[
							'label' => 'Method for loading images from Commons',
							'help' => new OOUI\HtmlSnippet(
								'<a href="https://www.mediawiki.org/wiki/Extension:QuickInstantCommons">QuickInstantCommons</a> is much faster than using full <a href="https://www.mediawiki.org/wiki/InstantCommons">InstantCommons</a> but may lack some advanced image viewing features.'
							),
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new ComboBoxInputWidget( [
							'placeholder' => 'Main Page',
							'classes' => [ 'form-landingPage' ],
							'name' => 'landingPage',
							'options' => array_map( static function ( string $page ) {
								return [ 'data' => $page ];
							}, get_known_pages() ),
							'menu' => [
								'filterFromInput' => true
							],
							'value' => !empty( $_GET['landingPage' ] ) ? $_GET['landingPage' ] : null
						] ),
						[
							'label' => 'Landing page:',
							'help' => 'The page linked to from this page, and any announcement posts.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\TextInputWidget( [
							'name' => 'language',
							'value' => 'en',
							'classes' => [ 'form-language' ]
						] ),
						[
							'label' => 'Language code',
							'help' => 'Will be used as the content language and default interface language.',
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
							'label' => 'Proxy articles from wikipedia.org',
							'help' => 'Any articles not local to the wiki will be pulled from Wikipedia, using the language code above.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'tempuser',
							'value' => 1,
							'selected' => false
						] ),
						[
							'label' => "Enable temporary user account creation (IP\u{00A0}Masking)",
							'help' => 'Anonymous editors will have a temporary user account created for them.',
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
							'classes' => [ 'createField' ],
							'warnings' => $config[ 'newWikiWarning' ] ? [ new OOUI\HtmlSnippet( $config[ 'newWikiWarning' ] ) ] : [],
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
}
?>
<br/>
<h3>Previously generated wikis</h3>
<?php
if ( $user ) {
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
$username = $user ? $user->username : null;

$stmt = $mysqli->prepare( '
	SELECT wiki, creator, UNIX_TIMESTAMP( created ) created, patches, branch, announcedTasks, landingPage, timeToCreate, deleted
	FROM wikis
	WHERE !deleted
	ORDER BY IF( creator = ?, 1, 0 ) DESC, created DESC
' );
if ( !$stmt ) {
	die( $mysqli->error );
}
$stmt->bind_param( 's', $username );
$stmt->execute();
$results = $stmt->get_result();
if ( !$results ) {
	die( $mysqli->error );
}
$shownMyWikis = false;
$shownOtherWikis = false;
while ( $data = $results->fetch_assoc() ) {
	$wikiData = get_wiki_data_from_row( $data );
	$wiki = $data['wiki'];

	$wikiPatches[$wiki] = $wikiData['patches'];

	$closed = false;
	$patches = format_patch_list( $wikiData['patchList'], $wikiData['branch'], $closed );
	$linkedTasks = format_linked_tasks( $wikiData['linkedTaskList'] );

	$creator = $wikiData[ 'creator' ] ?? '';
	$canDelete = can_delete( $creator );
	$anyCanDelete = $anyCanDelete || $canDelete;

	if ( !$shownMyWikis && $creator === $username ) {
		$rows .= '<tr class="wikiSection"><th colspan="99">My wikis</th></tr>';
		$shownMyWikis = true;
	}
	if ( $shownMyWikis && !$shownOtherWikis && $creator !== $username ) {
		$rows .= '<tr class="wikiSection"><th colspan="99">Other wikis</th></tr>';
		$shownOtherWikis = true;
	}

	$classes = [];
	if ( $creator !== $username ) {
		$classes[] = 'other';
	}
	if ( !$closed ) {
		$classes[] = 'open';
	}

	$actions = [];
	if ( $canDelete ) {
		$actions[] = '<a href="delete.php?wiki=' . $wiki . '">Delete</a>';
	}
	if ( $canCreate ) {
		$patchList = array_map( static function ( $data ) {
			return htmlspecialchars( $data['r'] );
		}, $wikiData['patchList'] );

		if ( count( $patchList ) || $wikiData['branch'] !== 'master' ) {
			$actions[] = '<a class="copyWiki" href="?' .
				http_build_query( [
					'patches' => implode( ',', $patchList ),
					'branch' => $wikiData['branch'],
					'landingPage' => $wikiData['landingPage'],
				], '', '&amp;' ) .
				'">Copy</a>';
		}
	}

	$rows .= '<tr class="' . implode( ' ', $classes ) . '">' .
		'<td data-label="Wiki" class="wiki">' .
			'<span class="wikiAnchor" id="' . substr( $wiki, 0, 10 ) . '"></span>' .
			get_wiki_link( $wiki, $wikiData['landingPage'] ) .
		'</td>' .
		'<td data-label="Patches" class="patches">' . $patches . '</td>' .
		'<td data-label="Linked tasks" class="linkedTasks">' . $linkedTasks . '</td>' .
		'<td data-label="Time" class="date">' . date( 'Y-m-d H:i:s', $wikiData[ 'created' ] ) . '</td>' .
		( $useOAuth ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
		( $canAdmin ? '<td data-label="Time to create">' . ( $wikiData['timeToCreate'] ? format_duration( $wikiData['timeToCreate'] ) : '' ) . '</td>' : '' ) .
		( count( $actions ) ?
			'<td data-label="Actions">' . implode( '&nbsp;&middot;&nbsp;', $actions ) . '</td>' :
			'<!-- EMPTY ACTIONS -->'
		) .
	'</tr>';

	if ( $username && $username === $creator && $closed ) {
		$closedWikis++;
	}
}
$stmt->close();

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
	'<tr class="headerRow">' .
		'<th>Wiki</th>' .
		'<th>Patches<br /><em>✓=Merged ✗=Abandoned 🛇=<abbr title="Open patches marked with \'DNM\' or \'DO NOT MERGE\'">Do not merge</abbr></em></th>' .
		'<th>Linked tasks<br /><em>✓=Resolved ✗=Declined/Invalid</em></th>' .
		'<th>Time</th>' .
		( $useOAuth ? '<th>Creator</th>' : '' ) .
		( $canAdmin ? '<th><abbr title="Time to create">TTC</abbr></th>' : '' ) .
		( $anyCanDelete ? '<th>Actions</th>' : '' ) .
	'</tr>' .
	$rows .
'</table>';

echo '<script>
window.pd = window.pd || {};
pd.wikiPatches = ' . json_encode( $wikiPatches ) . ';
pd.config = ' . json_encode( [
	'phabricatorUrl' => $config['phabricatorUrl'],
	'gerritUrl' => $config['gerritUrl'],
] ) . ';
</script>';
?>
<script src="js/DetailsFieldLayout.js"></script>
<script src="js/PatchSelectWidget.js"></script>
<script src="js/index.js"></script>
<?php
include "footer.html";
