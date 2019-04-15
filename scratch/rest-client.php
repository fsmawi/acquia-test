<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();
$client_options = array(
  'verify' => FALSE,
);
$response = $client->get('https://planktonwipprod.sprint114.ahclouddev.com/serialized-object/1', $client_options);
print_r(json_decode($response->getBody()));
