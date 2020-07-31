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
	} else {
		$client->setCallback( $config[ 'oauth' ][ 'callback' ] );

		list( $authUrl, $token ) = $client->initiate();

		$_SESSION['request_key'] = $token->key;
		$_SESSION['request_secret'] = $token->secret;

		echo "</div></header><main>" .
			"<div class='signIn'><a href='$authUrl'>Sign in with OAuth</a> to create and manage wikis.</div>";
		include 'footer.html';
		die();
	}

}
