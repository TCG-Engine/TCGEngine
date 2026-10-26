<?php
// TEMP probe (Hemlock vs Ahsoka game read, 2026-10-26): play seat 1's next decisions on a built game with the real bot chain
// (SWUBotLegalActions → SWUBotHeuristicChoose → EngineExecuteLoadedAction), printing the pick, the rule, and the top fallback scores.
// Usage: php SWUSim/DevTools/_probe_bot.php <game> <variant ('' | no-x | try-x)> [steps=1] [style=midrange] [seat=1]
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
$root = dirname(__DIR__, 2); chdir($root);
include_once './Core/EngineActionRunner.php';
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
require_once './SWUSim/BotController.php';
global $gameName, $playerID;
$gameName = $argv[1]; $GLOBALS['gameName'] = $gameName;
$variant = strval($argv[2] ?? ''); $steps = intval($argv[3] ?? 1); $style = strval($argv[4] ?? 'midrange'); $seat = intval($argv[5] ?? 1);
$playerID = $seat;
ParseGamestate('./SWUSim/');
$GLOBALS['SWUBotFiredLog'] = [];
for ($s = 0; $s < $steps; $s++) {
    $playerID = $seat;
    $legal = SWUBotLegalActions($gameName, $seat);
    $acts = $legal['actions'] ?? [];
    if (empty($acts) || intval($legal['playerID'] ?? 0) !== $seat) { echo "  (no decision for seat $seat)\n"; break; }
    // The fallback's view of every action, under this variant.
    $prev = $GLOBALS['SWUBotDisabledFeatures'] ?? [];
    SWUBotSetDisabledFeatures(SWUBotVariantDisabled($variant) ?? []);
    if (function_exists('SWUBotMemoBegin')) SWUBotMemoBegin();
    $ctx = ['style' => $style, 'seat' => $seat, 'opp' => SWUBotOpponent($seat), 'kind' => strval($legal['kind'] ?? ''),
            'type' => strval($legal['decisionType'] ?? ''), 'param' => strval($legal['decisionParam'] ?? ''),
            'tooltip' => strval($legal['decisionTooltip'] ?? ''), 'following' => (array)($legal['following'] ?? []), 'actions' => array_values($acts)];
    $ctx['_guides'] = _SWUBotGuides($ctx);
    $sc = [];
    foreach ($ctx['actions'] as $i => $a) $sc[] = [SWUBotScoreAction($ctx, $a, $i), strval($a['label'] ?? $a['cardID'] ?? '?')];
    if (function_exists('SWUBotMemoEnd')) SWUBotMemoEnd();
    SWUBotSetDisabledFeatures($prev);
    usort($sc, fn($x, $y) => $y[0] <=> $x[0]);
    $GLOBALS['SWUBotLastRule'] = '';
    $pick = SWUBotHeuristicChoose($style, $acts, $legal, $variant);
    printf("  step %d [%s %s] pick=%s  (%s)\n", $s + 1, $legal['kind'] ?? '', $legal['decisionTooltip'] ?? '', strval($pick['label'] ?? $pick['cardID'] ?? '?'), $GLOBALS['SWUBotLastRule'] ?: 'fallback');
    foreach (array_slice($sc, 0, 8) as $x) printf("      %8.3f  %s\n", $x[0], $x[1]);
    EngineExecuteLoadedAction(['playerID' => $seat, 'mode' => intval($pick['mode'] ?? 0), 'cardID' => strval($pick['cardID'] ?? ''),
        'buttonInput' => strval($pick['buttonInput'] ?? ''), 'chkInput' => $pick['chkInput'] ?? [], 'inputText' => strval($pick['inputText'] ?? '')],
        'SWUSim', $gameName, ['updateCache' => false]);
}
$log = is_string($GLOBALS['gGameLog'] ?? null) ? $GLOBALS['gGameLog'] : '';
foreach (array_slice(explode('<NL>', $log), -4) as $e) echo '  log: ' . preg_replace('/\[\[([A-Z0-9_]+)\|([^\]]*)\]\]/', '$2', strip_tags($e)) . "\n";
