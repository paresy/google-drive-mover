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

// Count how many files someone owns
$ownerShipCounter = [];

// Traverse the folders of our My Drive folder
inspectFolder($sourceFolderID);

print_r($ownerShipCounter);

function inspectFolder($folderID, $levelInformation = [])
{
    global $ownerShipCounter, $service;
    do {
        $optParams = [
            'pageSize' => 100,
            'fields' => 'nextPageToken, files(id, mimeType, name, owners, trashed, webViewLink)',
            'q' => "'$folderID' in parents",
        ];
        if (isset($token)) {
            $optParams["pageToken"] = $token;
        }
        $results = $service->files->listFiles($optParams);
        foreach ($results->getFiles() as $file) {
            if ($file->getMimeType() == 'application/vnd.google-apps.folder') {
                inspectFolder($file->getId(), array_merge($levelInformation, [$file->getName()]));
            }
            else if($file->getMimeType() == 'application/vnd.google-apps.shortcut') {
                // Skip shortcuts. They can be recursive and break the script.
            }
            else if ($file->getTrashed()) {
                // Skip trashed files
            }
            else {
                printf("%s, (Owner: %s)\n", implode(" > ", array_merge($levelInformation, [$file->getName()])), $file->getOwners()[0]->getDisplayName());

                // Uncomment if you want to get links for all files
                //printf("*** %s\n", $file->getWebViewLink());

                $owner = $file->getOwners()[0]->getDisplayName() . " <" . $file->getOwners()[0]->getEmailAddress() . ">";
                if (!isset($ownerShipCounter[$owner])) {
                    $ownerShipCounter[$owner] = 1;
                } else {
                    $ownerShipCounter[$owner]++;
                }
            }
        }
        $token = $results->getNextPageToken();
    } while($token);
}