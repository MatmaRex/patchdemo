<?php

$wgAutoCreateTempUser['enabled'] = true;

// editing is enabled only for temp accounts
$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['temp']['edit'] = true;
