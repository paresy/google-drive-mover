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

// Traverse the folders of our My Drive folder
cleanupFolder($sourceFolderID);

function cleanupFolder($folderID, $levelInformation = [])
{
    global $service, $dryRun, $quitAfterFirst;

    $count = 0;
    do {
        $optParams = [
            'pageSize' => 100,
            'fields' => 'nextPageToken, files(id, mimeType, name, owners, ownedByMe)',
            'q' => "'$folderID' in parents",
        ];
        if (isset($token)) {
            $optParams["pageToken"] = $token;
        }
        $results = $service->files->listFiles($optParams);
        foreach ($results->getFiles() as $file) {
            if ($file->getMimeType() == 'application/vnd.google-apps.folder') {
                if(cleanupFolder($file->getId(), array_merge($levelInformation, [$file->getName()]))) {
                    if ($file->getOwnedByMe()) {
                        printf("%s\n", implode(" > ", array_merge($levelInformation, [$file->getName()])));

                        // Delete folder if it was marked as empty
                        printf("*** Deleting %s\n", $file->getId());

                        if (!$dryRun) {
                            $service->files->delete($file->getId());
                            if ($quitAfterFirst) {
                                die("Quit after first delete is enabled!");
                            }
                        }
                    }
                    else {
                        printf("SKIPPED: %s, (Owner: %s)\n", implode(" > ", array_merge($levelInformation, [$file->getName()])), $file->getOwners()[0]->getDisplayName());

                        // Increase counter if folder is not ours
                        $count++;
                    }
                }
                else {
                    printf("NOTEMPTY: %s, (Owner: %s)\n", implode(" > ", array_merge($levelInformation, [$file->getName()])), $file->getOwners()[0]->getDisplayName());

                    // Increase counter if folder is not empty
                    $count++;
                }
            } else {
                // Increase counter for every file
                $count++;
            }
        }
        $token = $results->getNextPageToken();
    } while($token);

    return $count == 0;
}