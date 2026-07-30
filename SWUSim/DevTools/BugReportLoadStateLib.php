<?php
// BugReportLoadStateLib.php — map a bug-report "load mode" to an undo-stack ordinal and step a
// PARSED SWUSim gamestate to it. SWUSim only (undo rides in the gamestate; other roots have none).
// Assumes the SWUSim runtime is already included (SWUComputeUndoTarget / LoadUndoSnapshot / UndoStack*).
// Pure enough to unit-test: the caller does ParseGamestate before and WriteGamestate after.

// Undo-stack ordinal to restore for a given mode (-1 = no step, keep the current/live state):
//   'current'    → -1  the saved snapshot already IS the reported (current) state.
//   'begin'      →  0  ordinal 0 is the pregame 'pregame-step' snapshot — the game's opening state.
//   'last-round' →  SWUComputeUndoTarget('phase') — the first action of the CURRENT round's action
//                    phase (the lowest run of 'action' entries above the most recent non-action
//                    'resource'/'pregame-step' boundary), i.e. the start of the round in progress.
function SWUBugReportUndoOrdinalForMode(string $mode): int {
    if (UndoStackCount() <= 0) return -1;
    switch ($mode) {
        case 'begin':      return 0;
        case 'last-round': return SWUComputeUndoTarget('phase');
        case 'current':
        default:           return -1;
    }
}

// Step the currently-parsed gamestate to $mode's undo state. Returns a status array; the caller
// persists the result with WriteGamestate when 'stepped' is true.
function SWUBugReportStepLoadedGamestate(string $mode): array {
    $ord = SWUBugReportUndoOrdinalForMode($mode);
    if ($ord < 0) {
        return ['ok' => true, 'mode' => $mode, 'ordinal' => -1, 'stepped' => false, 'boundary' => '', 'phase' => ''];
    }
    $line = UndoStackRead($ord);
    $rec  = $line !== null ? UndoRecordParse($line) : null;
    $ok   = LoadUndoSnapshot($ord);
    return [
        'ok'       => (bool)$ok,
        'mode'     => $mode,
        'ordinal'  => $ord,
        'stepped'  => (bool)$ok,
        'boundary' => $rec['boundary'] ?? '',
        'phase'    => $rec['phase'] ?? '',
    ];
}
