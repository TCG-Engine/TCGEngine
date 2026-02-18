<?php
// Returns the web path to the latest GeneratedCardDictionaries_*.js for a given root.
header('Content-Type: application/json');

$root = $_GET['root'] ?? '';

if (empty($root)) {
    echo json_encode(['success' => false, 'error' => 'root parameter required']);
    exit;
}

// Sanitize root name - only allow alphanumeric and underscores
$root = preg_replace('/[^a-zA-Z0-9_]/', '', $root);
if (empty($root)) {
    echo json_encode(['success' => false, 'error' => 'Invalid root name']);
    exit;
}

$baseDir = dirname(dirname(dirname(__FILE__)));
$generatedDir = $baseDir . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR . 'GeneratedCode' . DIRECTORY_SEPARATOR;

$files = glob($generatedDir . 'GeneratedCardDictionaries_*.js');

if (empty($files)) {
    echo json_encode(['success' => false, 'error' => 'No GeneratedCardDictionaries found for root: ' . $root]);
    exit;
}

// Sort to get the latest (timestamped filename sorts lexicographically)
sort($files);
$latestFile = end($files);
$filename = basename($latestFile);

// Return the web-accessible path
echo json_encode([
    'success' => true,
    'path' => '/TCGEngine/' . $root . '/GeneratedCode/' . $filename,
    'filename' => $filename
]);
