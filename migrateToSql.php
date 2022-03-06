<?php

require_once "includes.php";

if ( !is_cli() ) {
	echo "This script must be run from the command line\n";
	exit( 1 );
}

function get_if_file_exists( $file ) {
	return file_exists( $file ) ? file_get_contents( $file ) : null;
}

function convert_wiki_to_db( string $wiki ) {
	$patches = [];
	$settings = get_if_file_exists( 'wikis/' . $wiki . '/w/LocalSettings.php' );
	if ( $settings ) {
		preg_match( '`wgSitename = "Patch Demo \((.*)\)";`', $settings, $matches );
		if ( count( $matches ) ) {
			$title = $matches[ 1 ];
			preg_match( '`(([0-9]+,[0-9]+ ?)+)`', $title, $matches );
			if ( count( $matches ) ) {
				$patches = explode( ' ', $matches[ 1 ] );
			}
		}
	}
	$creator = trim( get_if_file_exists( 'wikis/' . $wiki . '/creator.txt' ) ?? '' );
	$created = intval( trim( get_if_file_exists( 'wikis/' . $wiki . '/created.txt' ) ?? time() ) );

	insert_wiki_data( $wiki, $creator, $created, '' );
	wiki_add_patches( $wiki, $patches );
}

$wikis = scandir( 'wikis' );
foreach ( $wikis as $wiki ) {
	if ( substr( $wiki, 0, 1 ) !== '.' ) {
		convert_wiki_to_db( $wiki );
	}
}
