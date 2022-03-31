<?php

$wgGroupPermissions['sysop']['flow-create-board'] = true;

if ( file_exists( 'extensions/VisualEditor/extension.json' ) ) {
	$wgDefaultUserOptions['flow-editor'] = 'visualeditor';
}
