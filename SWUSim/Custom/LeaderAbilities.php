<?php

// Registry of per-card leader action-ability handlers.
// Key: CardID (e.g. "SOR_001"). Value: callable($player) that implements the ability.
// Handlers are responsible for calling SWUAfterAction($player) when done.
// Leaders without a handler registered here will still exhaust but do nothing else.
global $leaderAbilities;
$leaderAbilities = [];

// Resource component of each leader action's cost (omitted = 0). Checked by
// SWULeaderActionAffordable BEFORE the leader exhausts — if the cost can't be
// paid, the action never starts and the leader stays ready (CR: all costs of
// an ability must be payable to use it).
//
// PAYMENT IS CENTRAL — do NOT pay inside a $leaderAbilities closure. SWULeaderAction
// pays this cost through SWUOfferAltPayment (Credit tokens per CR 3.13, then SEC_122
// Droids, then resources) and only then dispatches the closure, so every leader Action
// accepts the same payment mixes a card play does. A closure that also called
// SWUExhaustResources would charge the player twice.
global $leaderActionResourceCosts;
$leaderActionResourceCosts = [
    "SOR_005" => 1, // Luke Skywalker
    "SOR_010" => 1, // Darth Vader
    "SOR_006" => 1, // Emperor Palpatine (also requires a friendly unit to defeat)
    "SOR_016" => 1, // Grand Admiral Thrawn
    "SOR_007" => 1, // Grand Moff Tarkin
    "SOR_013" => 1, // Cassian Andor
    "JTL_013" => 1, // Poe Dameron (flip + attach as Pilot to a 0-pilot friendly Vehicle)
    "JTL_003" => 1, // Lando Calrissian (play a unit from hand; conditional Shield)
    "JTL_007" => 1, // Admiral Holdo (+2/+2 to a Resistance unit)
    "JTL_015" => 1, // Rio Durant (attack with a space unit, +1/+0 + Saboteur)
    "JTL_016" => 1, // Admiral Ackbar (exhaust a non-leader unit → controller creates an X-Wing)
    "LOF_004" => 1, // Kanan Jarrus (Shield to a Creature or Spectre unit)
    "LOF_006" => 1, // Supreme Leader Snoke (Experience to highest-power unit)
    "LOF_011" => 1, // Kit Fisto (if attacked with a Jedi this phase, deal 2)
    "SEC_001" => 1, // Chancellor Palpatine (search top 5 for a Plot card)
    "SEC_002" => 1, // Jabba the Hutt (a friendly damaged unit deals damage to an enemy unit)
    "SEC_004" => 1, // Leia Organa (disclose → give Experience to a non-aspect-sharing unit)
    "SEC_008" => 1, // Bail Organa (return a friendly resource → ramp top of deck)
    "SEC_010" => 1, // Dedra Meero (enemy's controller may self-damage it, else you draw)
    "SEC_011" => 1, // Governor Pryce (ready a token unit)
    "SEC_014" => 1, // Sly Moore (if 4+ exhausted units in play, create a Spy)
    "SEC_015" => 1, // C-3PO (if you control an exhausted unit, exhaust a unit)
    "IBH_001" => 1, // Leia Organa (heal 1 from a friendly unit)
    "IBH_053" => 1, // Darth Vader (deal 1 to a base)
];

// LOF leaders whose Action cost includes "use the Force (lose your Force token)". Gated in
// SWULeaderActionAffordable; the ability closures call UseTheForce() to pay.
global $leaderActionForceCost;
$leaderActionForceCost = [
    "LOF_002" => true, // Mother Talzin
    "LOF_003" => true, // Ahsoka Tano
    "LOF_008" => true, // Obi-Wan Kenobi
    "LOF_009" => true, // Darth Maul
    "LOF_013" => true, // Barriss Offee
    "LOF_014" => true, // Grand Inquisitor
    "LOF_015" => true, // Cal Kestis
    "LOF_016" => true, // Qui-Gon Jinn
    "LOF_018" => true, // Anakin Skywalker
];







































































































// ═══════════════════════════════════════════════════════════════════════════
// LOF Leaders — Phase 14 (leader-side Actions)
// ═══════════════════════════════════════════════════════════════════════════

















// LOF_005 Morgan Elsbeth — Action [Exhaust]: Choose a friendly unit that attacked this phase. Play a unit
// from your hand that shares a keyword with the chosen unit. It costs 1 resource less.
function _SWUCardKeywordSet(string $cardID): array {
    $kws = [];
    foreach (['Ambush','Bounty','Coordinate','Exploit','Grit','Hidden','Overwhelm','Piloting','Plot','Raid','Restore','Saboteur','Sentinel','Shielded','Smuggle'] as $kw) {
        $reg = $GLOBALS[$kw . '_Cards'] ?? null;
        if (is_array($reg) && isset($reg[$cardID])) $kws[] = $kw;
    }
    return $kws;
}
// LOF_005 deployed On Attack discount: does $cardID (a card in hand → printed keywords only) share a
// keyword with any friendly unit IN PLAY (which counts its current printed + conditional + granted keywords)?
function _SWULof005SharesKeywordWithFriendly(int $player, string $cardID): bool {
    $myKw = _SWUCardKeywordSet($cardID);
    if (empty($myKw)) return false;
    foreach (GetUnitsInPlay($player) as $u) {
        if (!empty($u->removed)) continue;
        $uKw = _SWUCardKeywordSet($u->CardID ?? '');
        foreach (['Ambush'=>'AMBUSH','Grit'=>'GRIT','Hidden'=>'HIDDEN','Overwhelm'=>'OVERWHELM','Saboteur'=>'SABOTEUR','Sentinel'=>'SENTINEL','Shielded'=>'SHIELDED','Raid'=>'RAID','Restore'=>'RESTORE'] as $name => $kw) {
            if (!in_array($name, $uKw, true) && _SWUUnitHasKeyword($u, $kw)) $uKw[] = $name;
        }
        if (!empty(array_intersect($myKw, $uKw))) return true;
    }
    return false;
}



























// LOF_013 Barriss Offee — Action [Exhaust, use the Force]: Play an event from your hand. It costs 1 less.
// DISCOUNT_PLAY_FROM_HAND owns the after-action (ActivateCard on play, SWUAfterAction on decline).
$leaderAbilities["LOF_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = SWUHandPlayablesAtDiscount($player, ['Event'], 1);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_an_event_from_hand_(costs_1_less)", "DISCOUNT_PLAY_FROM_HAND|1");
};
















// LOF_018 Anakin Skywalker — Action [Exhaust, use the Force]: Play a Villainy non-unit card from your hand,
// ignoring its aspect penalties (play at printed cost). (LOF_017 Darth Revan is a combat reaction, wired
// in CombatLogic/GameLogic rather than here.)
$leaderAbilities["LOF_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $ready = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a Piloting cost (CR 3.13)
    $targets = [];
    $hand = GetHand($player);
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i]; if (SWUObjGone($c)) continue;
        $cid = $c->CardID;
        if (strpos(CardAspect($cid) ?? '', 'Villainy') === false) continue;       // Villainy only
        if (CardType($cid) === 'Unit') {
            // A Villainy Unit is not a valid "non-unit card" — EXCEPT a Pilot, which may be played AS A
            // PILOT (upgrade) when it can attach to a friendly Vehicle and its Piloting cost (ignoring
            // aspect penalties) is affordable. (Intended: Anakin plays a Villainy pilot only AS a pilot.)
            if (HasKeyword_Piloting($c) && !empty(SWUGetPilotValidTargets($player, $cid))
                    && $ready >= intval(CardPilotingCost($cid))) {
                $targets[] = "myHand-{$i}";
            }
            continue;
        }
        if ($ready >= intval(CardCost($cid))) $targets[] = "myHand-{$i}";          // non-unit at printed cost
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_a_Villainy_non-unit_card_(ignoring_aspect_penalties)", "LOF_018#0");
};







// ── SEC_003 Lama Su ───────────────────────────────────────────────────────────
// Friendly NON-Vehicle units that are valid hosts for $upgradeCardID.
function _SWUSec003Hosts(int $player, string $upgradeCardID): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (SWUGetUpgradeValidTargets($player, $upgradeCardID) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Controller ?? 0) !== $player) continue;   // friendly only
        if (HasTrait($o->CardID, 'Vehicle')) continue;           // non-Vehicle only
        $out[] = $mz;
    }
    return $out;
}
// Hand upgrades that have ≥1 valid non-Vehicle host and are affordable at the −1 discount.
function _SWUSec003PlayableHandUpgrades(int $player): array {
    global $playerID; $playerID = $player;
    $ready = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a play cost (CR 3.13)
    $out   = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
        $hosts = _SWUSec003Hosts($player, $o->CardID);
        if (empty($hosts)) continue;
        $cost = max(0, SWUComputePlayCost($player, $o, GetZoneObject($hosts[0])) - 1);
        if ($cost <= $ready) $out[] = $mz;
    }
    return $out;
}




// ── SEC_004 Leia Organa ───────────────────────────────────────────────────────
// Hand cards bearing at least one disclosable aspect icon (everything except Villainy).
function _SWUSec004DiscloseableHand(int $player): array {
    global $playerID; $playerID = $player;
    $five = ['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism'];
    $out  = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!empty(array_intersect(SWUCardAspectIcons($o->CardID), $five))) $out[] = $mz;
    }
    return $out;
}


































// ══════════════════════════════════════════════════════════════════════════════
// LAW Phase 8 — two-sided leaders (front Action via $leaderAbilities; deployed side
// via the unit-ability registries keyed on the leader CardID).
// ══════════════════════════════════════════════════════════════════════════════












;




// ── LAW_015 Jabba the Hutt ─────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust, return a friendly Underworld unit to its owner's hand]: Create a
// Credit token. Deployed Action: Play an Underworld unit from your hand; if you defeated a Credit while
// paying its cost, it gains Ambush this phase (deployed side + the conditional-Ambush plumbing live in
// CardDQHandlers.php / GameLogic.php). The leader is exhausted by SWULeaderAction; this closure pays the
// 1 resource then the return-a-unit additional cost (mandatory) before the effect.
function _SWULaw015FriendlyUnderworldUnits(int $player): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && TraitContains($o, 'Underworld')) $out[] = $mz;
        }
    }
    return $out;
}
// After the [1 resource] cost is paid (with or without a Credit defeated): return a friendly Underworld
// unit (the additional cost), then create a Credit token. Reached via the LAW_015_FRONT_PAY continuation.
function _SWULaw015AfterPay(int $player, bool $paidOk): void {
    global $playerID; $playerID = $player;
    if (!$paidOk) { SWUAfterAction($player); return; } // affordability-gated; defensive
    $targets = _SWULaw015FriendlyUnderworldUnits($player);
    if (empty($targets)) { SWUAfterAction($player); return; } // defensive (affordability requires one)
    SWUQueueChooseTarget($player, $targets, "Return_a_friendly_Underworld_unit_to_its_owner's_hand", "LAW_015#1");
}
























// ── LAW_009 Hera Syndulla — passive cost-waive only (in SWUComputePlayCost); no front Action. ────────



























// ── LAW_017 Han Solo ──────────────────────────────────────────────────────────
// Front Action [Exhaust, defeat a friendly token]: deal 1 to a unit.
// Deployed: Saboteur (auto) + On Attack: defeat any number of friendly tokens; deal that many to a unit.
// "Friendly token" (CR: tokens are Shield / Experience / the Force token / Credit tokens / Token units).
// Each defeatable token becomes a distinct OPTIONCHOOSE option decodable back to the token to defeat.
// (Subcards and the Force token aren't zone objects, so they can't be MZCHOOSE targets — hence a menu.)
// Field separator '~' avoids the ':' / '&' the DSL uses for args/lists; host mzIDs keep their '-'.
function _SWULaw017TokenOptions(int $player): array {
    global $playerID; $playerID = $player;
    $opts = [];
    if (PlayerHasTheForce($player)) $opts[] = 'Force';
    $c = 0;
    foreach (SWUUsableCreditTokenMzIDs($player) as $mz) { $opts[] = 'Credit' . $c; $c++; }
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz); if (SWUObjGone($o)) continue;
            $ex = _CountExperienceSubcards($o); for ($k = 0; $k < $ex; $k++) $opts[] = 'Exp~' . $mz . '~' . $k;
            $sh = _SWUCountShieldSubcards($o);  for ($k = 0; $k < $sh; $k++) $opts[] = 'Shield~' . $mz . '~' . $k;
        }
    }
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, ["Token Unit"]) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $opts[] = 'Unit~' . $mz; }
    return $opts;
}

function _SWULaw017DealNToUnit(int $player, int $n): void {
    if ($n <= 0) return;
    SWUOfferUnitTarget($player, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>$n,'prompt'=>"Deal_{$n}_damage_to_a_unit"]);
}



function _SWULaw017FinishDeployed(int $player): void {
    $n = intval(GetSWUVar("LAW017_CNT_{$player}", '0'));
    SetSWUVar("LAW017_CNT_{$player}", '0');
    _SWULaw017DealNToUnit($player, $n);
}






















// ASH_017 Greef Karga — triggered: may exhaust → give an Advantage token to the just-played/created unit
// (its UID is passed via the trigger extra).
function Ash017Trigger($player, $uid): void {
    global $playerID; $playerID = intval($player);
    if ($uid <= 0 || SWUFindMzByUID($uid) === null) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Greef_to_give_that_unit_an_Advantage_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_017#0|{$uid}", 1);
}


// ASH_017 Greef Karga (DEPLOYED unit side) — "When you play a unit or a token is created under your control:
// give an Advantage token to that unit." Non-optional, no exhaust cost (unlike the undeployed side).
function Ash017DeployedTrigger($player, $uid): void {
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($uid));
    if ($mz === null) return;
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed)) DoGiveAdvantageToken(intval($player), $mz);
}

// ASH_018 Grogu — triggered (play a uq unit costing 4+): if Grogu is ready, you may deploy him.
function Ash018Trigger($player): void {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Deploy_Grogu?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_018#0", 1);
}


// ASH_005 Luke Skywalker — "When a friendly unit's attack ends: you may exhaust this leader; if you do,
// heal 1 damage from that unit." Dispatched from the combat hook (DispatchTrigger case 'ASH_005').
function Ash005Trigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;   // attacker left play → nothing to heal
    if (intval($self->Damage ?? 0) <= 0) return;            // no damage on it → no benefit, skip the offer
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Luke_to_heal_1_from_that_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_005#0|{$mzID}", 1);
}


// ASH_005 Luke Skywalker (DEPLOYED unit side) — "When a friendly unit's attack ends: Heal 2 damage from
// that unit or from your base." Mandatory heal (no "may"); the player chooses the source. Offer only the
// sources that actually carry damage — "that unit" (the attacker, $mzID) and/or "your base" — so the
// no-benefit case fizzles cleanly with no prompt (mirrors the undeployed side's Damage<=0 skip). Dispatched
// from the combat hook (DispatchTrigger case 'ASH_005#1').
function Ash005DeployedTrigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $targets = [];
    if ($mzID !== '' && str_contains($mzID, '-')) {
        $self = GetZoneObject($mzID);
        if ($self !== null && empty($self->removed) && intval($self->Damage ?? 0) > 0) $targets[] = $mzID;
    }
    $base = GetBase(intval($player));
    if (!empty($base) && empty($base[0]->removed) && intval($base[0]->Damage ?? 0) > 0) $targets[] = 'myBase-0';
    if (empty($targets)) return;   // neither the attacker nor the base is damaged → nothing to heal
    SWUQueueChooseTarget(intval($player), $targets, "Heal_2_from_that_unit_or_your_base", "HEAL_TARGET|2");
}


// ASH_002 Fennec Shand — Action [1 resource, Exhaust, exhaust a friendly unit]: play a unit from your hand
// (paying its cost). It enters play ready. Costs: 1 resource + leader exhaust (auto) + exhaust a friendly
// unit (chosen in ASH_002#0), then play a hand unit with $gForceEnterReady (ASH_002#1).
$leaderAbilities["ASH_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $handUnits = ZoneSearch("myHand", ["Unit", "Token Unit"]);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        $arr = GetZone($z);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if ($u !== null && empty($u->removed) && intval($u->Status) === 1) $ready[] = "{$z}-{$i}";
        }
    }
    if (empty($handUnits) || empty($ready)) { SWUAfterAction($player); return; }   // can't pay / nothing to play
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Exhaust_a_friendly_unit_(cost)", "ASH_002#0");
};



// ── Deployed Leader Unit "Action" abilities (leader-gaps.md Group E) ─────────
// Deployed leader Actions dispatch through SWUUnitAction → $unitAbilities[CardID]
// (NO fallback to $leaderAbilities). costKind 'none' = no self-exhaust, no framework
// resource cost (each closure pays its own cost). ASH_002/LOF_013/LOF_018 have a
// deployed effect identical to their front Action (only the front's self-Exhaust is
// dropped), so they reuse the front closure verbatim; the affordability gate for the
// Force-cost ones lives in SWUUnitActionAffordable (else UseTheForce no-ops → free play).
global $unitAbilities, $unitActionCostKind, $unitActionResourceCosts;

// ASH_002 Fennec Shand — Action [1 resource, exhaust a friendly unit]: play a unit ready.
$unitAbilities["ASH_002"]      = $leaderAbilities["ASH_002"];


// LOF_013 Barriss Offee — Action [use the Force]: play an event from hand, costs 1 less.
$unitAbilities["LOF_013"]      = $leaderAbilities["LOF_013"];


// LOF_018 Anakin Skywalker — Action [use the Force]: play a Villainy non-unit, ignoring aspect penalties.
$unitAbilities["LOF_018"]      = $leaderAbilities["LOF_018"];













// ASH_013 Ezra Bridger — triggered (combat hook): may exhaust → give an Advantage token to a unit other
// than the attacker. ($parts[0] = attacker mzID, captured at trigger time.)
function Ash013Trigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Ezra_to_give_an_Advantage_token_to_a_different_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_013#0|{$mzID}", 1);
}


// ASH_013 Ezra Bridger (DEPLOYED unit side) — "When a friendly unit's attack ends: if it dealt 3+ combat
// damage to a base, you may give an Advantage token to a different unit." Unlike the undeployed side there is
// NO self-exhaust cost — it's a straight optional give. ($mzID = attacker, captured at trigger time.)
function Ash013DeployedTrigger($player, $mzID): void {
    $attObj = ($mzID && str_contains($mzID, '-')) ? GetZoneObject($mzID) : null;
    $attUID = SWUObjUID($attObj);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'GIVE_ADVANTAGE', 'excludeUID' => $attUID, 'may' => true,
        'question' => "Give_an_Advantage_token_to_a_different_unit?",
        'prompt'   => "Give_an_Advantage_token_to_a_different_unit",
    ]);
}

// ASH_016 Shin Hati (DEPLOYED unit side) — "When a friendly unit's attack ends: you may exhaust a unit that
// costs less than the combat damage dealt to a base this attack. Use this ability only once per round." NO
// self-exhaust cost; the once-per-round is tracked on the leader's NumUses budget (reset each regroup). The
// use is consumed only when a target is actually exhausted (declining leaves it available — "pass and reuse").
function Ash016DeployedTrigger($player, $mzID, $baseDmg): void {
    global $playerID; $playerID = intval($player);
    if ($baseDmg <= 0) return;                                  // no base damage → nothing costs "less than 0"
    if (!SWUHasUseAvailable(SWUGetLeader(intval($player)))) return;   // once-per-round already spent
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) < $baseDmg) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Exhaust_a_unit_costing_less_than_{$baseDmg}?",
        "Exhaust_a_unit_costing_less_than_{$baseDmg}", "ASH_016#1");
}







// ASH_016 Shin Hati — triggered (combat hook): may exhaust → exhaust a unit costing less than the combat
// damage dealt to a base this attack ($baseDmg passed via the trigger extra).
function Ash016Trigger($player, $mzID, $baseDmg): void {
    global $playerID; $playerID = intval($player);
    if ($baseDmg <= 0) return;   // no base damage → nothing costs "less than 0"
    $any = false;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) < $baseDmg) { $any = true; break 2; }
        }
    }
    if (!$any) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Shin_to_exhaust_a_cheaper_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_016#0|{$baseDmg}", 1);
}
















// ── SHD_006 Jabba the Hutt "His High Exaltedness" ───────────────────────────────
// Front Action [Exhaust]: Choose a unit. For this phase it gains "Bounty - The next unit you play
// this phase costs 1 resource less." The grant is a phase-duration BOUNTY turn-effect token whose
// dash param carries the discount (SHD_006-1 here; the deployed side grants -2). The custom reward
// is collected when the bountied unit is defeated/captured — see the granted-bounty snapshot in
// CollectWhenDefeatedTriggers + SWUCollectBounty (GameLogic.php). The deployed side (Epic deploy at
// 7+ resources = the standard threshold = printed cost 7; When-Deployed capture; the cost-2 Action)
// lives in CardDQHandlers.php. "Choose a unit" = ANY unit in any arena (you typically bounty an
// enemy so YOU — the opponent of its controller — collect it on defeat, CR 13.f).
function _SWUShd006AllUnits(int $player): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $out[] = $mz;
        }
    }
    return $out;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SHD leaders — Batch 12.1 (SHD_002 Qi'ra, SHD_003 Finn, SHD_004 Rey)
// (Epic deploy is generic: threshold = leader's printed cost, handled in SWUDeployLeader.)
// ═══════════════════════════════════════════════════════════════════════════════











































// ── SHD_017 Lando Calrissian ────────────────────────────────────────────────────
// Front Action [Exhaust] / deployed Action (once each round): "Play a card using Smuggle. It costs 2
// resources less. Defeat a resource you own and control." Ruling (CR — a leader ability resolves fully in
// sequence): the resource is defeated AFTER the Smuggled card's slot is replaced but BEFORE its When Played
// — enforced by SWUSmuggleResource's deferHandler path. Scope: offers smugglable UNIT resources.
function _SWUShd017HasTarget(int $player): bool {
    $ready = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a Smuggle cost (CR 3.13)
    foreach (GetResources($player) as $r) {
        if (!empty($r->removed) || SWUIsCreditToken($r->CardID ?? '')) continue;
        $cid = $r->CardID ?? '';
        if (stripos(CardType($cid) ?? '', 'Unit') === false) continue;
        $c = GetEffectiveSmuggleCost($player, $cid);
        // Lando -2, then the shared modifier delta (surcharge/discount), then halve — matching
        // SWUSmuggleResource's paid amount so Lando only offers a resource the player can actually afford.
        if ($c >= 0 && $ready >= SWUApplyCostHalving($player, max(0, $c - 2 + _SWUPlayCostModifierDelta($player, $r, null, true)))) return true;
    }
    return false;
}






   

// ── TS26 leaders ────────────────────────────────────────────────────────────────
// TARGET pool: mzIDs of units that are FRIENDLY RIGHT NOW and entered play this phase; optionally exclude
// one UID. Used by TS26_02 Anakin and TS26_04 Padmé (both sides).
// ⚠ Checks EVERY seat's flag, not just $player's. The flag is stamped under whoever controlled the unit
// AT ENTRY, so a unit that entered under the OPPONENT's control and has since come across (Change of
// Heart, No Glory) still "entered play this phase" and is a legal target now that it is friendly.
// The mirror case needs no handling: a unit that entered under my control but is now the enemy's simply
// is not in my arenas, so it drops out on its own.
function _SWUEnteredThisPhaseUnits(int $player, int $excludeUID = -1): array {
    global $playerID; $playerID = intval($player);
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $uid = intval($o->UniqueID ?? -1);
            if ($uid === $excludeUID) continue;
            if (_SWUUnitEnteredThisPhase($uid)) $out[] = $mz;
        }
    }
    return $out;
}

// Did this UniqueID enter play this phase under ANY controller?
function _SWUUnitEnteredThisPhase(int $uid): bool {
    if ($uid < 0) return false;
    for ($p = 1; $p <= SeatCountForGame(); $p++) {
        if (GlobalEffectCount($p, 'SWU_ENTERED_PHASE_' . $uid) > 0) return true;
    }
    return false;
}

// GATE count: how many units entered play this phase UNDER $player's control — a historical tally, and
// deliberately NOT a scan of the current board.
// ⚠ "2 or more friendly units entered play this phase" is a fact about the past that a later defeat or
// control change cannot undo. Counting live arena units instead broke it two ways: a 2-unit turn where
// one entrant DIED failed the gate (so the survivor never got its Shield), and an entrant that was later
// STOLEN stopped counting too. The flag is stamped per entry-controller and cleared at the phase
// boundary, so tallying the flags themselves is the honest reading.
function _SWUEnteredThisPhaseCount(int $player): int {
    $n = 0;
    foreach (GetGlobalEffects(intval($player)) as $e) {
        if (strpos((string)($e->CardID ?? ''), 'SWU_ENTERED_PHASE_') === 0) $n++;
    }
    return $n;
}














?>
