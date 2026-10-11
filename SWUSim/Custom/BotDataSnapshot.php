<?php
// BotData — the per-action STATE SNAPSHOT (spec docs/superpowers/specs/2026-09-23-swusim-bot-data-loop-design.md §1.5).
// A pure read: it never mutates the game and never touches the filesystem. Everything an offline
// reader needs to judge a move, with no schema knowledge required.
//
// ⚠ BOTH seats' hands are recorded. This is a post-hoc analysis corpus, not a live view — an
// analysis that cannot see what the human was holding cannot tell a good play from a forced one.
//
// ⚠ NO IDENTITY. Seats are 1/2 plus isBot. No username, userId or authKey is passed in here at all,
// so there is nothing for a later scrubber to miss.
//
// ⚠ LOAD ORDER. SWUBotUnits() lives in BotEvaluator.php, which production only loads via
// BotHeuristic.php <- BotController.php. That chain is NOT loaded on a human's request, and this
// recorder runs on exactly that request — so the dependency is required here, explicitly. Without
// this line a human's first action fatals. Guard: botdata_snapshot_standalone_test.php.
require_once __DIR__ . '/BotEvaluator.php';

function _SWUBotDataUnitRow(array $v): array {
    $obj = $v['obj'] ?? null;
    // Experience is the SOR_T01 token subcard; Shield is SOR_T02 (already counted by the view).
    $exp = 0; $upgradeIds = [];
    foreach (($obj->Subcards ?? []) as $sub) {
        $cid = is_array($sub) ? strval($sub['CardID'] ?? '') : strval($sub->CardID ?? '');
        $gone = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
        if ($cid === '' || $gone) continue;
        if ($cid === 'SOR_T01') { $exp++; continue; }
        if ($cid === 'SOR_T02') continue;
        $upgradeIds[] = $cid;
    }
    // Player-facing TurnEffects only; SWU_-prefixed entries are backend cost/phase bookkeeping.
    $effects = array_values(array_filter(array_map('strval', ($obj->TurnEffects ?? [])),
        fn($e) => strpos($e, 'SWU_') !== 0));
    return [
        'id' => strval($v['cardID'] ?? ''), 'p' => intval($v['power'] ?? 0), 'hp' => intval($v['hp'] ?? 0),
        'dmg' => max(0, intval($v['hp'] ?? 0) - intval($v['remaining'] ?? 0)),
        'ready' => (bool)($v['ready'] ?? false), 'sentinel' => (bool)($v['sentinel'] ?? false),
        'shields' => intval($v['shields'] ?? 0), 'exp' => $exp,
        'upgrades' => $upgradeIds, 'effects' => $effects,
        'leader' => (bool)($v['isLeader'] ?? false),
    ];
}

function _SWUBotDataSeat(int $seat, array $botSeats): array {
    $live = fn($z) => array_values(array_filter((array)$z, fn($o) => $o !== null && empty($o->removed)));
    $baseZone = GetBase($seat);
    $baseObj  = $baseZone[0] ?? null;
    $ldrZone  = GetLeader($seat);
    $ldrObj   = $ldrZone[0] ?? null;

    $hand = [];
    foreach ($live(GetHand($seat)) as $o) {
        $hand[] = strval($o->CardID ?? '') . '$' . intval(SWUComputePlayCost($seat, $o));
    }
    $ground = []; $space = [];
    foreach (SWUBotUnits($seat) as $v) {
        if (($v['arena'] ?? '') === 'Space') $space[] = _SWUBotDataUnitRow($v);
        else $ground[] = _SWUBotDataUnitRow($v);
    }
    return [
        'bot'     => in_array($seat, $botSeats, true),
        'base'    => $baseObj === null ? '' : strval($baseObj->CardID ?? ''),
        'baseHp'  => SWUBaseRemainingHp($seat),
        'leader'  => ['id'       => $ldrObj === null ? '' : strval($ldrObj->CardID ?? ''),
                      'deployed' => $ldrObj !== null && !empty($ldrObj->Deployed),
                      'ready'    => $ldrObj !== null && intval($ldrObj->Status ?? 0) === 1],
        'res'     => ['total' => SWUResourceCount($seat), 'ready' => SWUResourceCount($seat, true)],
        // WHICH cards were resourced. A count alone cannot show a resourcing decision, and which card
        // a seat puts down is a real lever (feature 'resourcing3'). Free to record — no new event.
        'resCards' => array_map(fn($o) => strval($o->CardID ?? ''), $live(GetResources($seat))),
        'hand'    => $hand,
        'ground'  => $ground,
        'space'   => $space,
        'discard' => array_map(fn($o) => strval($o->CardID ?? ''), $live(GetDiscard($seat))),
        'deck'    => count($live(GetDeck($seat))),
    ];
}

// $action: ['kind' => 'play'|'attack'|'ability'|'deploy'|'initiative'|'undo'|'other', 'card' => CardID, 'mz' => mzID]
function SWUBotDataSnapshot(int $actionSeat, string $actor, array $action): array {
    $botSeats = function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [];
    return [
        'round'  => function_exists('GetTurnNumber') ? intval(GetTurnNumber()) : 0,
        'phase'  => function_exists('GetCurrentPhase') ? strval(GetCurrentPhase()) : '',
        'turn'   => function_exists('GetTurnPlayer') ? intval(GetTurnPlayer()) : 0,
        'init'   => function_exists('GetInitiativeCounter') ? strval(GetInitiativeCounter() ?? '') : '',
        'seat'   => $actionSeat,
        'actor'  => $actor,
        'action' => ['kind' => strval($action['kind'] ?? 'other'),
                     'card' => strval($action['card'] ?? ''),
                     'mz'   => strval($action['mz'] ?? '')],
        'seats'  => ['1' => _SWUBotDataSeat(1, $botSeats), '2' => _SWUBotDataSeat(2, $botSeats)],
    ];
}
