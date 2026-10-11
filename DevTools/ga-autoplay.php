<?php
// ga-autoplay.php -- replay a fixture's recorded actions, then keep answering whatever the engine asks until the decision
// queue is empty, printing every prompt and the answer given. Use it on a draft scenario (just castSpell with no answers) to
// discover the real prompt order, then write the answers into the scenario.
//
//   php DevTools/ga-autoplay.php --test=<slug> [--root=GrandArchiveSim] [--max=40] [--yes=YES|NO]
//
// Answers: first option of every MZ choice, YES (or --yes) for YESNO, 1 for numbers, PASS otherwise.
require_once __DIR__ . '/../Core/EngineActionRunner.php';

$args = getopt('', ['root::', 'test::', 'max::', 'yes::']);
$root = (string)($args['root'] ?? 'GrandArchiveSim');
$test = (string)($args['test'] ?? '');
$max = intval($args['max'] ?? 40);
$yes = (string)($args['yes'] ?? 'YES');
if ($test === '') { fwrite(STDERR, "usage: php DevTools/ga-autoplay.php --test=<slug>\n"); exit(1); }

$fixtureDir = RegressionFixtureDir($root, $test);
$gameName = 'auto_' . preg_replace('/[^a-z0-9_-]/i', '', $test) . '_' . uniqid();
$gameDir = RegressionRepoRoot() . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
RegressionEnsureDir($gameDir);
file_put_contents($gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt',
    RegressionNormalizeGamestateTextForRoot($root, file_get_contents($fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt')));

function autoTop(): array {
    foreach ([1, 2] as $p) {
        foreach (GetDecisionQueue($p) as $d) {
            if (is_object($d) && !(method_exists($d, 'Removed') && $d->Removed())) return [$p, $d];
        }
    }
    return [0, null];
}

function autoZoneSummary(): void {
    global $playerID;
    $playerID = 1;
    foreach ([1, 2] as $p) {
        $line = "P{$p}:";
        foreach (['Hand' => 'GetHand', 'Memory' => 'GetMemory', 'Graveyard' => 'GetGraveyard', 'Banish' => 'GetBanish', 'Deck' => 'GetDeck'] as $label => $fn) {
            if (!function_exists($fn)) continue;
            $n = 0;
            foreach ($fn($p) as $o) if (is_object($o) && !(method_exists($o, 'Removed') && $o->Removed())) $n++;
            $line .= " {$label}={$n}";
        }
        echo $line . "\n";
        $i = 0;
        foreach (GetField($p) as $o) {
            if (!is_object($o) || (method_exists($o, 'Removed') && $o->Removed())) continue;
            $te = is_array($o->TurnEffects ?? null) ? json_encode($o->TurnEffects) : '[]';
            $co = is_array($o->Counters ?? null) ? json_encode($o->Counters) : '[]';
            echo "    p{$p}Field-{$i} " . CardName($o->CardID) . " dmg=" . intval($o->Damage) . " status=" . intval($o->Status) . " counters={$co} turnEffects={$te} controller=" . intval($o->Controller) . "\n";
            $i++;
        }
    }
}

EngineLoadRootRuntime($root);
global $gameName;
ParseGamestate('./' . $root . '/');

foreach (RegressionLoadActionsForFixture($fixtureDir) as $i => $action) {
    $result = EngineRunAction($action, $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    echo 'action ' . ($i + 1) . ' P' . $action['playerID'] . ' ' . $action['cardID'] . ($result['success'] ? '' : '   [REJECTED: ' . $result['message'] . ']') . "\n";
}

for ($n = 0; $n < $max; ++$n) {
    ParseGamestate('./' . $root . '/');
    [$p, $d] = autoTop();
    if ($d === null) { echo "-- decision queue empty --\n"; break; }
    $type = $d->Type;
    $param = strval($d->Param);
    $answer = 'PASS';
    if (in_array($type, ['MZCHOOSE', 'MZMAYCHOOSE', 'MZMULTICHOOSE'])) {
        $optStr = $type === 'MZMULTICHOOSE' ? (explode('|', $param, 3)[2] ?? '') : $param;
        $first = trim(explode('@', explode('&', $optStr)[0])[0]);
        $answer = $first !== '' ? $first : 'PASS';
        if ($answer !== 'PASS' && strpos($answer, '-') === false) $answer .= '-0'; // reserve payment lists the zone name only
        if (stripos(strval($d->Tooltip ?? ''), 'fast_action') !== false) $answer = 'PASS';   // priority window: decline
    } elseif ($type === 'MZMODAL') {
        $pp = explode('|', $param, 3);   // min|max|options: answer with the first min mode indexes ("0" or "0,1")
        $answer = implode(',', range(0, max(1, intval($pp[0] ?? 1)) - 1));
    } elseif ($type === 'YESNO') {
        $answer = $yes;
    } elseif ($type === 'NUMBERCHOOSE') {
        $answer = '1';
    }
    echo "P{$p} {$type} [{$param}] " . ($d->Tooltip ?? '') . "  => {$answer}\n";
    $result = EngineRunAction(['playerID' => $p, 'mode' => 100, 'buttonInput' => '', 'cardID' => $answer, 'chkInput' => [], 'inputText' => ''], $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    if (!$result['success']) { echo "   [REJECTED: " . $result['message'] . "]\n"; break; }
}
ParseGamestate('./' . $root . '/');
echo "\nfinal state:\n";
autoZoneSummary();
RegressionDeleteDirRecursive($gameDir);
