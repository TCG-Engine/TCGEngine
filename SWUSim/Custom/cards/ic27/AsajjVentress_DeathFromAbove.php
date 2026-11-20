<?php
// IC27_147
// Cost 6 - Asajj Ventress - Death From Above - [Cunning,Villainy] - Unit (Ground) 5/7 (unique)
//   Traits: Force, Separatist, Sith
// Text: If a friendly non-leader ground unit would deal combat damage while attacking a base, you may reveal this
//       unit from your hand and pay [5 resources Cunning Villainy]. If you do, return the attacker to its owner's hand
//       and put this unit into play as the attacker. She deals combat damage to that base instead.
//
// OWNER DESIGN (2026-11-03) — set up at declaration, re-checked at damage ("doubly-checked lock"):
//   1. DECLARATION (_SWUIc27147OfferAtDeclaration, called by CombatLogic wherever a BASE becomes the attack target):
//      for a friendly non-leader ground attacker, a seat that holds Asajj and can pay is asked Attack / Asajj_Ventress.
//      Choosing her only ARMS it (SWU_IC27147_ARMED = "seat|attackerUID") — nothing is paid or revealed. A prompt at
//      damage time instead would add a pause to every base attack only while Asajj is held, leaking her.
//   2. DAMAGE (_SWUIc27147CommitAtDamage, at the top of the SWUCombatDamage handler — the true damage commit, after
//      every pending On Attack / On Defense decision has resolved. NOT _SWUOfferCombatPreventions: that seam can run
//      while another seat's decision (Condemn's disclose) is still open): the replacement happens only if it is STILL legal — the armed
//      attacker is still the attacker, still in play, would deal > 0 combat damage, Asajj is still in hand and still
//      affordable. Otherwise it lapses silently (Condemn's -6/-0, a removed attacker, …). On commit: reveal, pay, return
//      the attacker to its OWNER's hand (SWUBounceUnit: upgrades discarded, tokens set aside), put Asajj into play
//      EXHAUSTED under the holder's control, and hand her to SWUCombatDamage as the attacker (SWU_IC27147_SWAP). The
//      original's OWN abilities (printed Raid, "when this unit deals combat damage") stay with it (CR 7.5.d).
// OWNER RULINGS (2026-11-03, with an unofficial judge ruling): she is put into play "AS THE ATTACKER", so what the
// MODIFIED "ATTACK WITH A UNIT" put on the attacker moves to her — "+N/+0 for this attack" (LAW_202 Commence, LAW_001
// Saw, LAW_205, SOR_150), abilities granted for this attack (SOR_150's self-defeat), Babu Frik's HP-as-damage, and the
// post-attack riders (IC27_007, ASH_184 Follow Me, LAW_001's / LAW_205's "defeat it/that unit"). A LASTING EFFECT that
// another ability applies to the unit during the attack stays with it — Condemn's disclosed -6/-0, Dryden Vos's own
// "double this unit's power", Condemn's "loses all other abilities" — as do its printed abilities and phase buffs.
// The line is drawn by TIME: the attack-starting effect stamps its riders BEFORE the base is declared, and every On Attack
// / attack-start effect comes after. So the declaration snapshots the attacker's attack-duration effects
// (SWU_IC27147_CARRY) and only those move.
// One replacement per attack (CR 7.5.e/f): the arm is consumed at the commit, so a second copy in hand never applies.
// "friendly" spans the team (owner ruling): a teammate holding Asajj is asked on your attack; she enters under the
// HOLDER's control.

// True for a TurnEffects token that lasts only for this attack. The registry's attack-duration tokens, plus the
// legacy unregistered "+N for this attack" one-shot that SWUCombatDamage consumes inline.
function _SWUIc27147IsAttackScoped(string $te): bool {
    if (preg_match('/^SWU_ATK_POWER_\d+$/', $te)) return true;
    $p = SWUParseTurnEffect($te);
    return SWUEffectKind($p['base']) !== null && $p['duration'] === SWU_DUR_ATTACK;
}

// The attacker's attack-duration effects at DECLARATION — the modified attack's riders (see the header).
function _SWUIc27147AttackRiders($a): string {
    $out = [];
    foreach (($a->TurnEffects ?? []) as $te) {
        if (_SWUIc27147IsAttackScoped((string)$te)) $out[] = (string)$te;
    }
    return implode(',', $out);
}

function _SWUIc27147Cost(int $seat): int {
    return 5 + SWUAspectPenalty($seat, 'IC27_147');
}

// Hand index of the first live Asajj (Death From Above) in $seat's hand, or -1.
function _SWUIc27147HandIdx(int $seat): int {
    foreach (GetHand($seat) as $i => $c) {
        if (empty($c->removed) && ($c->CardID ?? '') === 'IC27_147') return $i;
    }
    return -1;
}

function _SWUIc27147CanSetUp(int $seat): bool {
    return _SWUIc27147HandIdx($seat) >= 0 && SWUTotalPaymentCapacity($seat) >= _SWUIc27147Cost($seat);
}

// "a friendly non-leader ground unit" — the unit side only: a deployed leader is a leader unit; a vehicle carrying a
// leader pilot is not.
function _SWUIc27147EligibleAttacker($a): bool {
    if (SWUObjGone($a)) return false;
    if ((string)($a->Location ?? '') !== 'GroundArena') return false;
    return strpos((string)(CardType($a->CardID ?? '') ?? ''), 'Leader') === false;
}

// The combat damage the attacker would deal to a base. Mirrors SWUCombatDamage's base-relevant terms — current power,
// Raid (not while it has lost its abilities, e.g. Condemn), "+N for this attack", and the HP-as-damage replacement —
// floored at 0. The vs-UNIT modifiers there (damaged defender, Grit, …) never apply to a base attack.
function _SWUIc27147AttackerBaseDamage($a): int {
    $p = intval(ObjectCurrentPowerRaw($a));
    $raid = LostAbilities($a) ? null : GetKeyword_Raid_Value($a);
    if ($raid !== null && $raid > 0) $p += $raid;
    foreach (($a->TurnEffects ?? []) as $te) {
        if (preg_match('/^SWU_ATK_POWER_(\d+)$/', (string)$te, $m)) $p += intval($m[1]);
    }
    if (in_array('SWU_HP_AS_DAMAGE', (array)($a->TurnEffects ?? []), true)) {
        $p = intval(ObjectCurrentHP($a)) - intval($a->Damage ?? 0);
    }
    return max(0, $p);
}

// "would deal combat damage"
function _SWUIc27147WouldDealCombatDamage($a): bool {
    return _SWUIc27147AttackerBaseDamage($a) > 0;
}

// The seats friendly to $atkSeat (itself first, then a teammate), in asking order.
function _SWUIc27147FriendlySeats(int $atkSeat): array {
    $out = [$atkSeat];
    foreach (GetLiveSeatsArray() as $s) {
        if (intval($s) !== $atkSeat && !SWUIsEnemySeat($atkSeat, intval($s))) $out[] = intval($s);
    }
    return $out;
}

// ── 1. Declaration ─────────────────────────────────────────────────────────────────────────────────────────────
// Returns true when the choice was queued — its continuation runs ExecuteSWUAttack — and false when the caller should
// run ExecuteSWUAttack itself. Always clears any stale arm first.
function _SWUIc27147OfferAtDeclaration(int $player, string $attackerMzID, string $targetMzID): bool {
    SetSWUVar('SWU_IC27147_ARMED', '');
    if (strpos($targetMzID, 'Base') === false) return false;
    global $playerID; $saved = $playerID; $playerID = $player;
    $a = GetZoneObject($attackerMzID);
    $playerID = $saved;
    if (!_SWUIc27147EligibleAttacker($a)) return false;
    $uid = intval($a->UniqueID ?? 0);
    foreach (_SWUIc27147FriendlySeats($player) as $seat) {
        if (!_SWUIc27147CanSetUp($seat)) continue;
        $cost = _SWUIc27147Cost($seat);
        SetSWUVar('SWU_IC27147_CARRY', _SWUIc27147AttackRiders($a));
        DecisionQueueController::AddDecision($seat, 'OPTIONCHOOSE', 'Attack&Asajj_Ventress', 1,
            tooltip: "Reveal_Asajj_Ventress_and_pay_{$cost}_to_have_her_deal_this_attack's_damage?");
        DecisionQueueController::AddDecision($seat, 'CUSTOM', "IC27_147#0|{$player}|{$attackerMzID}|{$targetMzID}|{$uid}", 1);
        return true;
    }
    return false;
}

$customDQHandlers["IC27_147#0"] = function($player, $parts, $lastDecision) {
    $atkSeat = intval($parts[0] ?? 0);
    SetSWUVar('SWU_IC27147_ARMED', $lastDecision === 'Asajj_Ventress' ? (intval($player) . '|' . intval($parts[3] ?? 0)) : '');    ExecuteSWUAttack($atkSeat, (string)($parts[1] ?? ''), (string)($parts[2] ?? ''));
    // A TEAMMATE answered (Team Suns): the attack's own steps were queued on the ATTACKER's queue, which this request
    // is not draining — drain it, exactly as BeginSWUAttack does after queueing an attack.
    if (intval($player) !== $atkSeat) (new DecisionQueueController())->ExecuteStaticMethods($atkSeat, "-");
};

// ── 2. Damage ──────────────────────────────────────────────────────────────────────────────────────────────────
// Returns true when it queued a decision the commit must wait for (the uniqueness choice).
function _SWUIc27147CommitAtDamage(int $activePlayer): bool {
    $armed = GetSWUVar('SWU_IC27147_ARMED', '');
    if ($armed === '') return false;
    SetSWUVar('SWU_IC27147_ARMED', '');                       // one replacement per attack (CR 7.5.e/f)
    // (Read only behind a live arm, and every offer that can arm rewrites it first — so it is never cleared.)
    $riders = array_count_values(array_filter(explode(',', GetSWUVar('SWU_IC27147_CARRY', ''))));
    [$seat, $uid] = array_map('intval', explode('|', $armed) + [0, 0]);
    if ($seat <= 0 || $uid <= 0 || intval(GetSWUVar('SWU_CURRENT_ATTACKER_UID', '0')) !== $uid) return false;
    if (strpos((string)GetSWUVar('SWU_CURRENT_DEFENDER', ''), 'Base') === false) return false;

    global $playerID; $saved = $playerID;
    $playerID = $activePlayer;
    $aMz = SWUFindMzByUID($uid);
    $a   = $aMz !== null ? GetZoneObject($aMz) : null;
    if (!_SWUIc27147EligibleAttacker($a) || !_SWUIc27147WouldDealCombatDamage($a) || !_SWUIc27147CanSetUp($seat)) {
        $playerID = $saved;
        return false;                                         // lapses: nothing paid, nothing revealed
    }
    // Only the declaration-time riders still on the attacker; anything added since (an On Attack's -6/-0) stays.
    $carry = [];
    foreach (($a->TurnEffects ?? []) as $te) {
        if (($riders[(string)$te] ?? 0) > 0) { $carry[] = (string)$te; $riders[(string)$te]--; }
    }

    // Reveal and pay (the holder's frame), then take her out of hand.
    $playerID = $seat;
    AddGameLogEntry('REVEAL', "P{$seat} revealed " . GameLogCardRef('IC27_147') . ' from hand', 'ALL');
    SWUPayCost($seat, _SWUIc27147Cost($seat));
    $hand = &GetHand($seat);
    $idx  = _SWUIc27147HandIdx($seat);
    if ($idx >= 0) $hand[$idx]->removed = true;
    unset($hand);
    DecisionQueueController::CleanupRemovedCards();

    // Return the attacker to its owner's hand (its controller's frame).
    $playerID = $activePlayer;
    $aMz = SWUFindMzByUID($uid);
    if ($aMz !== null) SWUBounceUnit($activePlayer, $aMz);

    // Put Asajj into play as the attacker: exhausted, the holder's.
    $playerID = $seat;
    $newUid = NextUniqueID();
    $new = AddGroundArena($seat, CardID: 'IC27_147', Status: 0, Owner: $seat, Damage: 0, Controller: $seat, UniqueID: $newUid);
    $newMz = $new->GetMzID();
    foreach ($carry as $te) AddTurnEffect($newMz, $te);
    AddGameLogEntry('EFFECT', "P{$seat}'s " . GameLogCardRef('IC27_147') . ' entered play as the attacker', 'ALL');
    SetSWUVar('SWU_CURRENT_ATTACKER_UID', strval($newUid));
    SetSWUVar('SWU_IC27147_SWAP', "{$uid}|{$newUid}");
    $playerID = $activePlayer;                                // SWU_CURRENT_ATTACKER is in the attacking seat's frame
    $atkFrameMz = SWUFindMzByUID($newUid);
    if ($atkFrameMz !== null) SetSWUVar('SWU_CURRENT_ATTACKER', $atkFrameMz);
    $pending = SWUEnforceUniqueness($seat, $activePlayer, false, '', 'IC27_147');
    $playerID = $saved;
    return $pending;
}
