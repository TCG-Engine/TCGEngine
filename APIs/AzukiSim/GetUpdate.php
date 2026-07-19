<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') exit;

// Run the generated endpoint from its expected working directory. Keeping this
// adapter separate means generator-owned files stay untouched.
chdir(__DIR__ . '/../../AzukiSim');
require './GetNextTurn.php';
