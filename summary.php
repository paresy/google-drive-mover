<?php

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require __DIR__ . '/client.php';

use Google\Service\Drive;

// Get the API client and construct the service object.
$client = getClient();
$service = new Drive($client);

// Count how many files someone owns
$ownerShipCounter = [];

// FolderID of our "GmbH" folder
inspectFolder('xxxxx', ["GmbH"]);

print_r($ownerShipCounter);

function inspectFolder($folderID, $levelInformation)
{
    global $ownerShipCounter, $service;
    do {
        $optParams = [
            'pageSize' => 100,
            'fields' => 'nextPageToken, files(id, mimeType, name, owners)',
            'q' => "'$folderID' in parents",
        ];
        if (isset($token)) {
            $optParams["pageToken"] = $token;
        }
        $results = $service->files->listFiles($optParams);
        foreach ($results->getFiles() as $file) {
            printf("%s > %s\n", implode(" > ", $levelInformation), $file->getName());

            if ($file->getMimeType() == 'application/vnd.google-apps.folder') {
                inspectFolder($file->getId(), array_merge($levelInformation, [$file->getName()]));
            } else {
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