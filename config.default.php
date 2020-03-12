<?php
$config = [
	// Allow any user to delete wikis, e.g. on a private installation
	'allowDelete' => false,
	// OAuth config. When enabled only authenticated users can create
	// wikis, and can delete their own wikis.
	'oauth' => [
		'url' => null,
		'key' => null,
		'secret' => null,
		// OAuth admins can delete any wiki
		'admins' => []
	]
];
