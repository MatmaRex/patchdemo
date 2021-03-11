<?php

OOUI\Theme::setSingleton( new OOUI\WikimediaUITheme() );

echo '<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Patch demo</title>
		<link rel="stylesheet" href="' . $basePath . '/css/common.css">
		<link rel="stylesheet" href="' . $basePath . '/node_modules/oojs-ui/dist/oojs-ui-wikimediaui.min.css">
		<script src="' . $basePath . '/node_modules/jquery/dist/jquery.min.js"></script>
		<script src="' . $basePath . '/node_modules/oojs/dist/oojs.jquery.min.js"></script>
		<script src="' . $basePath . '/node_modules/oojs-ui/dist/oojs-ui.min.js"></script>
		<script src="' . $basePath . '/node_modules/oojs-ui/dist/oojs-ui-wikimediaui.min.js"></script>
		<link rel="icon" type="image/png" sizes="32x32" href="' . $basePath . '/images/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="' . $basePath . '/images/favicon-16x16.png">
		<link rel="mask-icon" href="' . $basePath . '/images/safari-pinned-tab.svg" color="#006699">
		<link rel="shortcut icon" href="' . $basePath . '/images/favicon.ico">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<header>
			<div class="headerInner">
				<h1><a class="logo" href="' . $basePath . '/.">Patch demo</a></h1>
				<div class="sourceAndUser">';
if ( $user ) {
	echo "<div class='user'>Logged in as <b>{$user->username}</b> [<a href='?logout'>Log out</a>]</div>";
}
echo '
					<div class="source">
						<a href="https://github.com/MatmaRex/patchdemo">Source code</a>
						&bullet;
						<a href="https://github.com/MatmaRex/patchdemo/issues">Issues</a>' .
						( can_admin() ?
							' &bullet; <a href="editcounts.php">Edit counts</a>' :
							''
							) .
					'</div>
				</div>
			</div>
		</header>
		<main>';
