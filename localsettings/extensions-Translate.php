<?php

$wgGroupPermissions['translator']['translate'] = true;
// Bug 34182: needed with ConfirmEdit
$wgGroupPermissions['translator']['skipcaptcha'] = true;
$wgTranslateDocumentationLanguageCode = 'qqq';

// Add this if you want to enable access to page translation
$wgGroupPermissions['sysop']['pagetranslation'] = true;
$wgGroupPermissions['sysop']['translate-manage'] = true;
