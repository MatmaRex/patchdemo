<?php

$wgQuickSurveysConfig = [
	[
		'name' => 'internal example survey',
		'type' => 'internal',
		'layout' => 'single-answer',
		'question' => 'ext-quicksurveys-example-internal-survey-question',
		'answers' => [
			'ext-quicksurveys-example-internal-survey-answer-positive',
			'ext-quicksurveys-example-internal-survey-answer-neutral',
			'ext-quicksurveys-example-internal-survey-answer-negative',
		],
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
	[
		'name' => 'internal example survey with description and freeform text',
		'type' => 'internal',
		'layout' => 'single-answer',
		'question' => 'ext-quicksurveys-example-internal-survey-question',
		'answers' => [
			'ext-quicksurveys-example-internal-survey-answer-positive',
			'ext-quicksurveys-example-internal-survey-answer-neutral',
			'ext-quicksurveys-example-internal-survey-answer-negative',
		],
		'description' => 'ext-quicksurveys-example-internal-survey-description',
		'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
	[
		'name' => 'internal multiple answer example survey',
		'type' => 'internal',
		'layout' => 'multiple-answer',
		'question' => 'ext-quicksurveys-example-internal-survey-question',
		'answers' => [
			'ext-quicksurveys-example-internal-survey-answer-positive',
			'ext-quicksurveys-example-internal-survey-answer-neutral',
			'ext-quicksurveys-example-internal-survey-answer-negative',
		],
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
		'shuffleAnswersDisplay' => true,
	],
	[
		'name' => 'internal multiple answer example survey with description and freeform text',
		'type' => 'internal',
		'layout' => 'multiple-answer',
		'question' => 'ext-quicksurveys-example-internal-survey-question',
		'answers' => [
			'ext-quicksurveys-example-internal-survey-answer-positive',
			'ext-quicksurveys-example-internal-survey-answer-neutral',
			'ext-quicksurveys-example-internal-survey-answer-negative',
		],
		'description' => 'ext-quicksurveys-example-internal-survey-description',
		'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
		'shuffleAnswersDisplay' => true,
	],
	[
		'name' => 'external example survey',
		'type' => 'external',
		'question' => 'ext-quicksurveys-example-external-survey-question',
		'description' => 'ext-quicksurveys-example-external-survey-description',
		'link' => 'ext-quicksurveys-example-external-survey-link',
		'privacyPolicy' => 'ext-quicksurveys-example-external-survey-privacy-policy',
		'coverage' => 0,
		'enabled' => true,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
];
