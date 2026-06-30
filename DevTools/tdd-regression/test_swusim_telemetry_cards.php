<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_telemetry_cards.php
header('Content-Type: text/plain');
include __DIR__ . '/../../Core/DeterministicRNG.php';
include __DIR__ . '/../../Core/CoreZoneModifiers.php';
include __DIR__ . '/../../SWUSim/ZoneClasses.php';
include __DIR__ . '/../../SWUSim/ZoneAccessors.php';
include __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include __DIR__ . '/../../SWUSim/GamestateParser.php'; // pulls in GameLogic + Telemetry

global $gameName;
$gameName = 'telcards_' . uniqid();
InitializeGamestate();

// Build a 2-card deck for player 1.
$deck = &GetDeck(1);
array_push($deck, new Deck('JTL_100'));
array_push($deck, new Deck('JTL_101'));
for ($i = 0; $i < count($deck); $i++) $deck[$i]->mzIndex = $i;

$checks = [];

// Draw both → per-card 'drawn' should record each.
DoDrawCard(1, 2);
$t = SWUTelemetryGet();
$checks['drew JTL_100'] = ($t['cards']['1']['JTL_100']['drawn'] ?? 0) === 1;
$checks['drew JTL_101'] = ($t['cards']['1']['JTL_101']['drawn'] ?? 0) === 1;

// Resource one hand card → per-card 'resourced' (do this first, on a fresh non-removed slot).
$hand = &GetHand(1);
for ($i = 0; $i < count($hand); $i++) $hand[$i]->mzIndex = $i;
$resCardId = $hand[1]->CardID ?? '';
DoResourceCard(1, "myHand-1");
$t = SWUTelemetryGet();
$checks['resourced recorded'] = ($t['cards']['1'][$resCardId]['resourced'] ?? 0) === 1;

// Discard the remaining hand card → per-card 'discarded'.
$hand = &GetHand(1);
$live = null; foreach ($hand as $k => $c) { if (empty($c->removed)) { $live = $k; break; } }
for ($i = 0; $i < count($hand); $i++) $hand[$i]->mzIndex = $i;
$discardCardId = $hand[$live]->CardID ?? '';
DoDiscardCard(1, "myHand-$live");
$t = SWUTelemetryGet();
$checks['discarded recorded'] = ($t['cards']['1'][$discardCardId]['discarded'] ?? 0) === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', $fails) . " t=" . json_encode($t['cards'] ?? null) . "\n";
