<?php

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

$user = null;

$useOAuth = !empty( $config[ 'oauth' ][ 'url' ] );

$authUrl = null;
$authErr = null;

function oauth_signin_prompt() {
	global $authUrl, $authErr;
	if ( $authErr ) {
		return "<div class='signIn'>OAuth error:<br>" . htmlentities( $authErr ) . "</div>";
	} else {
		return "<div class='signIn'><a href='$authUrl'>Sign in with OAuth</a> to create and manage wikis.</div>";
	}
}

function logout() {
	unset( $_SESSION['access_key'], $_SESSION['access_secret'] );
	unset( $_SESSION['request_key'], $_SESSION['request_secret'] );
}

if ( $useOAuth && !$is404 ) {
	$conf = new ClientConfig( $config[ 'oauth' ][ 'url' ] );
	$conf->setConsumer( new Consumer(
		$config[ 'oauth' ][ 'key' ],
		$config[ 'oauth' ][ 'secret' ]
	) );
	$client = new Client( $conf );

	if ( isset( $_GET['logout'] ) ) {
		logout();
	}

	if ( isset( $_GET[ 'oauth_verifier' ] ) && isset( $_SESSION['request_key'] ) ) {
		$requestToken = new Token( $_SESSION['request_key'], $_SESSION['request_secret'] );
		$accessToken = $client->complete( $requestToken, $_GET['oauth_verifier'] );

		$_SESSION['access_key'] = $accessToken->key;
		$_SESSION['access_secret'] = $accessToken->secret;

		unset( $_SESSION['request_key'], $_SESSION['request_secret'] );
	}

	if ( !empty( $_SESSION['access_key'] ) ) {
		$accessToken = new Token( $_SESSION['access_key'], $_SESSION['access_secret'] );
		$user = $client->identify( $accessToken );
	} else {
		$client->setCallback( $config[ 'oauth' ][ 'callback' ] );

		try {
			list( $authUrl, $token ) = $client->initiate();
		} catch ( Exception $e ) {
			// e.g. Invalid signature error
			logout();
			$token = null;
			$authErr = $e->getMessage();
		}

		if ( $token ) {
			$_SESSION['request_key'] = $token->key;
			$_SESSION['request_secret'] = $token->secret;
		}
	}
}
