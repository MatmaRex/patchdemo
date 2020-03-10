<?php

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

$user = null;

$useOAuth = !empty( $config[ 'oauth' ][ 'url' ] );

if ( $useOAuth ) {
	$conf = new ClientConfig( $config[ 'oauth' ][ 'url' ] );
	$conf->setConsumer( new Consumer(
		$config[ 'oauth' ][ 'key' ],
		$config[ 'oauth' ][ 'secret' ]
	) );
	$client = new Client( $conf );

	if ( isset( $_GET['logout'] ) ) {
		unset( $_SESSION['access_key'], $_SESSION['access_secret'] );
		unset( $_SESSION['request_key'], $_SESSION['request_secret'] );
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
		echo "Logged in as <b>{$user->username}</b> [<a href='?logout'>Log out</a>]";
	} else {
		$url = ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) .
			$_SERVER['HTTP_HOST'] .
			preg_replace( '/\?.*/', '', $_SERVER['REQUEST_URI'] );
		$client->setCallback( $url );

		list( $authUrl, $token ) = $client->initiate();

		$_SESSION['request_key'] = $token->key;
		$_SESSION['request_secret'] = $token->secret;

		echo "You must <a href='$authUrl'>sign in with OAuth</a>";
		die();
	}

}
