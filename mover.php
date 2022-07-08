<?php

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require __DIR__ . '/client.php';
require __DIR__ . '/config.php';

use Google\Service\Drive;

// Get the API client and construct the service object.
$client = getClient();
$service = new Drive($client);

// Cache FolderIDs for faster access
$folderCache = [];

// This will move all files from the source folder of my drive to the target folder of the shared drive
moveFiles($sourceFolderID);

// Test Folder generation in target folder
//echo getTargetFolder(["Test Level 1", "Test Level 2", "Test Level 3"]);

function getTargetFolder($levelInformation) {
    global $service, $targetFolderID, $folderCache;

    $folderID = $targetFolderID;
    $currentLevel = &$folderCache;
    foreach($levelInformation as $name) {
        if (isset($currentLevel[$name])) {
            $folderID = $currentLevel[$name]["id"];
        }
        else {
            // Search if folder is already created
            $optParams = [
                'pageSize' => 1,
                'fields' => 'files(id)',
                'driveId' => $targetFolderID,
                'q' => "mimeType = 'application/vnd.google-apps.folder' and name = '$name'",
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
                'corpora' => 'drive',
            ];
            if ($folderID != $targetFolderID) {
                $optParams['q'] .= "and '$folderID' in parents";
            }
            $results = $service->files->listFiles($optParams);

            // Create a new one
            if (empty($results->getFiles())) {
                $file = new \Google_Service_Drive_DriveFile();
                $file->setName($name);
                $file->setMimeType('application/vnd.google-apps.folder');
                $file->setParents([$folderID]);

                $folder = $service->files->create($file, [
                    'supportsAllDrives' => true
                ]);
                $folderID = $folder->getId();
            }
            else {
                $folderID = $results->getFiles()[0]->getId();
            }

            // Map into cache
            $currentLevel[$name]["id"] = $folderID;
            $currentLevel[$name]["children"] = [];
        }
        $currentLevel = &$currentLevel[$name]["children"];
    }
    return $folderID;
}

function moveFiles($sourceFolderID, $levelInformation = [])
{
    global $service, $dryRun, $quitAfterFirst, $showSkipped, $showFolders;
    do {
        $optParams = [
            'pageSize' => 100,
            'fields' => 'nextPageToken, files(id, mimeType, name, owners, parents, ownedByMe)',
            'q' => "'$sourceFolderID' in parents",
        ];
        if (isset($token)) {
            $optParams["pageToken"] = $token;
        }
        $results = $service->files->listFiles($optParams);
        foreach ($results->getFiles() as $file) {
            if ($file->getMimeType() == 'application/vnd.google-apps.folder') {
                if ($showFolders) {
                    printf("FOLDER: %s, (Owner: %s)\n", implode(" > ", array_merge($levelInformation, [$file->getName()])), $file->getOwners()[0]->getDisplayName());
                }
                moveFiles($file->getId(), array_merge($levelInformation, [$file->getName()]));
            } else {
                if ($file->getOwnedByMe()) {
                    // Move file into new Shared Drive
                    printf("%s\n", implode(" > ", array_merge($levelInformation, [$file->getName()])));

                    if (!$dryRun) {
                        $folderID = getTargetFolder($levelInformation);

                        printf("*** Moving %s to %s\n", $file->getId(), $folderID);

                        $emptyFile = new Google_Service_Drive_DriveFile();
                        $service->files->update(
                            $file->getId(),
                            $emptyFile,
                            [
                                'supportsAllDrives' => true,
                                'addParents' => $folderID,
                                'removeParents' => $file->getParents()
                            ]
                        );
                        if ($quitAfterFirst) {
                            die("Quit after first move is enabled!");
                        }
                    }
                }
                else {
                    if ($showSkipped) {
                        printf("SKIPPED: %s, (Owner: %s)\n", implode(" > ", array_merge($levelInformation, [$file->getName()])), $file->getOwners()[0]->getDisplayName());
                    }
                }
            }
        }
        $token = $results->getNextPageToken();
    } while($token);
}