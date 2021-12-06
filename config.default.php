<?php
$config = [
	'phabricatorUrl' => 'https://phabricator.wikimedia.org',
	'gerritUrl' => 'https://gerrit.wikimedia.org',
	// Message shown below the main form (allows HTML formatting)
	'banner' => '',
	// Require that patches are V+2 before building the wiki
	'requireVerified' => true,
	// OAuth config. When enabled only authenticated users can create
	// wikis, and can delete their own wikis.
	'oauth' => [
		'url' => null,
		'callback' => null,
		'key' => null,
		'secret' => null,
		// OAuth admins can delete any wiki
		'admins' => [],
		// These users can override site configs. This is the same level of trust as V+2,
		// as those users can also execute arbitrary code.
		'configurers' => [],
		// Same as above, but regexes e.g. / \(WMF\)$/
		'configurersMatch' => [],
		// Instructions to request 'configurers' user status, e.g. "File a request <a href=...>here</a>."
		'configurersRequestHtml' => '',
	],
	// Conduit API key for bot cross-posting to Phabricator
	'conduitApiKey' => null,
];
