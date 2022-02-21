<?php
header( 'HTTP/1.0 404 Not Found' );
require_once "includes.php";

include "header.php";

$redirect = false;

// Check for redirect
$uri = $_SERVER['REQUEST_URI'];
if ( preg_match( '`/wikis/([0-9a-f]{10,32})/`', $uri, $matches, PREG_OFFSET_CAPTURE ) !== false ) {
	$wiki = $matches[1][0];
	$offset = $matches[1][1];
	$wikiData = get_wiki_data( $wiki );
	// Follow up to 10 redirect steps
	$i = 0;
	while ( $wikiData['redirect'] && $i < 10 ) {
		$redirect = $wikiData['redirect'];
		$wikiData = get_wiki_data( $redirect );
		$i++;
	}
	$redirectUri =
		substr( $uri, 0, $offset ) .
		$redirect .
		substr( $uri, $offset + strlen( $wiki ) );
}

if ( $redirect ) {
	echo new \OOUI\MessageWidget( [
		'type' => 'info',
		'icon' => 'articleRedirect',
		'label' => new \OOUI\HtmlSnippet(
			'This wiki has been deleted and the following wiki was selected as a direct replacement: ' .
			'<a href="' . htmlspecialchars( $redirectUri ) . '" class="wiki">' . substr( $redirect, 0, 10 ) . '</a>'
		)
	] );
} else {
	echo new \OOUI\MessageWidget( [
		'type' => 'error',
		'label' => 'Page not found. The wiki you are looking for may have been deleted.'
	] );
}

include "footer.html";
