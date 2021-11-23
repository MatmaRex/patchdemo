<?php

// 3D uploads are not enabled yet as they require
// the 3d2png binary and other dependencies.
// $wgTrustedMediaFormats[] = 'application/sla';
// $wgFileExtensions[] = 'stl'

// Enable MultimediaViewer integration
$wgMediaViewerExtensions['stl'] = 'mmv.3d';
