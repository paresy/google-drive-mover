<?php

// You can get the ID of the source folder from within the browser URL
$sourceFolderID = '';

// This is the ID of the shared drive. Use drives.php to get IDs
$targetFolderID = '';

$dryRun = true;
// Just print files which will be moved/deleted, but do not move/delete them

// Exit after first move operation. Nice option to test file by files before doing the big job
$quitAfterFirst = false;

// Also show progress on skipped/trashed files (a lot more verbose)
$showSkipped = true;

// Also show progress on each visited folder (a lot more verbose)
$showFolders = true;