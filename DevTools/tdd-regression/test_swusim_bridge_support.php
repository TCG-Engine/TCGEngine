<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_bridge_support.php
//
// The bridge is the decision-response encoder the SWUSim bot delegates to (spec Section 2).
// READ-ONLY: library-only load, no game is created.
header('Content-Type: text/plain');
if (!defined('TCGENGINE_BRIDGE_LIBRARY_ONLY')) define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once __DIR__ . '/../TestAutomationBridge.php';

$checks = [];

$zones = BridgePlayableZonesForRoot('SWUSim');
$checks['SWUSim has playable zones']   = is_array($zones) && !empty($zones);
foreach (['myHand', 'myGroundArena', 'mySpaceArena', 'myResources', 'myLeader', 'myBase'] as $z) {
    $checks["playable zones include $z"] = in_array($z, $zones, true);
}
// SWU is a two-arena game; conflating them would make attack enumeration wrong.
$checks['ground and space are distinct'] =
    in_array('myGroundArena', $zones, true) && in_array('mySpaceArena', $zones, true);

$pass = BridgePassActionForRoot('SWUSim', 2);
$checks['pass action is an array']     = is_array($pass);
$checks['pass action targets seat 2']  = intval($pass['playerID'] ?? 0) === 2;
$checks['pass action has a cardID']    = is_string($pass['cardID'] ?? null) && $pass['cardID'] !== '';

// Unlike GA and Azuki, a SWU seat has TWO persistent objects — leader AND base.
$checks['SWU avatar summary exists']   = function_exists('BridgeSWUAvatarSummary');

// ── OPTIONCHOOSE: a leading '@' segment is a UI DIRECTIVE, never an answer ───────────────────────
// "@{$topID}" renders that card's art above the buttons and "@-" renders a placeholder;
// Core/OptionChooseUI.js strips such segments and never draws one as a button. The engine's own
// validator does NOT: SWUValidateDecisionAnswer's OPTIONCHOOSE arm is `in_array($answer, $labels)`
// against the UN-stripped list, so it ACCEPTS the directive and the downstream handler then finds no
// matching label. The bot's 'first-legal' chooser takes $actions[0] — which was exactly that token —
// on every one of the 9+ producers that use the prefix (SOR_246, SOR_051 Ezra, SOR_147 C-3PO,
// SOR_236 Reinforcement Walker, LAW AllianceOutpost/Watchful, TS26 Ahsoka, and GameLogic.php's
// multi-Action base picker). So the filtering has to happen HERE, in the enumerator.
$optionChoose = function (string $param) {
    $d = new stdClass();
    $d->Type = 'OPTIONCHOOSE';
    $d->Param = $param;
    return array_column(BridgeEnumerateDecisionActions($d, 1), 'cardID');
};

$withArt = $optionChoose('@SOR_246&Play&Leave');
$checks['OPTIONCHOOSE drops the leading @CardID directive'] = ($withArt === ['Play', 'Leave']);
// first-legal is what the bot actually uses; naming it makes the failure mode explicit.
$checks['OPTIONCHOOSE first-legal is a real label']         = (($withArt[0] ?? '') === 'Play');

$withDash = $optionChoose('@-&Your_deck&P2_deck');
$checks['OPTIONCHOOSE drops the "@-" placeholder directive'] = ($withDash === ['Your_deck', 'P2_deck']);

// CONTROL — a Param with no directive is untouched. The filter must not eat ordinary labels, and an
// underscore inside one ("Your_deck") is TRANSPORT, not display text: it is submitted verbatim.
$plain = $optionChoose('Ground&Space');
$checks['OPTIONCHOOSE leaves a directive-free list alone'] = ($plain === ['Ground', 'Space']);
$plainSeats = $optionChoose('P2&P3&P4');
$checks['OPTIONCHOOSE keeps every seat label']             = ($plainSeats === ['P2', 'P3', 'P4']);

// Nothing offered may start with '@', whatever the shape.
$anyDirective = false;
foreach (['@SOR_246&Play&Leave', '@-&Your_deck&P2_deck', 'Ground&Space', '@LOF_012&OK'] as $p) {
    foreach ($optionChoose($p) as $label) { if (($label[0] ?? '') === '@') $anyDirective = true; }
}
$checks['no OPTIONCHOOSE action is a UI directive'] = ($anyDirective === false);

// ⚠ SWUSim-ONLY BLAST RADIUS. This file is loaded at runtime by AzukiSim's live in-game bot, so the
// change had to be proven inert for the other roots. It is: OPTIONCHOOSE has ZERO producers outside
// SWUSim (grep of GrandArchiveSim/, AzukiSim/, HellbreakSim/, FaBSim/ finds none), and the edit sits
// inside `case 'OPTIONCHOOSE':`, so no other decision type can reach it.

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    exit(1);
}
echo "PASS (" . count($checks) . " checks)\n";
