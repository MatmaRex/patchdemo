<?php

$wgGroupPermissions['user']['ipinfo'] = true;
$wgGroupPermissions['user']['ipinfo-view-basic'] = true;
$wgGroupPermissions['sysop']['ipinfo-view-full'] = true;
$wgGroupPermissions['sysop']['ipinfo-view-log'] = true;

$wgAddGroups['sysop']['no-ipinfo'] = true;
$wgRemoveGroups['sysop']['no-ipinfo'] = true;
