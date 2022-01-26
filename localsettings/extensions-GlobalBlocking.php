<?php

$wgGlobalBlockingDatabase = $wgDBname;

$wgGroupPermissions['sysop']['globalblock'] = true;
$wgGroupPermissions['sysop']['globalblock-whitelist'] = true;
$wgGroupPermissions['sysop']['globalblock-exempt'] = true;

unset( $wgGroupPermissions['steward'] );
