<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') exit;

// Pin the generic action runner to Azuki and its JSON response contract.
$_GET['folderPath'] = 'AzukiSim';
$_GET['responseFormat'] = 'json';

chdir(__DIR__ . '/../..');
require './ProcessInput.php';
