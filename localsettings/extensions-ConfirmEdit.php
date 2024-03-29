<?php

if ( file_exists( 'extensions/ConfirmEdit/QuestyCaptcha/extension.json' ) ) {
	// Unintrusive anti-spam CAPTCHA
	wfLoadExtension( 'ConfirmEdit/QuestyCaptcha' );
	$wgCaptchaQuestions = [
		"Type 'patchdemo' here as an anti-spam measure. Good thing spam bots can't read yet!" => 'patchdemo',
	];
	$wgCaptchaTriggers['edit'] = true;
	$wgCaptchaTriggers['createaccount'] = true;

	// Don't show CAPTCHA when logged in
	$wgGroupPermissions['user']['skipcaptcha'] = true;
}
