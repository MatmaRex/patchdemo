<?php
header( 'HTTP/1.0 404 Not Found' );
require_once "includes.php";

echo new \OOUI\MessageWidget( [
	'type' => 'error',
	'label' => 'Page not found. The wiki you are looking for may have been deleted.'
] );

include "footer.html";
