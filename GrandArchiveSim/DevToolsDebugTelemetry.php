<?php
// Throwaway HTTP debug endpoint: must run inside the web server process (mod_php) so it shares
// the live APCu gamestate segment with the running game — a separate CLI `php` invocation gets
// its own empty APCu cache and can't see it. DELETE after use.
header('Content-Type: application/json');
include '../Core/UILibraries.php';
include '../Core/NetworkingLibraries.php';
include '../Core/HTTPLibraries.php';
include '../Core/CoreZoneModifiers.php';
include './GamestateParser.php';
include './ZoneAccessors.php';
include './ZoneClasses.php';
include '../GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/StatsSubmit.php';

$gameName = TryGet("gameName");
ParseGamestate();

global $gTelemetry;
$detail = GACaptureCurrentGameDetail();
$fakeMatch = [
    'matchId' => 'DEBUG-M1', 'format' => 'premier', 'bestOf' => 1,
    'players' => ['1' => ['deckLink' => 'https://shoutatyourdecks.com/decks/4db11b14-2f36-4cc2-bb63-eb9d6c95d98c'], '2' => ['deckLink' => '']],
];
$fakeGame = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => 1, 'detail' => $detail];
$payload = GABuildGameResultPayload($fakeMatch, $fakeGame);

echo json_encode([
    'rawTelemetry' => json_decode($gTelemetry, true),
    'detail' => $detail,
    'payload' => $payload,
], JSON_PRETTY_PRINT);
