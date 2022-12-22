<?php

$wgContactConfig = [
	'default' => [
		'RecipientUser' => 'Patch Demo',
		'SenderEmail' => null,
		'SenderName' => null,
		'RequireDetails' => false,
		'IncludeIP' => false,
		'MustBeLoggedIn' => false,
		'RLModules' => [],
		'RLStyleModules' => [],
		'AdditionalFields' => [
			'Text' => [
				'label-message' => 'emailmessage',
				'type' => 'textarea',
				'rows' => 20,
				'required' => true,
			],
		],
	],
];
