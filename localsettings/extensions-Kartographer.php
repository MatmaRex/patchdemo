<?php

$wgKartographerMapServer = 'https://kartotherian.wmflabs.org';
$wgKartographerStaticMapframe = true;

// Copied from https://www.mediawiki.org/wiki/Extension:JsonConfig#Supporting_Wikimedia_templates
$wgJsonConfigEnableLuaSupport = true;
$wgJsonConfigModels['Map.JsonConfig'] = 'JsonConfig\JCMapDataContent';
$wgJsonConfigs['Map.JsonConfig'] = [
	'namespace' => 486,
	'nsName' => 'Data',
	// page name must end in ".map", and contain at least one symbol
	'pattern' => '/.\.map$/',
	'license' => 'CC0-1.0',
	'isLocal' => false,
];
$wgJsonConfigInterwikiPrefix = "commons";
$wgJsonConfigs['Map.JsonConfig']['remote'] = [
	'url' => 'https://commons.wikimedia.org/w/api.php'
];
