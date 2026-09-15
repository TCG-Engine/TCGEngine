<?php
// Where did each failed sweep game get stuck? Used by retro.py. Reads the KEPT game folders (the runner keeps a
// failed game's SWUSim/Games/<id>; the harness restores its stall state after its end-of-game probes).
//   php -d apc.enable_cli=1 SWUSim/DevTools/rl/classify_failed.php <id> [<id> …]   → one JSON object per line
// Fields: id, head (the first pending decision: seat, type, tooltip, param), winner, the last 3 game-log lines,
// and both leaders.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
chdir(dirname(__DIR__, 3));
include_once './Core/EngineActionRunner.php';
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
global $gameName, $playerID;
$playerID = 1;
foreach (array_slice($argv, 1) as $id) {
    if (!ctype_digit($id) || !is_dir("./SWUSim/Games/$id")) continue;
    $gameName = $id;
    try { ParseGamestate('./SWUSim/'); } catch (Throwable $e) { echo json_encode(['id' => $id, 'error' => $e->getMessage()]), "\n"; continue; }
    $head = null;
    foreach ([1, 2] as $s) foreach (GetDecisionQueue($s) as $e) {
        if (empty($e->removed)) { $head = [$s, strval($e->Type), strval($e->Tooltip), substr(strval($e->Param), 0, 120)]; break 2; }
    }
    global $gGameLog;
    $log = array_map(fn($l) => preg_replace('/\[\[([A-Z0-9_]+)\|([^\]]+)\]\]/', '$2($1)', explode('|', $l, 3)[2] ?? ''),
                     array_slice(explode('<NL>', (string)$gGameLog), -3));
    $leaders = [];
    foreach ([1, 2] as $s) { $l = GetLeader($s)[0] ?? null; $leaders[] = $l ? strval($l->CardID) : '?'; }
    echo json_encode(['id' => $id, 'head' => $head, 'winner' => function_exists('SWUGetGameWinner') ? SWUGetGameWinner() : null,
                      'log' => $log, 'leaders' => $leaders], JSON_UNESCAPED_SLASHES), "\n";
}
