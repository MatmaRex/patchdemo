<?php
$config = [
	// Warning shown below the new wiki form (allows HTML formatting)
	'newWikiWarning' => '',
	'phabricatorUrl' => 'https://phabricator.wikimedia.org',
	'gerritUrl' => 'https://gerrit.wikimedia.org',
	// Message shown below the main form (allows HTML formatting)
	'banner' => '',
	// Require that patches are V+2 before building the wiki
	'requireVerified' => true,
	// Additional paths, e.g. for npm when using nvm
	'extraPaths' => [],
	// OAuth config. When enabled only authenticated users can create
	// wikis, and can delete their own wikis.
	'oauth' => [
		'url' => null,
		'callback' => null,
		'key' => null,
		'secret' => null,
		// OAuth admins can delete any wiki
		'admins' => []
	],
	// Conduit API key for bot cross-posting to Phabricator
	'conduitApiKey' => null,
];
