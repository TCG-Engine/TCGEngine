<?php
// Per-game telemetry accumulator. Serialized in the $gTelemetry gamestate global.
// Shape: {cards:{seat:{cardId:{drawn,drawnToMemory,materialized,reserved,discarded,activated}}},
//         turns:[{seat,turn,cardsPlayed,memorySpent,reserveSpent,damageDealt,damageTaken,healed,level,hp}],
//         cur:{seat:{...running per-turn...}},
//         combatEvents:[{type:"attack_initiated",...} | {type:"damage_resolved",...}]}
// Mirrors SWUSim/Telemetry.php's shape/conventions; GA-specific fields (materialized/reserved
// instead of played/resourced, drawnToMemory, level, combatEvents) reflect GA's own zones/mechanics.
function GATelemetrySeatOk($seat) {
    $s = intval($seat);
    $max = function_exists('SeatCountForGame') ? SeatCountForGame() : 2;
    return $s >= 1 && $s <= $max;
}
function GATelemetryGet() {
    global $gTelemetry;
    $d = json_decode((string)$gTelemetry, true);
    if (!is_array($d)) $d = [];
    $d += ['cards' => [], 'turns' => [], 'cur' => [], 'combatEvents' => []];
    return $d;
}
function GATelemetrySet(array $d) {
    global $gTelemetry;
    $gTelemetry = json_encode($d);
}
function GATelemetryInit() {
    global $gTelemetry;
    $gTelemetry = json_encode(['cards' => [], 'turns' => [], 'cur' => [], 'combatEvents' => []]);
}
function GATelemetryBumpCard($seat, $cardId, $field, $n = 1) {
    $seat = strval(intval($seat)); $cardId = strval($cardId);
    if (!GATelemetrySeatOk($seat) || $cardId === '') return;
    $d = GATelemetryGet();
    $cur = $d['cards'][$seat][$cardId] ?? ['drawn'=>0,'drawnToMemory'=>0,'materialized'=>0,'reserved'=>0,'discarded'=>0,'activated'=>0];
    if (!isset($cur[$field])) $cur[$field] = 0;
    $cur[$field] += $n;
    $d['cards'][$seat][$cardId] = $cur;
    GATelemetrySet($d);
}
function GATelemetryBumpTurn($seat, $field, $n = 1) {
    $seat = strval(intval($seat));
    if (!GATelemetrySeatOk($seat)) return;
    $d = GATelemetryGet();
    $cur = $d['cur'][$seat] ?? [];
    $cur[$field] = ($cur[$field] ?? 0) + $n;
    $d['cur'][$seat] = $cur;
    GATelemetrySet($d);
}
// Append one combat event (attack_initiated or damage_resolved). $event is a plain assoc array;
// the caller is responsible for its shape (see GrandArchiveSim/StatsSubmit.php for the contract).
function GATelemetryLogCombatEvent(array $event) {
    $d = GATelemetryGet();
    $d['combatEvents'][] = $event;
    GATelemetrySet($d);
}
// Finalize the running per-turn counters for $seat into a turns[] record, then clear them.
// Reads the seat's champion HP/Level at the moment of the snapshot (end of that seat's turn).
function GATelemetrySnapshotTurn($seat, $turnNumber = null) {
    $seat = strval(intval($seat));
    if (!GATelemetrySeatOk($seat)) return;
    $d = GATelemetryGet();
    $cur = $d['cur'][$seat] ?? [];
    $level = 0; $hp = 0;
    if (function_exists('FindChampionMZ') && function_exists('GetZoneObject')) {
        // Both helpers resolve my/their zone names from the ambient viewer perspective, not
        // from FindChampionMZ's argument. Pin that perspective while reading this seat.
        global $playerID;
        $savedPlayerID = $playerID;
        $playerID = intval($seat);
        $champMZ = FindChampionMZ(intval($seat));
        $champObj = $champMZ !== null ? GetZoneObject($champMZ) : null;
        $playerID = $savedPlayerID;
        if ($champObj !== null) {
            if (function_exists('ObjectCurrentLevel')) $level = intval(ObjectCurrentLevel($champObj));
            if (function_exists('ObjectCurrentHP'))    $hp    = intval(ObjectCurrentHP($champObj));
        }
    }
    $turnNo = $turnNumber !== null ? intval($turnNumber) : (function_exists('GetTurnNumber') ? intval(GetTurnNumber()) : 0);
    $d['turns'][] = [
        'seat'          => intval($seat),
        'turn'          => $turnNo,
        'cardsPlayed'   => intval($cur['cardsPlayed'] ?? 0),
        'memorySpent'   => intval($cur['memorySpent'] ?? 0),
        'reserveSpent'  => intval($cur['reserveSpent'] ?? 0),
        'damageDealt'   => intval($cur['damageDealt'] ?? 0),
        'damageTaken'   => intval($cur['damageTaken'] ?? 0),
        'healed'        => intval($cur['healed'] ?? 0),
        'level'         => $level,
        'hp'            => $hp,
    ];
    $d['cur'][$seat] = [];
    GATelemetrySet($d);
}

// A game can end during its active turn (most commonly from lethal combat), before EndPhase()
// gets a chance to snapshot it. Capture every seat with pending counters so the final payload
// includes both the acting player's damage/cards and the defending player's damage taken.
function GATelemetryFlushPendingTurns($turnNumber = null) {
    $d = GATelemetryGet();
    foreach (($d['cur'] ?? []) as $seat => $cur) {
        if (!is_array($cur) || count($cur) === 0) continue;
        GATelemetrySnapshotTurn(intval($seat), $turnNumber);
    }
}
