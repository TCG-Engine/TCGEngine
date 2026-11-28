<?php
// HMW_035
// Cost 6 - Hunter - Everyone Get to Cover! - [Command,Vigilance,Heroism] - Power 4 - HP 7 - Trait: Clone
// Text: When Played: Choose two. You may choose the same option more than once:
//       • Give a Shield token to a unit.
//       • Attack with a unit, even if it's exhausted. It can't attack bases for this attack.

// ─── HMW_035 Hunter — the modal ────────────────────────────────────────────────────────────────────
// "Choose two" is MANDATORY (neither mode prints "may"), and the one "may" on the card attaches to the
// REPEAT. That rules out the shared SWUQueueModalChoose/MODAL_CHOOSE driver the SOR aspect events use:
// its handler filters the chosen label out of the next pick, which is correct for a plain "Choose two"
// and exactly wrong here. This card therefore runs its own two-step chain, in the same shape.
//
// The other reason it can't be the shared driver: the mode list must be RECOMPUTED BEFORE EACH PICK.
// The first pick can attack and remove the last enemy unit, which makes the attack mode illegal for the
// second — so the next picker is queued as a CUSTOM and builds its labels at DRAIN time, never from a
// snapshot taken when the ability started (the SEC_143 queued-builder shape).
//
// Both modes are printed elsewhere verbatim and reuse those cards' pieces:
//   • "Give a Shield token to a unit"  — SOR_058's Shield mode. "a unit" carries no controller
//     qualifier, so per CR it spans BOTH sides (friendlyOnly: false).
//   • "Attack with a unit, even if it's exhausted. It can't attack bases for this attack." — SOR_110
//     Frontline Shuttle, word for word: SWUUnitsWithNonBaseAttackTarget + BeginSWUAttack(noBases: true).

// The modes that can currently do something, in printed order. Recomputed per pick.
function _SWUHmw035Modes(int $player): array {
    $modes = [];
    // Shield — legal whenever ANY unit is in play, either side. In practice always true during Hunter's
    // own When Played (Hunter is itself a unit and a legal target), but the check keeps the mode honest
    // if this is ever reached from a board with nothing on it.
    if (!empty(SWUAllUnits('my')) || !empty(SWUAllUnits('their'))) $modes[] = 'Shield';
    // Attack — only when some friendly unit actually has a NON-BASE target. This is not a nicety: an
    // attack mode offered into an empty enemy board is a FIZZLE-ONLY option, and BeginSWUAttack readies
    // the attacker for the "even if it's exhausted" clause BEFORE it looks for a target, so choosing one
    // and aborting hands the player a free ready (the LAW_065 4-LOM bug). Filtering the MODE is the fix;
    // SWUUnitsWithNonBaseAttackTarget is the same helper SOR_110 gates on, and it deliberately does NOT
    // filter by readiness — an exhausted unit is a legal attacker here.
    if (!empty(SWUUnitsWithNonBaseAttackTarget($player))) $modes[] = 'Attack';
    return $modes;
}

// Offer pick number (3 - $picksLeft) of 2. $block escalates per pick so a mode's own sub-decisions
// (which sit at $block) fully resolve before the next picker is reached.
function _SWUHmw035Pick(int $player, int $picksLeft, int $block): void {
    if ($picksLeft <= 0) return;
    global $playerID; $playerID = intval($player);
    $modes = _SWUHmw035Modes($player);
    if (empty($modes)) return;
    // Only one mode can do anything — resolve it rather than asking a question with one answer. Same
    // habit as a single-legal-target MZCHOOSE auto-resolving, and the house rule against "use it
    // anyway?" prompts. The player still chose it twice; there was simply nothing else to choose.
    if (count($modes) === 1) { _SWUHmw035Resolve($player, $modes[0], $picksLeft, $block); return; }
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", implode("&", $modes), $block,
        tooltip: "Choose_a_mode_(you_may_repeat)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HMW_035#0|{$picksLeft}|{$block}", $block);
}

// Resolve one chosen mode, then queue the NEXT picker as a CUSTOM so its own mode list is rebuilt from
// the post-effect board.
function _SWUHmw035Resolve(int $player, string $label, int $picksLeft, int $block): void {
    global $playerID; $playerID = intval($player);
    if ($label === 'Shield') {
        GiveTokenUpgrade($player, '', [
            'token'        => 'SHIELD',
            'friendlyOnly' => false,          // "a unit" — enemy units included (CR default)
            'block'        => $block,
            'prompt'       => "Give_a_Shield_token_to_a_unit",
        ]);
    } elseif ($label === 'Attack') {
        // Mandatory choose: the card says "Attack with a unit", not "you MAY attack with a unit" — the
        // SOR_240 Fleet Lieutenant ruling (pass allowed at the attacker stage) turns on that printed
        // "may" and does not reach this card. A lone legal attacker auto-resolves via PASSPARAMETER.
        $attackers = SWUUnitsWithNonBaseAttackTarget($player);
        if (!empty($attackers)) {
            // ⚠ The NEXT pick is NOT queued here: it waits for this attack to COMPLETE (HMW_035#2 arms it; combat fires it from
            // CollectAfterAttackTriggers). Queued here, it sat ahead of the attack's combat step whenever the attack paused for a
            // decision (an On Attack "may", e.g. deployed Admiral Holdo) — the second mode was chosen mid-attack, a second attack
            // started inside the first, and the first one's combat damage was lost (bug report P-37, game 8008).
            SWUQueueChooseTarget($player, $attackers,
                "Attack_with_a_unit_(it_can't_attack_bases_for_this_attack)", "HMW_035#2|" . ($picksLeft - 1), $block);
            return;
        }
    }
    $left = $picksLeft - 1;
    if ($left > 0) {
        DecisionQueueController::AddDecision($player, "CUSTOM",
            "HMW_035#1|{$left}|" . ($block + 1), $block + 1);
    }
}

// ── Step 0: a mode was chosen from the OPTIONCHOOSE ────────────────────────────────────────────────
$customDQHandlers["HMW_035#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    _SWUHmw035Resolve(intval($player), (string)$lastDecision, intval($parts[0] ?? 0), intval($parts[1] ?? 1));
};

// ── Step 1: build the NEXT picker (deferred so the labels see the post-effect board) ───────────────
$customDQHandlers["HMW_035#1"] = function($player, $parts, $lastDecision) {
    _SWUHmw035Pick(intval($player), intval($parts[0] ?? 0), intval($parts[1] ?? 1));
};

// ── Step 2: the attacker was chosen ────────────────────────────────────────────────────────────────
// noBases: true is "It can't attack bases for this attack" — scoped to this one attack, so no marker is
// left on the unit afterwards (contrast JTL_092's phase-scoped CANT_ATTACK_BASES turn effect).
// Combat owns the after-action: this attack happens inside a When Played, where BeginSWUAttack skips
// SWUAfterAction in trigger-resume mode and SWU_TRIGGER_RESUME closes the action.
// $parts[0] = the picks still to make after this one. With one left, the next pick is armed for AFTER this attack: combat's
// CollectAfterAttackTriggers matches the attacker's UID (on the normal AND the fizzle path) and queues it then.
$customDQHandlers["HMW_035#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $left = intval($parts[0] ?? 0);
    $atk = SWUDecisionDeclined($lastDecision) ? null : GetZoneObject((string)$lastDecision);
    if (SWUObjGone($atk)) {
        // No attack after all: nothing to wait for, so the next pick follows directly.
        if ($left > 0) _SWUHmw035Pick(intval($player), $left, 1);
        return;
    }
    if ($left > 0) SetSWUVar('SWU_HMW035_NEXT_PICK', strval(intval($atk->UniqueID ?? 0)));
    BeginSWUAttack(intval($player), (string)$lastDecision, noBases: true);
};

// Fired by combat once the armed attack has completed (see CollectAfterAttackTriggers / DispatchTrigger 'HMW_035_NEXT').
function _SWUHmw035AfterAttack(int $player): void {
    _SWUHmw035Pick($player, 1, 1);
}

$whenPlayedAbilities["HMW_035:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    _SWUHmw035Pick(intval($player), 2, 1);
};
