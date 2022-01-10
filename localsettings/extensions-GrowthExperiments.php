<?php

$wgGERestbaseUrl = "https://$wgLanguageCode.wikipedia.org/api/rest_v1";
$wgGENewcomerTasksRemoteApiUrl = "https://$wgLanguageCode.wikipedia.org/w/api.php";
$wgGENewcomerTasksTopicType = 'ores';
$wgWelcomeSurveyExperimentalGroups['exp2_target_specialpage']['range'] = '0-9';
$wgGEHomepageMentorsList = 'Project:GrowthExperiments_mentors';
$wgGEHelpPanelHelpDeskTitle = 'Project:GrowthExperiments_help_desk';

$growthExperimentsLocalSettings = "$wgExtensionDirectory/GrowthExperiments/tests/selenium/fixtures/GrowthExperiments.LocalSettings.php";
if ( file_exists( $growthExperimentsLocalSettings ) ) {
	require_once $growthExperimentsLocalSettings;
}
