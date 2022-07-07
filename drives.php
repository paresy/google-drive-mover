<?php

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require __DIR__ . '/client.php';

use Google\Service\Drive;

// Get the API client and construct the service object.
$client = getClient();
$service = new Drive($client);

$optParams = [
    'pageSize' => 100,
    'fields' => 'nextPageToken, drives(id, name)',
];
$results = $service->drives->listDrives($optParams);
foreach ($results->getDrives() as $drive) {
    printf("%s\n", $drive->getName());
}