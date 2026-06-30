<?php
// Per-game telemetry accumulator. Serialized in the $gTelemetry gamestate global.
// Shape: {cards:{seat:{cardId:{played,resourced,activated,drawn,discarded}}},
//         turns:[{seat,cardsUsed,resourcesUsed,resourcesLeft,cardsLeft,damageDealt,damageTaken,restored}],
//         cur:{seat:{...running per-turn...}}}
function SWUTelemetryGet() {
    global $gTelemetry;
    $d = json_decode((string)$gTelemetry, true);
    if (!is_array($d)) $d = [];
    $d += ['cards' => [], 'turns' => [], 'cur' => []];
    return $d;
}
function SWUTelemetrySet(array $d) {
    global $gTelemetry;
    $gTelemetry = json_encode($d);
}
function SWUTelemetryInit() {
    global $gTelemetry;
    $gTelemetry = json_encode(['cards' => [], 'turns' => [], 'cur' => []]);
}
function SWUTelemetryBumpCard($seat, $cardId, $field, $n = 1) {
    $seat = strval(intval($seat)); $cardId = strval($cardId);
    if (($seat !== '1' && $seat !== '2') || $cardId === '') return;
    $d = SWUTelemetryGet();
    $cur = $d['cards'][$seat][$cardId] ?? ['played'=>0,'resourced'=>0,'activated'=>0,'drawn'=>0,'discarded'=>0];
    if (!isset($cur[$field])) $cur[$field] = 0;
    $cur[$field] += $n;
    $d['cards'][$seat][$cardId] = $cur;
    SWUTelemetrySet($d);
}
function SWUTelemetryBumpTurn($seat, $field, $n = 1) {
    $seat = strval(intval($seat));
    if ($seat !== '1' && $seat !== '2') return;
    $d = SWUTelemetryGet();
    $cur = $d['cur'][$seat] ?? [];
    $cur[$field] = ($cur[$field] ?? 0) + $n;
    $d['cur'][$seat] = $cur;
    SWUTelemetrySet($d);
}
// Finalize the running per-turn counters for $seat into a turns[] record, then clear them.
function SWUTelemetrySnapshotTurn($seat) {
    $seat = strval(intval($seat));
    if ($seat !== '1' && $seat !== '2') return;
    $d = SWUTelemetryGet();
    $cur = $d['cur'][$seat] ?? [];
    $handLeft = function_exists('GetHand') ? count(array_filter(GetHand(intval($seat)), fn($c)=>empty($c->removed))) : 0;
    $resLeft  = function_exists('SWUResourceCount') ? SWUResourceCount(intval($seat), true) : 0;
    $d['turns'][] = [
        'seat'          => intval($seat),
        'cardsUsed'     => intval($cur['cardsUsed'] ?? 0),
        'resourcesUsed' => intval($cur['resourcesUsed'] ?? 0),
        'resourcesLeft' => intval($resLeft),
        'cardsLeft'     => intval($handLeft),
        'damageDealt'   => intval($cur['damageDealt'] ?? 0),
        'damageTaken'   => intval($cur['damageTaken'] ?? 0),
        'restored'      => intval($cur['restored'] ?? 0), // EXTRA (not in SWUDeck contract)
    ];
    $d['cur'][$seat] = [];
    SWUTelemetrySet($d);
}
