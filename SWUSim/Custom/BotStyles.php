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
    $style = $ctx['style'];
    if ($style === 'normal') $style = SWUBotIsRacing(intval($ctx['seat']), intval($ctx['opp'])) ? 'aggro' : 'normal-trade';
    // Control races like Aggro when it is ahead (feature 'baserace'; owner ruling 2026-09-14).
    if ($style === 'control' && SWUBotFeatureOn('baserace') && SWUBotIsRacing(intval($ctx['seat']), intval($ctx['opp']))) $style = 'aggro';
    $all = [];
    if ($t['base']) $all[] = ['base', null];
    foreach ($t['units'] as $u) $all[] = ['unit', $u];
    $keep = [];
    foreach ($all as [$k, $u]) {
        if ($style === 'aggro') {
            if ($k === 'base' || SWUBotOverwhelmKills($att, $u)) $keep[] = [$k, $u];
        } elseif ($style === 'control') {
            if ($k === 'unit') $keep[] = [$k, $u];
        } else { // normal-trade: kill-and-survive, or a trade into something that cost more
            if ($k !== 'unit') continue;
            $o = SWUBotCombatOutcome($att, $u);
            if ($o === 'kill-survive' || ($o === 'trade' && $u['cost'] > $att['cost'])) $keep[] = [$k, $u];
        }
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

// Starting values for the fallback scorer (layer 4). Tuned later against self-play reports, the difficulty
// calibration and the RL warm start — not fixed facts.
function SWUBotWeights(string $style, int $seat): array {
    static $T = [
        //                aggro normal control
        'base'      => [1.0, 0.6, 0.3],   // per point of damage to the enemy base
        'kill'      => [0.6, 1.0, 1.2],   // × value of an enemy unit killed
        'loss'      => [0.5, 0.8, 1.0],   // × value of own unit lost
        'chip'      => [0.2, 0.3, 0.4],   // per point of non-lethal damage to a unit
        'grit'      => [0.3, 0.3, 0.3],   // penalty per point of non-lethal damage to an enemy Grit unit
        'develop'   => [0.3, 0.3, 0.25],  // × printed cost of a card played
        'unitPlay'  => [0.5, 0.0, 0.0],   // flat bonus for playing a unit (Aggro: units entering play)
        'removal'   => [0.5, 0.8, 1.2],   // tag bonuses for plays (SWUBotCardTags)
        'wipe'      => [0.0, 0.3, 1.5],
        'damage'    => [0.4, 0.6, 0.8],
        'draw'      => [0.3, 0.4, 1.2],   // Control maximises card draw early and mid game (owner, 2026-09-13); × SWUBotDrawMultiplier
        'heal'      => [0.1, 0.3, 0.6],
        'burn'      => [0.6, 0.4, 0.3],   // indirect / base damage from a card (tags v2, 2026-09-14)
        'buff'      => [0.5, 0.4, 0.3],
        'bounce'    => [0.3, 0.5, 0.6],
        'exhaust'   => [0.3, 0.4, 0.4],
        'deploy'    => [1.5, 1.5, 1.5],
        'ability'   => [0.4, 0.4, 0.4],   // leader / unit / base Action
        'ready'     => [0.3, 0.3, 0.3],   // guide: buffs and upgrades go on units that attack this round
        'initiative'=> [0.05, 0.05, 0.05],
        // GUIDES (BotGuides.php) — large enough to reproduce the layer-2 rules they replaced, in their old
        // priority (attack before developing beat Aggro's max-units), while staying learnable in training.
        'attackFirst' => [6.0, 6.0, 6.0], // a free attack when no play on offer could improve it (was rule 6)
        'maxUnits'    => [4.0, 0.0, 0.0], // Aggro: the play from the affordable set with the most units (was rule 7)
        'stopPass'    => [1.5, 1.5, 1.5], // at the resource stop, above the floor: skip the regroup resource (rule 11, a guide)
    ];
    $col = ['aggro' => 0, 'normal' => 1, 'control' => 2][$style] ?? 1;
    if ($style === 'normal' && SWUBotIsRacing($seat, SWUBotOpponent($seat))) $col = 0;   // Normal races like Aggro
    $out = [];
    foreach ($T as $k => $v) $out[$k] = $v[$col];
    return $out;
}

