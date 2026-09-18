<?php
// Layer 1 of the RL bots spec's decision stack — the STYLE RULE — plus the action helpers every later layer
// shares, and the per-style weight tables the fallback scorer uses.
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2 ("Layer 1", "Layer 4").
//
// The style rule is a FILTER on ATTACK-TARGET candidates only: it narrows, it never picks. Whether and when
// to attack is left to later layers. Lethal (layer-2 rule 2) runs BEFORE this filter — a Control bot must
// still take a winning base attack (plan ruling, recorded in the spec).
//   Aggro   — the enemy base, or a unit its attacker defeats with Overwhelm (the excess still hits the base)
//   Control — an enemy unit whenever one is legal; never the base while a unit is available — unless it is racing
//             (SWUBotIsRacing), when it takes Aggro's rule (feature 'baserace'; owner ruling 2026-09-14)
//   Normal  — racing (SWUBotIsRacing): Aggro's rule; otherwise favourable trades only, if any exist

require_once __DIR__ . '/BotArchetypes.php';   // the five archetypes, the rank scale and the weight table

function SWUBotActionMz(array $action): string {
    if (intval($action['mode'] ?? 0) === 100) return '';
    return explode('!', strval($action['cardID'] ?? ''))[0];
}

// The wire forms come from SWUBotFreePlayActions() (SWUSim/BotLegalActions.php); mode-100 actions are
// answers to a pending decision.
function SWUBotActionKind(array $action): string {
    if (intval($action['mode'] ?? 0) === 100) return 'answer';
    $c = strval($action['cardID'] ?? '');
    if ($c === 'myHealth-0!CustomInput!Pass') return 'pass';
    if ($c === 'InitiativeCounter-0!CustomInput!TakeInitiative') return 'initiative';
    if (preg_match('/^myHand-\d+!FSM!$/', $c)) return 'play';
    if (preg_match('/^my(Ground|Space)Arena-\d+!FSM!$/', $c)) return 'attack';
    if (str_contains($c, '!CustomInput!DeployLeader')) return 'deploy';
    if (str_ends_with($c, '!CustomInput!LeaderAbility')) return 'leader-ability';
    if (str_ends_with($c, '!CustomInput!EpicAction')) return 'base-epic';
    if (str_ends_with($c, '!CustomInput!Activate')) return 'unit-action';
    return 'answer';
}

// How many items a multi-choose candidate selects: "PASS" / "" / "-" select nothing, otherwise the
// '&'-joined picks (the bridge's MZMULTICHOOSE encoding).
function SWUBotSelectionCount(array $action): int {
    $c = strval($action['cardID'] ?? '');
    if ($c === '' || $c === 'PASS' || $c === '-') return 0;
    return count(explode('&', $c));
}

// The attacker of a pending attack-target decision, read from the continuation queued behind it
// ("SWUResolveAttack|<attackerMz>", CombatLogic ~:4090).
function SWUBotAttackerMz(array $ctx): ?string {
    foreach ((array)($ctx['following'] ?? []) as $p) {
        if (str_starts_with(strval($p), 'SWUResolveAttack|')) return explode('|', strval($p))[1] ?? null;
    }
    return null;
}

// What $att could attack right now: same-arena enemy units and the base, Sentinel-restricted the way the
// engine restricts them (CR 6.3.2b; Saboteur ignores Sentinel, CR 7.5.10). Used to value an attack at
// FREE-PLAY time, before the engine has raised the target prompt.
function SWUBotAttackTargets(int $seat, array $att): array {
    $opp = SWUBotOpponent($seat);
    $inArena = array_values(array_filter(SWUBotUnits($opp), fn($v) => $v['arena'] === $att['arena']));
    $sentinels = array_values(array_filter($inArena, fn($v) => $v['sentinel']));
    if (!empty($sentinels) && !$att['saboteur']) return ['base' => false, 'units' => $sentinels];
    return ['base' => true, 'units' => $inArena];
}

// The targets the style rule allows for $att, as [kind, view|null] pairs ('base' or 'unit'). The free-play
// twin of SWUBotStyleFilter, so free-play valuation and the target prompt can never disagree. Never empty
// when a target exists: with no qualifying target the choice is left open.
function SWUBotAllowedTargets(array $ctx, array $att): array {
    $t = SWUBotAttackTargets(intval($ctx['seat']), $att);
    // The filter and the weights now agree by construction: both read SWUBotRacingRank.
    $rank = SWUBotRacingRank(strval($ctx['style']), intval($ctx['seat']));
    $mode = $rank <= 1 ? 'aggro' : ($rank === 2 ? 'normal-trade' : 'control');
    $all = [];
    if ($t['base']) $all[] = ['base', null];
    foreach ($t['units'] as $u) $all[] = ['unit', $u];
    // The filter never removes the BASE by preference — that exclusion was the bug. Until 2026-09-17 the control
    // branch kept `unit` targets only, so a control bot could not choose the base at all unless it was racing, and
    // racing (clock <= 3) was true in ~7% of its decisions. Measured: control attacked the base in 55.0% of its
    // attacks against aggro's 92.5%, and 462 of its freely-chosen unit attacks killed nothing at all. The weights
    // decide between base and unit now (base 0.60 flat, chip strictly below it).
    // Each archetype KEEPS its own unit-side pruning (owner, 2026-09-18): the defect was control's base exclusion,
    // not its unit preferences, and widening further would change aggro and midrange unmeasured.
    // SWUBotAttackTargets above has already removed whatever the RULES forbid (Sentinel without Saboteur).
    $keep = [];
    foreach ($all as [$k, $u]) {
        if ($k === 'base') { $keep[] = [$k, $u]; continue; }
        if ($mode === 'aggro') {
            if (!SWUBotOverwhelmKills($att, $u)) continue;          // aggro takes a unit only when Overwhelm carries
        } elseif ($mode === 'normal-trade') {
            $o = SWUBotCombatOutcome($att, $u);                      // midrange: kill-and-survive, or trade up
            if ($o !== 'kill-survive' && !($o === 'trade' && $u['cost'] > $att['cost'])) continue;
        }
        // the control wing (rank >= 3) keeps every unit
        $keep[] = [$k, $u];
    }
    return empty($keep) ? $all : $keep;
}

// Attack candidates with at least one allowed target that is not a losing trade (the attacker dies and the
// defender survives). The base is never a losing trade.
function SWUBotFreeAttacks(array $ctx): array {
    $out = [];
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'attack') continue;
        $att = SWUBotViewForMz(intval($ctx['seat']), SWUBotActionMz($a));
        if ($att === null) continue;
        foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
            if ($k === 'base' || SWUBotCombatOutcome($att, $u) !== 'die') { $out[] = $a; break; }
        }
    }
    return $out;
}

function SWUBotStyleFilter(array $ctx): array {
    $acts = $ctx['actions'];
    if (($ctx['kind'] ?? '') !== 'decision' || ($ctx['tooltip'] ?? '') !== 'Choose_an_attack_target') return $acts;
    $attMz = SWUBotAttackerMz($ctx);
    $att = $attMz !== null ? SWUBotViewForMz(intval($ctx['seat']), $attMz) : null;
    if ($att === null) return $acts;
    $allowedUids = []; $baseAllowed = false;
    foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
        if ($k === 'base') $baseAllowed = true; else $allowedUids[$u['uid']] = true;
    }
    $keep = [];
    foreach ($acts as $a) {
        $c = strval($a['cardID'] ?? '');
        if (str_contains($c, 'Base-')) { if ($baseAllowed) $keep[] = $a; continue; }
        $v = SWUBotViewForMz(intval($ctx['seat']), $c);
        if ($v !== null && isset($allowedUids[$v['uid']])) $keep[] = $a;
    }
    return empty($keep) ? $acts : $keep;   // never empty
}

