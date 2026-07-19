<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') exit;

// This public adapter intentionally exposes only Azuki's private RL-bot mode.
$_POST['rootName'] = 'AzukiSim';
$_POST['createRlBot'] = '1';
$_POST['format'] = 'rlbot';

chdir(__DIR__ . '/../Lobbies');
require './JoinQueue.php';
