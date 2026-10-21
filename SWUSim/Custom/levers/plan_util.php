<?php
// Helpers for the tempo DECK PLANS (feature group p59, spec docs/superpowers/specs/2026-10-20-swusim-tempo-deck-plans-design.md). Registers no
// lever. Mother Talzin (LOF_002): "deploy on 5" (owner, 2026-10-20) — the safety checks below only time the deploy WITHIN that round.

const SWU_BOT_TALZIN_UPGRADES = ['LOF_261' => 3, 'ASH_055' => 2];   // Constructed Lightsaber, Blade of Talzin

// 'red' / 'yellow' / 'other' by the base's aspect (Aggression / Cunning); '' when my leader is not Talzin.
function _SWUBotTalzinColour(int $seat): string {
    if (function_exists('SeatCountForGame') && SeatCountForGame() > 2) return '';   // 2-player only (spec): every Talzin plan is inert in Twin Suns
    if (strval((GetLeader($seat)[0] ?? null)->CardID ?? '') !== 'LOF_002') return '';
    $aspect = strval(CardAspect(strval((GetBase($seat)[0] ?? null)->CardID ?? '')) ?? '');
    if (str_contains($aspect, 'Aggression')) return 'red';
    if (str_contains($aspect, 'Cunning')) return 'yellow';
    return 'other';
}
function _SWUBotTalzinUnit(int $seat): ?array {
    foreach (SWUBotUnits($seat) as $u) if (!empty($u['isLeader']) && $u['cardID'] === 'LOF_002') return $u;
    return null;
}
// Ready resources to keep for her upgrades on the flip turn: the upgrades I hold, up to 5 (owner: "at least 2 resources for at minimum the
// Blade of Talzin. but ideally hold all 5 resources to play both upgrades").
function _SWUBotTalzinReserve(int $seat): int {
    $r = 0;
    foreach (_SWUBotHandCards($seat) as $cid) $r += SWU_BOT_TALZIN_UPGRADES[$cid] ?? 0;
    return min(5, $r);
}
// Safe to flip now: no ready enemy unit hits as hard as her HP ("wait until it's already attacked"), and the opponent has spent their
// resources ("better to wait until they use resources before deploying" — Ambush answers): 1 or fewer ready resources + Credits left.
function _SWUBotTalzinSafe(int $seat): bool {
    $hp = intval(CardHp('LOF_002'));
    foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready']) && intval($e['attackPower']) >= $hp) return false;
    $opp = SWUBotOpponent($seat);
    $left = SWUResourceCount($opp, true) + (function_exists('SWUUsableCreditTokenMzIDs') ? count(SWUUsableCreditTokenMzIDs($opp)) : 0);
    return $left <= 1;
}
// Flip anyway, before the round is wasted: the opponent just passed or claimed the initiative, or I have nothing else WORTH doing — no
// attack or play (her upgrades aside) that scores above PASS. Held plays must not count: the reserve hold below caps them at -0.4, and
// then the 0.05 initiative claim beat the waiting deploy and ended her round (smoke 2026-10-21, 4 of 10 games flipped a round late).
function _SWUBotTalzinForced(array $ctx, int $seat): bool {
    $opp = SWUBotOpponent($seat);
    if (function_exists('GetSWUVar') && intval(GetSWUVar('PASS', '0')) >= 1) return true;
    if (function_exists('GetInitiativeCounter') && strval(GetInitiativeCounter()) === 'P' . $opp . '_CLAIMED') return true;
    foreach ((array)($ctx['actions'] ?? []) as $a) {
        $k = SWUBotActionKind($a);
        if ($k === 'play') {
            $o = _SWUBotHandObject($seat, $a);
            if ($o === null || isset(SWU_BOT_TALZIN_UPGRADES[strval($o->CardID ?? '')])) continue;
        } elseif ($k !== 'attack') continue;
        if (_SWUBotLvScoresAbovePass($ctx, $a)) return false;
    }
    return true;
}
// Is $action Talzin's (front-side) leader Action / her deploy?
function _SWUBotTalzinAction(int $seat, array $action, string $kind): bool {
    if (SWUBotActionKind($action) !== $kind) return false;
    $l = _SWUBotLeaderOfAction($seat, $action);
    return $l !== null && strval($l->CardID ?? '') === 'LOF_002';
}
