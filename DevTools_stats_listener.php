<?php
// Throwaway local stats-API stand-in for testing GrandArchiveSim/StatsSubmit.php.
// Run: php -S 0.0.0.0:8899 DevTools_stats_listener.php
// Logs each received POST body (pretty-printed) to stdout and to received_payloads.log,
// and returns {"success": true}.
$body = file_get_contents('php://input');
$decoded = json_decode($body, true);
$pretty = $decoded !== null ? json_encode($decoded, JSON_PRETTY_PRINT) : $body;
$entry = "==== " . date('c') . " " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " ====\n" . $pretty . "\n\n";
file_put_contents(__DIR__ . '/received_payloads.log', $entry, FILE_APPEND);
error_log($entry);
header('Content-Type: application/json');
echo json_encode(["success" => true]);
