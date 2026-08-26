<?php
// HMW_185
// Cost 5 - Ty Yorrick, Monster Hunter - [Aggression] - Unit (Ground) 4/5 - unique
// Traits: Force, Bounty Hunter
// Text: If a friendly ability would deal damage, you may have that ability deal that much damage plus 1
//       instead.
//       On Attack: You may deal 1 damage to a Creature unit.
//
// ── Clause 1: "If a friendly ability would deal damage, you may have that ability deal that much
//    damage plus 1 instead." ────────────────────────────────────────────────────────────────────────
// The OFFER lives in GameLogic beside the damage funnels (_SWUHmw185Decider / _SWUHmw185Defer /
// SWUOfferSplitDamage) because it has to be raised from four different funnels; only the CONTINUATIONS
// that resume each of them are here. Each funnel defers the whole damage instance behind a YESNO and
// re-enters itself with the amount adjusted and its own offer suppressed, so the +1 lands before every
// prevention — it changes what the ability DEALS, not what the target takes.
//
// USER RULINGS 2026-08-26:
//   • it fires every time a trigger/ability deals damage, not once per ability resolution;
//   • divided damage takes the +1 on the POOL (SOR_135's 6 becomes 7 to divide), not per share;
//   • it STACKS with JTL_165 Hunting Aggressor on indirect damage.
// Combat damage is excluded — it is not an ability.

// Resume single-target unit damage. parts: uid | amount | dealer | sourceToken | skipPrevent
// $player here is TY'S CONTROLLER (the decision's owner), which is not necessarily the dealer — they
// differ in Team Suns, where a teammate's ability is still "a friendly ability". Everything is
// re-resolved under the DEALER's frame.
$customDQHandlers["HMW_185#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $uid    = intval($parts[0] ?? 0);
    $amount = intval($parts[1] ?? 0);
    $dealer = intval($parts[2] ?? 0);
    $srcTok = (string)($parts[3] ?? '');
    $skipPrevent = intval($parts[4] ?? 0) === 1;
    if (_SWUHmw185Accepted($lastDecision)) $amount += 1;
    if ($amount <= 0 || $dealer <= 0) return;
    $playerID = $dealer;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;                       // target left play while the question was pending
    $srcMz = null;
    if ($srcTok !== '') {
        $srcMz = ($srcTok[0] === 'U') ? SWUFindMzByUID(intval(substr($srcTok, 1))) : substr($srcTok, 1);
    }
    SWUDealDamageToUnit($mz, $amount, $dealer, $srcMz, $skipPrevent, true);
};

// Resume indirect damage. parts: controller | amount | damagedPlayer | thenHandler | sourceUID
$customDQHandlers["HMW_185#1"] = function($player, $parts, $lastDecision) {
    $controller = intval($parts[0] ?? 0);
    $amount     = intval($parts[1] ?? 0);
    $damaged    = intval($parts[2] ?? 0);
    $then       = (string)($parts[3] ?? '');
    $srcUID     = intval($parts[4] ?? 0);
    if (_SWUHmw185Accepted($lastDecision)) $amount += 1;
    if ($controller <= 0 || $damaged <= 0) return;
    SWUDealIndirectDamage($controller, $amount, $damaged, $then, $srcUID, true);
};

// Resume divided damage. parts: player | amount | upto | tooltip | targets (~-joined)
$customDQHandlers["HMW_185#2"] = function($player, $parts, $lastDecision) {
    $dealer  = intval($parts[0] ?? 0);
    $amount  = intval($parts[1] ?? 0);
    $upto    = intval($parts[2] ?? 0) === 1;
    $tooltip = (string)($parts[3] ?? '');
    $targets = array_values(array_filter(explode('~', (string)($parts[4] ?? ''))));
    if (_SWUHmw185Accepted($lastDecision)) $amount += 1;
    if ($dealer <= 0) return;
    global $playerID; $playerID = $dealer;   // the pool's mzIDs are in the DEALER's frame
    SWUOfferSplitDamage($dealer, $amount, $targets, $tooltip, $upto, true);
};

// Resume base damage. parts: dealer | amount | targetPlayer | damagerToken (U<uid> / P<seat> / N)
$customDQHandlers["HMW_185#3"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $dealer  = intval($parts[0] ?? 0);
    $amount  = intval($parts[1] ?? 0);
    $target  = intval($parts[2] ?? 0);
    $tok     = (string)($parts[3] ?? 'N');
    if (_SWUHmw185Accepted($lastDecision)) $amount += 1;
    if ($amount <= 0 || $target <= 0) return;
    $damager = null;
    if ($tok !== '' && $tok[0] === 'P') $damager = intval(substr($tok, 1));
    elseif ($tok !== '' && $tok[0] === 'U') {
        $playerID = $dealer;
        $m = SWUFindMzByUID(intval(substr($tok, 1)));
        $damager = ($m !== null) ? GetZoneObject($m) : null;
    }
    // Restore the dealer's frame so the funnel infers the same dealer it did the first time when the
    // token carried nothing (its fallback reads the ambient $playerID).
    $playerID = $dealer;
    SWUDealDamageToBase($amount, $target, $damager, false, true);
};

// ── On Attack ────────────────────────────────────────────────────────────────
// "You may deal 1 damage to a Creature unit."
//   • MZMAYCHOOSE, not a mandatory choose — both because the text says "You may" and because a
//     mandatory multi-target MZCHOOSE queued directly in an OnAttack closure auto-resolves to nothing
//     (OnAttackTrigger restores $playerID before MZCountChoices runs).
//   • "a Creature unit" is unqualified: no friendly/enemy restriction and no arena restriction, so the
//     pool spans all four arenas. Ty himself is Force / Bounty Hunter and is excluded on the TRAIT, not
//     by a self-exclusion — the text says "a Creature unit", not "another".
//   • TraitContains (object-aware), so a granted or suppressed Creature trait is honoured.
//   • An optional clause that could only fizzle must not be offered at all. No guard is written here
//     for that: SWUQueueMayChooseTarget already returns on an empty target list (GameLogic ~1719).
//     An `if (empty($targets)) return;` in front of it was measured as a no-op — removing it changed
//     nothing in the suite — so it is deliberately absent rather than kept as a redundant line with a
//     comment implying this card enforces the rule itself. Guarded by NoCreatureInPlay_NoOfferAtAll.
//   • No SWUAfterAction — combat owns the action end.
$onAttackAbilities["HMW_185:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $targets = _SWUCollectUnits(0, fn($o) => TraitContains($o, 'Creature'));
    SWUQueueMayChooseTarget(intval($player), $targets,
        'Deal_1_damage_to_a_Creature_unit?',
        'Deal_1_damage_to_a_Creature_unit',
        'DEAL_UNIT_DAMAGE|1');
};
