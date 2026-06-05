<?php
/**
 * SWU combat logic: attacks, damage calculation, and combat-related effects.
 *
 * SWU combat flow (CR 6.3):
 *   BeginSWUAttack(player, attackerMzID)
 *     → exhausts attacker
 *     → if >1 valid target: queues MZCHOOSE → CUSTOM "SWUResolveAttack|{attacker}"
 *     → if exactly 1 target: calls ExecuteSWUAttack directly
 *
 *   ExecuteSWUAttack(player, attackerMzID, targetMzID)
 *     → fires Step 1 triggers (OnAttack / OnDefense), then queues SWUCombatDamage
 *
 *   SWUCombatDamage DQ handler
 *     → applies damage, handles Shoot First / Overwhelm / Restore / Raid / Shield
 *     → defeats units, fires Step 3 triggers (When Defeated / After Attack)
 *     → calls SWUAfterAction to swap TurnPlayer
 */

// ── Damage helpers ─────────────────────────────────────────────────────────

// Consume one shield token (SOR_T02) from $unit. Returns true if a token was found and removed.
// Subcards may be arrays (builder/deserialized path) or stdClass objects (just-added path).
function SWUConsumeShieldToken($unit): bool {
    if ($unit === null || !is_array($unit->Subcards ?? null)) return false;
    foreach ($unit->Subcards as $key => $sub) {
        $cardID  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        $removed = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
        if (!$removed && $cardID === 'SOR_T02') {
            array_splice($unit->Subcards, $key, 1);
            return true;
        }
    }
    return false;
}

// ── Damage/heal animation helpers ───────────────────────────────────────────
// Thin wrappers over the engine frame-animation pipeline (Core/EngineActionRunner.php).
// $relMzID is a perspective-relative mzID (e.g. "myGroundArena-0" / "myBase-0");
// $perspective is the player that mzID is relative to. The engine plays these
// before the next board re-render; nothing here touches gamestate.

function SWUQueueDamageAnim(string $relMzID, int $amount, int $perspective): void {
    if ($amount <= 0) return;
    $abs = ConvertMzIDToAbsolute($relMzID, intval($perspective));
    if ($abs === '') return;
    QueueDamageAnimation($abs, intval($amount));
}

function SWUQueueHealAnim(string $relMzID, int $actualHealed, int $perspective): void {
    if ($actualHealed <= 0) return;
    $abs = ConvertMzIDToAbsolute($relMzID, intval($perspective));
    if ($abs === '') return;
    QueueRestoreAnimation($abs, intval($actualHealed));
}

function SWUQueuePreventedAnim(string $relMzID, int $perspective): void {
    $abs = ConvertMzIDToAbsolute($relMzID, intval($perspective));
    if ($abs === '') return;
    QueuePreventedDamageAnimation($abs);
}

// Shield-break shatter on the unit at $relMzID, played at shield slot $slot (0 = rightmost orb).
// One call per shield broken — saboteur strips several at once (each at its own slot, all
// simultaneous); combat absorption breaks the first shield (slot 0).
function SWUQueueShieldBreakAnim(string $relMzID, int $perspective, int $slot = 0): void {
    $abs = ConvertMzIDToAbsolute($relMzID, intval($perspective));
    if ($abs === '') return;
    QueueShieldBreakAnimation($abs, intval($slot));
}

// Deal $damage to player $targetPlayer's base. Sets flash on win.
function SWUDealDamageToBase($damage, $targetPlayer, $damager = null) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($targetPlayer);
    $base = &GetBase($targetPlayer);
    for ($i = 0; $i < count($base); $i++) {
        if (isset($base[$i]->removed) && $base[$i]->removed) continue;
        // JTL_074 Close the Shield Gate — "the next time damage would be dealt to this base this phase,
        // prevent it." One-shot per chosen base, consumed here; any leftover is cleared at regroup.
        if (intval($damage) > 0 && GlobalEffectCount(intval($targetPlayer), 'SWU_SHIELD_GATE') > 0) {
            RemoveGlobalEffect(intval($targetPlayer), 'SWU_SHIELD_GATE');
            SetFlashMessage("Damage to the base was prevented (Close the Shield Gate).");
            $playerID = $savedPID;
            return;
        }
        $base[$i]->Damage = intval($base[$i]->Damage) + $damage;
        SWUQueueDamageAnim("myBase-0", intval($damage), intval($targetPlayer));
        // "Each opponent whose base you've damaged this phase" set (SOR_175) — flag the DAMAGER
        // (the caller's context, i.e. the other player) keyed by the damaged base's owner. Cleared
        // at regroup. Skip self-damage (a player damaging their own base doesn't flag anyone).
        if (intval($damage) > 0) {
            // An explicit $damager (e.g. indirect damage attributes to the ability's controller,
            // CR 35.4) wins; otherwise infer from the caller's context. When the damager equals
            // the target (self-inflicted), the guard below suppresses the enemy-base flags.
            $damager = ($damager !== null)
                ? intval($damager)
                : (($savedPID > 0 && $savedPID !== intval($targetPlayer)) ? intval($savedPID) : OtherPlayer(intval($targetPlayer)));
            if ($damager !== intval($targetPlayer)) {
                AddGlobalEffects($damager, 'SWU_DMGBASE_' . intval($targetPlayer));
                // SOR_013 Cassian Andor (leader action) — cumulative AMOUNT of damage dealt to each
                // enemy base this phase (one flag per point). Checked for the "3 or more" threshold;
                // cleared at RegroupPhaseStart. (SWU_DMGBASE_ above is only a presence flag — SOR_175.)
                for ($d = 0; $d < intval($damage); $d++) {
                    AddGlobalEffects($damager, 'SWU_BASEDMG_AMT_' . intval($targetPlayer));
                }
            }
            // JTL_009 Boba Fett — "when you deal non-combat damage" (combat base damage sets the flag).
            if (empty($GLOBALS['gInCombatDamage'])) _SWUCollectBobaNonCombatReaction(intval($damager));
        }
        $baseHP = intval(CardHp($base[$i]->CardID));
        if ($base[$i]->Damage >= $baseHP) {
            $winner = $targetPlayer === 1 ? 2 : 1;
            global $gWinner;
            $gWinner = $winner;
            SetFlashMessage("GAMEOVER:Player {$targetPlayer}'s base has been defeated! Player {$winner} wins!");
        }
        break;
    }
    $playerID = $savedPID;
}

// Return a deployed leader unit to its Leader zone instead of the discard pile.
// Resets leader zone flags; the arena entry is simply marked removed.
function SWUReturnLeaderToZone(int $ownerPlayer, string $unitMzID): void {
    global $playerID;
    $savedPID = $playerID;

    // Remove the leader-unit under the CURRENT $playerID context — the mzID is relative to whoever
    // is defeating it (the attacker in combat, the effect's controller for "defeat target unit"),
    // NOT necessarily the owner. Re-resolving it under $ownerPlayer would flip "their"/"my" and find
    // the wrong (or no) arena, leaving the unit on the board (e.g. Takedown on an enemy deployed leader).
    $unitObj = GetZoneObject($unitMzID);
    if ($unitObj !== null && (!isset($unitObj->removed) || !$unitObj->removed)) {
        // CR 8.34.4: rescue any captives guarded by this leader-unit before it leaves play.
        SWURescueCaptivesOf($unitObj);
        $unitObj->removed = true;
    }

    $playerID = $ownerPlayer;
    $leaderArr = &GetLeader($ownerPlayer);
    for ($i = 0; $i < count($leaderArr); $i++) {
        if (!isset($leaderArr[$i]->removed) || !$leaderArr[$i]->removed) {
            $leaderArr[$i]->Deployed        = false;
            $leaderArr[$i]->DeployedUniqueID = 0;
            $leaderArr[$i]->Ready           = false;
            $leaderArr[$i]->Damage          = 0;
            break;
        }
    }

    $playerID = $savedPID;
}

// Return any leader-pilot subcards on $host to the leader zone (instead of discarding them).
// Called after the host is confirmed to be leaving play (defeated or bounced) when the host's
// OWN CardID is NOT a leader — i.e. a Vehicle carrying a leader-pilot upgrade (IsPilot=true
// subcard whose CardID has CardType containing 'Leader'). The subcard is removed from Subcards
// and the owning player's leader-zone entry is reset (Deployed=false/DeployedUniqueID=0/Ready=false/Damage=0).
function SWUReturnLeaderPilotSubcards($host, int $ownerPlayer): void {
    if ($host === null || empty($host->Subcards) || !is_array($host->Subcards)) return;
    global $playerID;
    $savedPID = $playerID;
    $newSubcards = [];
    foreach ($host->Subcards as $sub) {
        $isRemoved = is_array($sub) ? !empty($sub['removed'])    : !empty($sub->removed);
        $isCaptive = is_array($sub) ? !empty($sub['IsCaptive'])  : !empty($sub->IsCaptive);
        $subCardID = is_array($sub) ? ($sub['CardID'] ?? '')     : ($sub->CardID ?? '');
        // A leader card can only be a subcard via Piloting, so a (non-captive) leader subcard is
        // always a leader-pilot — recognize it by its leader CardType regardless of the IsPilot flag.
        if (!$isRemoved && !$isCaptive && strpos(CardType($subCardID) ?? '', 'Leader') !== false) {
            // Return this leader to the leader zone — find the owning player from the subcard.
            $subOwner = is_array($sub) ? intval($sub['Owner'] ?? $ownerPlayer) : intval($sub->Owner ?? $ownerPlayer);
            $playerID = $subOwner;
            $leaderArr = &GetLeader($subOwner);
            for ($i = 0; $i < count($leaderArr); $i++) {
                if (!isset($leaderArr[$i]->removed) || !$leaderArr[$i]->removed) {
                    $leaderArr[$i]->Deployed        = false;
                    $leaderArr[$i]->DeployedUniqueID = 0;
                    $leaderArr[$i]->Ready           = false;
                    $leaderArr[$i]->Damage          = 0;
                    break;
                }
            }
            // Do NOT add to $newSubcards — the subcard is removed from the host.
            continue;
        }
        $newSubcards[] = $sub;
    }
    $host->Subcards = $newSubcards;
    $playerID = $savedPID;
}

// Upgrade/pilot subcards whose disposition when their host leaves play is governed by their OWN
// bespoke mechanic (handled elsewhere in the defeat flow) rather than the generic "defeated upgrade →
// owner's discard" rule — skip them here so they are not double-handled:
//   JTL_094 Luke (pilot) — rebuilt from a deferred defeat-replacement snapshot (may become a ground
//                          unit instead of being defeated); the deferred flush owns its disposition.
// NOTE: SHD_053 Second Chance and JTL_073 Grim Valor DO go to the owner's discard here — they still
// fire their respective effects (the unit's TPF free-replay marker / the granted When Defeated trigger,
// read off the still-attached subcard later) because this sweep does not remove subcards from the host.
const SWU_SELF_HANDLED_DEFEAT_SUBCARDS = ['JTL_094'];

// Discard a leaving-play host's remaining (non-leader, non-captive) upgrade/pilot subcards to their
// OWNER's discard. CR: when a unit leaves play its upgrades are defeated, and a defeated card always
// goes to ITS OWN owner's discard — control is never "stolen" into another player's discard. Token
// upgrades are set aside (removed from game), not discarded. Call AFTER SWURescueCaptivesOf (captives
// already released) and SWUReturnLeaderPilotSubcards (leader-pilots already returned to the leader
// zone). Does NOT clear Subcards: later collection passes (JTL_073 grant) still read the array.
function SWUDiscardHostSubcards($host): void {
    if ($host === null || empty($host->Subcards) || !is_array($host->Subcards)) return;
    global $playerID;
    $savedPID = $playerID;
    foreach ($host->Subcards as $sub) {
        $isCaptive = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $isRemoved = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        if ($isCaptive || $isRemoved) continue;
        $subCardID = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        if ($subCardID === '' || in_array($subCardID, SWU_SELF_HANDLED_DEFEAT_SUBCARDS, true)) continue;
        if (strpos(strtolower(CardType($subCardID) ?? ''), 'token') !== false) continue; // tokens set aside
        $subOwner = is_array($sub) ? intval($sub['Owner'] ?? $savedPID) : intval($sub->Owner ?? $savedPID);
        SWUAddToDiscard($subOwner, $subCardID, 'PLAY');
    }
    $playerID = $savedPID;
}

// Defeat a unit (move to discard). Returns true if the unit was removed.
// Leader units return to their leader zone instead of going to the discard pile.
function SWUDefeatUnit($player, $unitMzID, $skipReplacement = false, $fromDamage = false) {
    global $playerID, $gDeferredReplacements;
    $savedPID = $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($unitMzID);
    if ($obj === null || (isset($obj->removed) && $obj->removed)) {
        $playerID = $savedPID;
        return false;
    }
    // "Can't be defeated by enemy card abilities" (SHD_187 / JTL_103 / LAW_149 / SEC_012 / TWI_220).
    // Only blocks a DIRECT defeat effect from an opponent — NOT state-based "no remaining HP" defeat
    // ($fromDamage, governed by SWUImmuneToHpDefeat), NOT combat, NOT the controller's own abilities.
    if (!$fromDamage && intval($player) !== intval($obj->Controller ?? $player) && SWUAvoidsDefeat($obj)) {
        $playerID = $savedPID;
        return false;
    }
    // Defeat-replacement (CR): "if this unit would be defeated, you may instead …" (JTL_049 L3-37).
    // Park the unit (NOT defeated/discarded; WhenDefeated does NOT fire) and resolve at action end.
    if (!$skipReplacement) {
        $rep = _SWUUnitDefeatReplacement($obj);
        if ($rep !== null) {
            $gDeferredReplacements[] = [
                'uid'        => intval($obj->UniqueID ?? 0),
                'controller' => intval($obj->Controller ?? $obj->Owner ?? $player),
                'cardID'     => $obj->CardID,
                'kind'       => $rep['kind'],
            ];
            $playerID = $savedPID;
            return true;
        }
    }
    // Fire the defeated unit's WhenDefeated ability AND the leave-play reactions (Gideon/Krell/Boba)
    // for ANY effect-defeat routed through here — direct "defeat target unit" (Takedown/Vanquish),
    // sacrifice, shrink sweep, Rukh, etc. Combat-defeats mark units removed directly (not via this
    // function) and collect separately; the damage paths used to pre-collect before calling this, so
    // those pre-collects were removed (this is now the single collection point for effect-defeats).
    CollectWhenDefeatedTriggers(intval($player), [
        ['player' => intval($obj->Controller ?? $obj->Owner ?? $player), 'cardID' => $obj->CardID, 'mzID' => $unitMzID]
    ]);
    $obj = GetZoneObject($unitMzID);
    if ($obj === null || (isset($obj->removed) && $obj->removed)) { $playerID = $savedPID; return true; }
    $owner = isset($obj->Owner) ? intval($obj->Owner) : intval($player);
    if (strpos(CardType($obj->CardID) ?? '', 'Leader') !== false) {
        SWUReturnLeaderToZone($owner, $unitMzID);
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return true;
    }
    $cardID = $obj->CardID;
    $hasSecondChance = _SWUUnitHasUpgrade($obj, 'SHD_053');
    $controller = isset($obj->Controller) ? intval($obj->Controller) : $owner;
    // CR 8.34.4: rescue any captives guarded by this unit before it leaves play.
    SWURescueCaptivesOf($obj);
    // If the host carries a leader-pilot upgrade, return that leader to zone before discarding.
    SWUReturnLeaderPilotSubcards($obj, $owner);
    _SWUDeferPilotDefeatReplacements($obj); // JTL_094: a pilot upgrade that "may instead move to ground"
    SWUDiscardHostSubcards($obj);           // remaining upgrades/pilots → each owner's discard
    $obj->removed = true;
    SWUAddToDiscard($owner, $cardID, 'PLAY', $hasSecondChance ? 'TPF' : '');
    if ($owner !== intval($player)) {
        AddGlobalEffects(intval($player), 'SWU_ENEMY_DEFEATED');
    }
    // "A friendly unit was defeated this phase" (SOR_051 Luke) — friendly = CONTROLLER (a stolen
    // unit is friendly to whoever currently controls it). Cleared at RegroupPhaseStart.
    AddGlobalEffects($controller, 'SWU_FRIENDLY_DEFEATED');
    // "Units defeated this phase" multiset, counted per CardID on the OWNER (whose discard it lands
    // in) — SOR_091 returns that many copies from your discard. CardID-keyed (not UniqueID) because
    // a discard entry's UniqueID does NOT survive gamestate serialization across the action boundary;
    // GlobalEffects flags do. Cleared at RegroupPhaseStart.
    AddGlobalEffects($owner, 'SWU_DEFEATED_CARD_' . $cardID);
    DecisionQueueController::CleanupRemovedCards();
    $playerID = $savedPID;
    return true;
}

// Defeat the Nth non-captive upgrade on the unit at $hostMzID.
// $upgradeIndex is 0-based among non-captive, non-removed subcards.
// Token upgrades (type contains "Token") are removed from the game (set aside).
// Non-token upgrades go to their owner's discard with From='PLAY'.
// Returns true if an upgrade was found and removed, false otherwise.
function SWUDefeatUpgrade(int $player, string $hostMzID, int $upgradeIndex = 0, bool $bounce = false, bool $skipReplacement = false): bool {
    global $playerID, $gDeferredReplacements;
    $savedPID = $playerID;
    $playerID = intval($player);

    $host = &GetZoneObject($hostMzID);
    if ($host === null || ($host->removed ?? false) || !is_array($host->Subcards ?? null)) {
        $playerID = $savedPID;
        return false;
    }

    $upgradeCount = 0;
    $foundCardID = null;
    $foundOwner  = null;
    $foundKey    = null;
    $foundIsPilot = false;
    foreach ($host->Subcards as $key => $sub) {
        $isCaptive = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $isRemoved = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        if ($isCaptive || $isRemoved) continue;

        if ($upgradeCount === $upgradeIndex) {
            $foundCardID  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            $foundOwner   = is_array($sub) ? intval($sub['Owner'] ?? $player) : intval($sub->Owner ?? $player);
            $foundKey     = $key;
            $foundIsPilot = is_array($sub) ? ($sub['IsPilot'] ?? false) : ($sub->IsPilot ?? false);
            $foundUID     = is_array($sub) ? intval($sub['UniqueID'] ?? 0) : intval($sub->UniqueID ?? 0);
            $foundCtrl    = is_array($sub) ? intval($sub['Controller'] ?? $foundOwner) : intval($sub->Controller ?? $foundOwner);
            $foundCaps    = is_array($sub) ? ($sub['Captives'] ?? []) : ($sub->Captives ?? []);
            break;
        }
        $upgradeCount++;
    }

    if ($foundKey === null) {
        $playerID = $savedPID;
        return false;
    }

    // Rebuild Subcards without the defeated upgrade (explicit reassignment ensures the
    // property on the live zone object is updated even when $host was obtained without &).
    $newSubcards = [];
    foreach ($host->Subcards as $k => $s) {
        if ($k !== $foundKey) $newSubcards[] = $s;
    }
    $host->Subcards = $newSubcards;

    // JTL_094 Luke pilot-upgrade defeat-replacement (host SURVIVES this "defeat an upgrade" effect):
    // park a snapshot and offer the controller the move-to-ground at action end (same kind as the
    // host-leaves-play path; the upgrade is already off the host).
    if (!$skipReplacement && $foundCardID === 'JTL_094') {
        if (intval($foundUID) <= 0) $foundUID = NextUniqueID();
        $gDeferredReplacements[] = ['kind' => 'upgrade_to_unit', 'uid' => intval($foundUID),
            'controller' => intval($foundCtrl), 'owner' => intval($foundOwner), 'cardID' => $foundCardID,
            'captives' => is_array($foundCaps) ? $foundCaps : []];
        $playerID = $savedPID;
        return true;
    }

    // Leader-pilot subcard (IsPilot + CardID is a leader): return to the leader zone instead of discard.
    if ($foundIsPilot && strpos(CardType($foundCardID) ?? '', 'Leader') !== false) {
        $playerID = $foundOwner;
        $leaderArr = &GetLeader($foundOwner);
        for ($i = 0; $i < count($leaderArr); $i++) {
            if (!isset($leaderArr[$i]->removed) || !$leaderArr[$i]->removed) {
                $leaderArr[$i]->Deployed        = false;
                $leaderArr[$i]->DeployedUniqueID = 0;
                $leaderArr[$i]->Ready           = false;
                $leaderArr[$i]->Damage          = 0;
                break;
            }
        }
    // Tokens are set aside (removed from game); non-tokens go to owner's discard or hand.
    } elseif (strpos(strtolower(CardType($foundCardID) ?? ''), 'token') === false) {
        if ($bounce) {
            AddHand($foundOwner, CardID: $foundCardID);
        } else {
            SWUAddToDiscard($foundOwner, $foundCardID, 'PLAY');
        }
    }

    // SOR_122 Traitorous / JTL_083 Pantoran Starship Thief: when this upgrade detaches (defeated), the
    // host returns to its owner's control ("When this upgrade detaches from a unit: that unit's owner
    // takes control of it.").
    if ($foundCardID === 'SOR_122' || $foundCardID === 'JTL_083') {
        $hostOwner = intval($host->Owner ?? $player);
        $hostCtrl  = intval($host->Controller ?? $hostOwner);
        if ($hostCtrl !== $hostOwner) {
            SWUTakeControlOfUnit($hostOwner, $hostMzID);
        }
    }

    $playerID = $savedPID;

    // Removing an upgrade can lower the host's HP (e.g. SOR_120 Academy Training +2/+2). Run the
    // state-based "no remaining HP" sweep so a now-lethally-damaged host is defeated (CR 8.21).
    SWUCheckShrinkDefeats();

    return true;
}

// ── Macro ChoiceFunction wrappers ───────────────────────────────────────────
// These are called by the generated macro system (first param is always $player).
// They also serve as the direct implementation — call these directly when
// macros are not available (e.g. await context in ability code).

function OnDamageBase($player, $source, $targetPlayer, $amount) {
    SWUDealDamageToBase(intval($amount), intval($targetPlayer));
}

// Remove up to $amount damage counters from a unit. Clamps at 0.
function OnHealUnit($player, $mzCard, $amount) {
    $obj = &GetZoneObject($mzCard);
    if ($obj === null || (isset($obj->removed) && $obj->removed)) return;
    $before = intval($obj->Damage);
    $obj->Damage = max(0, $before - intval($amount));
    $healed = $before - intval($obj->Damage);
    SWUQueueHealAnim($mzCard, $healed, intval($player));
    // Reactive "When 1 or more damage is healed from this unit" (JTL_062 Silver Angel).
    if ($healed >= 1 && function_exists('_SWUOnUnitHealed')) _SWUOnUnitHealed($obj);
}

// Remove up to $amount damage counters from player $targetPlayer's base. Clamps at 0.
function OnHealBase($player, $targetPlayer, $amount) {
    // SOR_160 Wolffe: "Bases can't be healed for this phase." A global lock set by either player —
    // block ALL base healing (including Restore) while it's active.
    if (GlobalEffectCount(1, 'SWU_NOHEAL_BASE') > 0 || GlobalEffectCount(2, 'SWU_NOHEAL_BASE') > 0) return;
    $base = &GetBase(intval($targetPlayer));
    for ($i = 0; $i < count($base); $i++) {
        if (isset($base[$i]->removed) && $base[$i]->removed) continue;
        $before = intval($base[$i]->Damage);
        $base[$i]->Damage = max(0, $before - intval($amount));
        SWUQueueHealAnim("myBase-0", $before - intval($base[$i]->Damage), intval($targetPlayer));
        break;
    }
}

// ── Combat trigger collectors ───────────────────────────────────────────────

// True if $obj carries an attack-duration TurnEffect marker with base $base (granted "for this attack").
function _SWUAttackHasMarker($obj, string $base): bool {
    foreach (($obj->TurnEffects ?? []) as $te) {
        if (SWUParseTurnEffect((string)$te)['base'] === $base) return true;
    }
    return false;
}

// Step 1: collect On Attack / On Defense triggers after attacker and target are known.
// Collects into $gPendingTriggers but DOES NOT flush — ExecuteSWUAttack flushes via
// FlushCombatTriggerBag so that combat damage runs after the triggers resolve.
function CollectCombatStep1Triggers($activePlayer, $attackerMzID, $defenderMzID): void {
    global $onAttackAbilities;
    $attacker = GetZoneObject($attackerMzID);
    $defender = GetZoneObject($defenderMzID);

    if ($attacker !== null && !isset($attacker->removed) && HasOnAttackAbility($attacker->CardID)
        && !LostAbilities($attacker)) {
        AddTrigger($activePlayer, 'OnAttack', $attacker->CardID, $attackerMzID);
    }
    // JTL_156 Trench Run — granted "On Attack: discard 2 from the defending player's deck; deal the
    // cost difference (unpreventable) to this unit." (Marker added for this attack only.)
    if ($attacker !== null && !isset($attacker->removed) && _SWUAttackHasMarker($attacker, 'JTL_156')) {
        AddTrigger($activePlayer, 'JTL_156', 'JTL_156', $attackerMzID);
    }
    // Upgrade-granted On Attack abilities (e.g. JTL_172, SOR_137 on a Force unit).
    if ($attacker !== null && !isset($attacker->removed)) {
        foreach (GetUpgradesOnUnit($attacker) as $upgrade) {
            $key = $upgrade->CardID . ':0';
            if (isset($onAttackAbilities[$key])) {
                AddTrigger($activePlayer, 'OnAttackFromUpgrade', $upgrade->CardID, $attackerMzID);
            }
        }
    }
    if ($defender !== null && !isset($defender->removed) && HasOnDefenseAbility($defender->CardID)) {
        $defController = intval($defender->Controller ?? GetOpponent($activePlayer));
        // $defenderMzID is in the ATTACKER's (active player's) frame — the defender always sits in the
        // attacker's "their..." zone. The OnDefense trigger is dispatched under $defController, so convert
        // the mzID to that frame ("theirGroundArena-N" → "myGroundArena-N") or it resolves to the wrong unit.
        $defMzForDef = preg_replace('/^their/', 'my', $defenderMzID);
        AddTrigger($defController, 'OnDefense', $defender->CardID, $defMzForDef);
    }
    // Upgrade-granted "When attached unit is attacked: ..." reactive (JTL_260 Death Star Plans — the
    // ATTACKING player steals the upgrade). Fires for the ATTACKER (active player); $defenderMzID is in
    // the attacker's frame, so the handler can locate the upgrade and pick one of the attacker's units.
    if ($defender !== null && !isset($defender->removed)) {
        global $onAttackedFromUpgradeAbilities;
        foreach (GetUpgradesOnUnit($defender) as $up) {
            if (isset($onAttackedFromUpgradeAbilities[$up->CardID ?? ''])) {
                AddTrigger($activePlayer, 'OnAttackedFromUpgrade', $up->CardID, $defenderMzID);
            }
        }
    }
}

// Step 3: collect When Defeated and On Attack End triggers after damage resolution.
function CollectCombatStep3Triggers($activePlayer, $attackerMzID, $defenderMzID, array $defeatedCards, array $combatCtx = []): void {
    CollectWhenDefeatedTriggers($activePlayer, $defeatedCards);
    // After-attack triggers always fire; conditions ("if survived", "if defeated defender") live inside handlers.
    CollectAfterAttackTriggers($activePlayer, $attackerMzID, $defenderMzID, $combatCtx);
}

// WhenDealsCombatDamage / WhenDefeats windows (Phase 7.2): from the just-resolved attack's $combatCtx,
// queue the attacker's combat-hit abilities. Added to the same trigger bag as OnAttackEnd so they
// ride the EffectStack flush (decisions defer SWUAfterAction). $activePlayer = the attacker's controller.
function SWUCollectCombatHitTriggers($activePlayer, $attackerMzID, $defenderMzID, array $combatCtx): void {
    $attacker = GetZoneObject($attackerMzID);
    if ($attacker === null || !empty($attacker->removed)) return; // attacker defeated → its triggers don't fire
    $cardID = $attacker->CardID ?? '';
    switch ($cardID) {
        case 'SOR_085': // Rukh — "deals combat damage to a non-leader unit while attacking: defeat it."
            if (!empty($combatCtx['dealtToUnit']) && empty($combatCtx['defenderIsLeader'])) {
                AddTrigger($activePlayer, 'SOR_085', 'SOR_085', $defenderMzID);
            }
            break;
        case 'SOR_149': // Mace Windu — "attacks and defeats a unit: ready him."
            if (!empty($combatCtx['defenderDefeated'])) {
                AddTrigger($activePlayer, 'SOR_149', 'SOR_149', $attackerMzID);
            }
            break;
        case 'SOR_133': // Seventh Sister — "deals combat damage to an opponent's base: may deal 3 to a ground unit."
            if (!empty($combatCtx['dealtToBase'])) {
                AddTrigger($activePlayer, 'SOR_133', 'SOR_133', '');
            }
            break;
        case 'JTL_188': // Moff Gideon — deals combat damage to an opponent's base → that opponent's unit
                        // plays cost 1 more this phase (static flag; no decision).
            if (!empty($combatCtx['dealtToBase'])) {
                AddGlobalEffects(OtherPlayer($activePlayer), 'SWU_GIDEON_TAX');
            }
            break;
        case 'SOR_088': // Blizzard Assault AT-AT — "attacks and defeats a unit: may deal the excess to an enemy ground unit."
            if (!empty($combatCtx['defenderDefeated']) && intval($combatCtx['excess'] ?? 0) > 0) {
                AddTrigger($activePlayer, 'SOR_088', 'SOR_088', '', strval(intval($combatCtx['excess'])));
            }
            break;
    }

    // SOR_150 Heroic Sacrifice — granted "When this unit deals combat damage: Defeat it." The per-attack
    // marker is captured into $combatCtx['attackerSelfDefeat'] at combat start (the SWU_DUR_ATTACK token
    // is already stripped by SWUExpireTurnEffects before this collection runs). Fires on combat damage
    // to a unit OR a base.
    if (!empty($combatCtx['attackerSelfDefeat']) && (!empty($combatCtx['dealtToBase']) || !empty($combatCtx['dealtToUnit']))) {
        AddTrigger($activePlayer, 'SOR_150', 'SOR_150', $attackerMzID);
    }

    // JTL_177 Stay on Target — granted "When this unit deals damage to a base: Draw a card." (this attack)
    if (!empty($combatCtx['jtl177BaseDraw']) && !empty($combatCtx['dealtToBase'])) {
        DoDrawCard(intval($activePlayer), 1);
    }

    // JTL_120 Dorsal Turret (upgrade) — host gains "When this unit deals combat damage to a unit while
    // attacking: Defeat that unit."
    if (!empty($combatCtx['dealtToUnit']) && _SWUUnitHasUpgrade($attacker, 'JTL_120')) {
        AddTrigger($activePlayer, 'JTL_120', 'JTL_120', $defenderMzID);
    }

    // SOR_013 Cassian Andor (deployed Leader Unit) — "When you deal damage to an enemy base: You may
    // draw a card. Use this ability only once each round." Controller-based (any friendly unit's
    // base hit counts, not just Cassian's own attack), so it rides this collection point after the
    // attacker switch. Once-per-round flag set at collect time; cleared at RegroupPhaseStart.
    if (!empty($combatCtx['dealtToBase'])
        && _SWUCountUnitsWithCardID(intval($activePlayer), 'SOR_013') > 0
        && SWUHasUseAvailable(SWUGetLeader(intval($activePlayer)))) {
        AddTrigger($activePlayer, 'SOR_013', 'SOR_013', '');
        SWUConsumeUse(SWUGetLeader(intval($activePlayer))); // once/round draw via leader NumUses
    }
}

// After-attack: fires unconditionally so handlers can inspect the outcome.
function CollectAfterAttackTriggers($activePlayer, $attackerMzID, $defenderMzID, array $combatCtx = []): void {
    $attacker = GetZoneObject($attackerMzID);
    if ($attacker !== null && !isset($attacker->removed) && HasOnAttackEndAbility($attacker->CardID)) {
        // SOR_146 Zeb Orrelios — "completes an attack: IF the defender was defeated, ...". The "if" is
        // a trigger condition; gate collection on the combat outcome so nothing fires (no decision)
        // when the defender survived. Other OnAttackEnd cards (SOR_009/015/192) are unconditional.
        if ($attacker->CardID !== 'SOR_146' || !empty($combatCtx['defenderDefeated'])) {
            AddTrigger($activePlayer, 'OnAttackEnd', $attacker->CardID, $attackerMzID);
        }
    }
    // Upgrade-granted "When attached unit completes an attack (and survives): ..." — the surviving-
    // attacker null check above is the "and survives" gate (a defeated attacker is removed by now).
    // JTL_197 Anakin Skywalker (may return itself to hand). Mirrors the OnAttack-from-upgrade scan.
    if ($attacker !== null && !isset($attacker->removed)) {
        global $onAttackEndFromUpgradeAbilities;
        foreach (GetUpgradesOnUnit($attacker) as $upgrade) {
            if (isset($onAttackEndFromUpgradeAbilities[$upgrade->CardID])) {
                AddTrigger($activePlayer, 'OnAttackEndFromUpgrade', $upgrade->CardID, $attackerMzID);
            }
        }
    }
    SWUCollectCombatHitTriggers($activePlayer, $attackerMzID, $defenderMzID, $combatCtx);
    // Route after-attack triggers through the EffectStack (not the flat FlushTriggerBag), so the
    // block-20 SWU_TRIGGER_RESUME defers SWUAfterAction until the attack's full trigger resolution
    // completes — and so a chained "then attack with another" (below) fires in the correct order,
    // AFTER any OnAttackEnd nested attack. (WhenDefeated triggers were already flushed separately by
    // CollectWhenDefeatedTriggers, so the bag holds at most the single OnAttackEnd here.)
    $flushed = FlushEntryTriggerBag($activePlayer);
    // Chained "then (may) attack with another unit" (SOR_009 Leia leader action / deployed
    // OnAttackEnd, SOR_103 Rebel Assault): the initiating card armed SWU_CHAINED_ATTACK before the
    // first BeginSWUAttack. It is fired (and the var consumed) by the SWU_TRIGGER_RESUME stack-empty
    // handler, AFTER this attack's full trigger resolution. If no trigger flush queued a resume,
    // queue a bare one so the chained attack still fires and SWUAfterAction stays deferred until then.
    if ($flushed === 0 && GetSWUVar('SWU_CHAINED_ATTACK', '') !== '') {
        DecisionQueueController::AddDecision($activePlayer, "CUSTOM", "SWU_TRIGGER_RESUME|{$activePlayer}", 20);
    }
}

// Execute the attack: fire Step 1 triggers then queue SWUCombatDamage to run after they resolve.
// $attackerMzID and $targetMzID are perspective-relative from $player.
function ExecuteSWUAttack($player, $attackerMzID, $targetMzID) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $attacker = GetZoneObject($attackerMzID);
    if ($attacker === null || (isset($attacker->removed) && $attacker->removed)) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }

    // SOR_072 Entrenched / JTL_092 Scramble Fighters: "can't attack bases." Backstop (the valid-targets
    // list already omits the base) so a base target never resolves into combat.
    if (strpos($targetMzID, 'Base') !== false
        && (_SWUUnitHasUpgrade($attacker, 'SOR_072')
            || (is_array($attacker->TurnEffects ?? null) && in_array('CANT_ATTACK_BASES', $attacker->TurnEffects)))) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }

    // Expose the current defender so On-Attack abilities that target "the defender"
    // (e.g. SOR_054 Jedi Lightsaber's granted ability) can resolve it. Persisted in the
    // gamestate var so it survives the request boundary if triggers resume in a later request.
    SetSWUVar('SWU_CURRENT_DEFENDER', $targetMzID);

    // SOR_212 Strafing Gunship: "While this unit is attacking a ground unit, the defender gets -2/-0."
    // The SWU_DEF_DEBUFF_N marker lives on the ATTACKER (SWUCombatDamage reads it from the attacker to
    // reduce the defender's counter-power); it's consumed there, so it's one-shot per attack. Ground-only
    // — a space defender is unaffected (the -0 HP is a no-op).
    if (($attacker->CardID ?? '') === 'SOR_212' && strpos($targetMzID, 'GroundArena') !== false) {
        AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2');
    }

    // Restore (CR 7.6.6 / keyword text "When this unit attacks, heal X damage from your base"):
    // fires on EVERY attack — a unit OR a base target — not just base attacks. Heal the attacker's
    // own base by the Restore value once per attack, here at attack time.
    $restoreVal = GetKeyword_Restore_Value($attacker);
    if ($restoreVal !== null && $restoreVal > 0) OnHealBase($player, $player, $restoreVal);

    // Step 1: collect On Attack / On Defense triggers, then flush them onto the
    // EffectStack with a COMBAT continuation that queues SWUCombatDamage once the
    // triggers fully resolve. With no triggers, queue combat damage directly.
    CollectCombatStep1Triggers($player, $attackerMzID, $targetMzID);
    $triggered = FlushCombatTriggerBag($player, $attackerMzID, $targetMzID);
    if ($triggered === 0) {
        $attackerUID = intval($attacker->UniqueID ?? 0);
        DecisionQueueController::AddDecision(
            $player, "CUSTOM", "SWUCombatDamage|{$attackerMzID}|{$targetMzID}|{$attackerUID}", 1
        );
    }
    // $triggered >= 1: the COMBAT continuation in SWU_TRIGGER_RESUME queues SWUCombatDamage.

    $playerID = $savedPID;
}

// Returns true when a SWU_TRIGGER_RESUME entry is present in either player's DQ.
// SWUCombatDamage must skip its own SWUAfterAction call in this case to
// avoid a double TurnPlayer swap when Ambush fires from the entry trigger path.
function _SWUInTriggerResumeMode(): bool {
    for ($p = 1; $p <= 2; $p++) {
        $dq = GetDecisionQueue($p);
        foreach ($dq as $entry) {
            $param = $entry->Param ?? '';
            if (str_starts_with($param, 'SWU_TRIGGER_RESUME')) return true;
        }
    }
    return false;
}

// DQ handler: resolve combat damage after Step 1 triggers have fully resolved.
global $customDQHandlers;
$customDQHandlers["SWUCombatDamage"] = function($player, $parts, $lastDecision) {
    global $playerID, $gDeferredReplacements;
    $savedPID     = $playerID;
    $playerID     = intval($player);
    $attackerMzID = $parts[0] ?? '';
    $targetMzID   = $parts[1] ?? '';
    $attackerUID  = intval($parts[2] ?? 0);

    $attacker = GetZoneObject($attackerMzID);
    // If the mzID is stale (attacker shifted indices after a mid-attack sacrifice),
    // fall back to scanning the arena by UniqueID.
    if (($attacker === null || (isset($attacker->removed) && $attacker->removed)) && $attackerUID > 0) {
        $zoneName = explode('-', $attackerMzID)[0];
        $zone = GetZone($zoneName);
        foreach ($zone as $idx => $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? 0) === $attackerUID) {
                $attacker = $u;
                $attackerMzID = $zoneName . '-' . $idx;
                break;
            }
        }
    }
    $target   = GetZoneObject($targetMzID);

    // Capture card IDs before damage may remove them
    $_logAttackerID = isset($attacker->CardID) ? $attacker->CardID : '';
    $_logTargetID   = ($target !== null && isset($target->CardID)) ? $target->CardID : '';
    $_logTargetZone = $targetMzID;
    $_logPlayer     = intval($player);

    if ($attacker === null || (isset($attacker->removed) && $attacker->removed)) {
        $playerID = $savedPID;
        if (!_SWUInTriggerResumeMode()) SWUAfterAction($player);
        return;
    }

    $attackPower = intval(ObjectCurrentPower($attacker));
    // Raid: +N power for this attack only (CR 7.6.7).
    $raidVal = GetKeyword_Raid_Value($attacker);
    if ($raidVal !== null && $raidVal > 0) $attackPower += $raidVal;
    // SOR_130 First Legion Snowtrooper: while attacking a DAMAGED unit, +2/+0 and
    // gains Overwhelm (both combat-time, depend on the defender's damage at declaration).
    $sor130VsDamaged = ($attacker->CardID === 'SOR_130'
        && $target !== null && empty($target->removed)
        && intval($target->Damage ?? 0) > 0);
    if ($sor130VsDamaged) $attackPower += 2;
    // SOR_071 Electrostaff: "While attached unit is defending, the attacker gets -1/-0." If the
    // defender (host of this upgrade) is being attacked, reduce the attacker's power by 1.
    if ($target !== null && empty($target->removed) && _SWUUnitHasUpgrade($target, 'SOR_071')) {
        $attackPower = max(0, $attackPower - 1);
    }
    // JTL_054 Gold Leader: "While this unit is defending, the attacker gets -1/-0."
    if ($target !== null && empty($target->removed) && ($target->CardID ?? '') === 'JTL_054') {
        $attackPower = max(0, $attackPower - 1);
    }
    // JTL_259 Retrofitted Airspeeder: "While attacking a space unit, this unit gets -1/-0."
    if (($attacker->CardID ?? '') === 'JTL_259' && $target !== null && empty($target->removed)
        && strpos((string)$targetMzID, 'SpaceArena') !== false) {
        $attackPower = max(0, $attackPower - 1);
    }
    // "Deals combat damage before the defender" ordering — the colloquial "Shoot First". Sources:
    // the SHOOT_FIRST marker (SOR_217 Shoot First grant) OR SOR_198 Han Solo's innate deal-first.
    // SOR_217's +1/+0 is a separate registry STAT_BUFF (token SOR_217) already folded into the
    // ObjectCurrentPower above — it is NOT added here, so deal-first and the buff are decoupled
    // (SOR_198 gets the ordering with NO +1/+0).
    $hasShootFirst = (is_array($attacker->TurnEffects ?? null) && in_array('SHOOT_FIRST', $attacker->TurnEffects))
        || (($attacker->CardID ?? '') === 'SOR_198');
    // JTL_185 Hound's Tooth: deals combat damage first while attacking an EXHAUSTED unit that DIDN'T
    // enter play this phase (the SWU_PLAYED_UNIT_{uid} flag marks units that entered this round).
    if (($attacker->CardID ?? '') === 'JTL_185' && $target !== null && empty($target->removed)
        && intval($target->Status ?? 1) === 0
        && GlobalEffectCount(intval($target->Controller ?? 0), 'SWU_PLAYED_UNIT_' . intval($target->UniqueID ?? 0)) <= 0) {
        $hasShootFirst = true;
    }
    // "+N power for this attack" one-shot bonuses (Surprise Strike SOR_220, attack-with
    // riders SOR_227/SOR_240). Summed into attack power and consumed so they don't persist
    // as a stat (the attacker's ObjectCurrentPower is unchanged before/after).
    if (is_array($attacker->TurnEffects ?? null)) {
        $keptTE = [];
        foreach ($attacker->TurnEffects as $te) {
            if (preg_match('/^SWU_ATK_POWER_(\d+)$/', (string)$te, $atkM)) $attackPower += intval($atkM[1]);
            else $keptTE[] = $te;
        }
        $attacker->TurnEffects = $keptTE;
    }
    // Jyn Erso (SOR_018): "the defender gets -1/-0" — reduces the power the defender deals back.
    // Two sources: a one-shot SWU_DEF_DEBUFF_N from her leader action ("for this attack", consumed
    // here), and her deployed passive (-1 while any friendly unit of the attacker's controller is
    // attacking — she herself counts). Applied to $defendPower in the unit-defender branch below.
    $defenderPowerDebuff = 0;
    if (is_array($attacker->TurnEffects ?? null)) {
        $keptDef = [];
        foreach ($attacker->TurnEffects as $te) {
            if (preg_match('/^SWU_DEF_DEBUFF_(\d+)$/', (string)$te, $defM)) $defenderPowerDebuff += intval($defM[1]);
            else $keptDef[] = $te;
        }
        $attacker->TurnEffects = $keptDef;
    }
    if (_SWULeaderDeployed(intval($attacker->Controller ?? $player), 'SOR_018')) $defenderPowerDebuff += 1;
    $defeatedCards = [];
    // Combat-hit context (Phase 7.2): captured here, consumed by CollectCombatStep3Triggers to fire
    // WhenDealsCombatDamage / WhenDefeats abilities (Rukh, Mace, Seventh Sister, SOR_088).
    $combatCtx = ['dealtToBase' => false, 'dealtToUnit' => false, 'defenderDefeated' => false,
                  'defenderIsLeader' => false, 'excess' => 0, 'attackerSelfDefeat' => false];
    // Capture attack-duration markers NOW (before SWUExpireTurnEffects('attack') strips them later):
    // SOR_150 Heroic Sacrifice's granted "when it deals combat damage: defeat it"; JTL_177 Stay on
    // Target's "deal damage to a base → draw"; JTL_193 I Have You Now's damage prevention on the attacker.
    $preventAttackerDmg = false;
    foreach (($attacker->TurnEffects ?? []) as $te) {
        $teBase = SWUParseTurnEffect((string)$te)['base'];
        if ($teBase === 'SOR_150') $combatCtx['attackerSelfDefeat'] = true;
        if ($teBase === 'JTL_177') $combatCtx['jtl177BaseDraw']    = true;
        if ($teBase === 'JTL_193') $preventAttackerDmg            = true;
    }

    $targetZone = explode('-', $targetMzID)[0];
    if ($targetZone === 'theirBase') {
        $GLOBALS['gInCombatDamage'] = true;
        SWUDealDamageToBase($attackPower, $player === 1 ? 2 : 1);
        $GLOBALS['gInCombatDamage'] = false;
        $combatCtx['dealtToBase'] = ($attackPower > 0);
        // (Restore now fires once per attack in ExecuteSWUAttack — for unit AND base targets.)
    } elseif ($target !== null && !isset($target->removed)) {
        $defendPower = max(0, intval(ObjectCurrentPower($target)) - $defenderPowerDebuff);
        $combatCtx['defenderIsLeader'] = IsLeaderUnit($target);

        // Saboteur (CR 7.6.9): defeat ALL shield tokens on defender before combat damage.
        if (HasKeyword_Saboteur($attacker) && is_array($target->Subcards ?? null)) {
            // $shieldSlot tracks each shield's render index (top-right orbs, order = Subcards order)
            // so its break plays at its own original position. All fire simultaneously (stagger
            // is exempted client-side).
            $shieldSlot = 0;
            foreach ($target->Subcards as &$sub) {
                $isShield = is_array($sub)
                    ? (empty($sub['removed']) && ($sub['CardID'] ?? '') === 'SOR_T02')
                    : (empty($sub->removed) && isset($sub->CardID) && $sub->CardID === 'SOR_T02');
                if (!$isShield) continue;
                if (is_array($sub)) $sub['removed'] = true; else $sub->removed = true;
                SWUQueueShieldBreakAnim($targetMzID, intval($player), $shieldSlot);
                $shieldSlot++;
            }
            unset($sub);
        }

        if ($hasShootFirst) {
            // Shoot First (SOR_217): attacker deals damage before defender.
            // If the defender is defeated, it deals no combat damage (CR card text).
            if (!SWUConsumeShieldToken($target)) {
                $target->Damage = intval($target->Damage) + $attackPower;
                SWUQueueDamageAnim($targetMzID, $attackPower, intval($player));
                $combatCtx['dealtToUnit'] = ($attackPower > 0);
            } else {
                SWUQueuePreventedAnim($targetMzID, intval($player));
                SWUQueueShieldBreakAnim($targetMzID, intval($player));
            }
            $defenderRemainingHP = intval(ObjectCurrentHP($target)) - intval($target->Damage);
            if ($defenderRemainingHP > 0) {
                // Defender survived — take counter-damage normally (unless prevented this attack).
                if ($preventAttackerDmg) {
                    SWUQueuePreventedAnim($attackerMzID, intval($player));
                } elseif (!SWUConsumeShieldToken($attacker)) {
                    $attacker->Damage = intval($attacker->Damage) + $defendPower;
                    SWUQueueDamageAnim($attackerMzID, $defendPower, intval($player));
                } else {
                    SWUQueuePreventedAnim($attackerMzID, intval($player));
                    SWUQueueShieldBreakAnim($attackerMzID, intval($player));
                }
            }
            // Defender at 0 HP → attacker takes no counter-damage.
        } else {
            // Normal simultaneous combat damage (CR 7.6.3).
            // Shield (CR 7.6.5): attacker's shield absorbs all counter-damage in one hit.
            if ($preventAttackerDmg) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));   // JTL_193: all damage to it prevented
            } elseif (!SWUConsumeShieldToken($attacker)) {
                $attacker->Damage = intval($attacker->Damage) + $defendPower;
                SWUQueueDamageAnim($attackerMzID, $defendPower, intval($player));
            } else {
                SWUQueuePreventedAnim($attackerMzID, intval($player));
                SWUQueueShieldBreakAnim($attackerMzID, intval($player));
            }
            // Shielded (CR 7.6.5): a shield token absorbs all combat damage in one hit; consume it instead.
            if (!SWUConsumeShieldToken($target)) {
                $target->Damage = intval($target->Damage) + $attackPower;
                SWUQueueDamageAnim($targetMzID, $attackPower, intval($player));
                $combatCtx['dealtToUnit'] = ($attackPower > 0);
            } else {
                SWUQueuePreventedAnim($targetMzID, intval($player));
                SWUQueueShieldBreakAnim($targetMzID, intval($player));
            }
        }

        // Lethality uses CURRENT HP (printed + upgrades + "for this phase" buffs − debuffs), not
        // printed HP — so a +HP buff/upgrade keeps a unit alive in combat. When such a buff expires
        // at RegroupPhaseStart, the now-over-damaged unit is defeated by the sweep there.
        $attackerHP = intval(ObjectCurrentHP($attacker)) - intval($attacker->Damage);
        $defenderHP = intval(ObjectCurrentHP($target))   - intval($target->Damage);
        $combatCtx['defenderDefeated'] = ($defenderHP <= 0 && !SWUImmuneToHpDefeat($target));
        $combatCtx['excess'] = $combatCtx['defenderDefeated'] ? max(0, -$defenderHP) : 0;

        // Keep $playerID = $player (attacker's perspective) throughout so that
        // mzIDs like "myGroundArena-0" / "theirGroundArena-0" resolve correctly.
        $atkRep = ($attackerHP <= 0 && !SWUImmuneToHpDefeat($attacker)) ? _SWUUnitDefeatReplacement($attacker) : null;
        if ($atkRep !== null) {
            // Defeat-replacement (JTL_049): park the would-be-defeated attacker; resolved at action end.
            $gDeferredReplacements[] = ['uid' => intval($attacker->UniqueID ?? 0),
                'controller' => intval($attacker->Controller ?? $player), 'cardID' => $attacker->CardID, 'kind' => $atkRep['kind']];
        } elseif ($attackerHP <= 0 && !SWUImmuneToHpDefeat($attacker)) {
            $defeatedCards[] = ['player' => intval($attacker->Controller), 'cardID' => $attacker->CardID, 'mzID' => $attackerMzID];
            $atkOwner = intval($attacker->Owner ?? $player);
            if (strpos(CardType($attacker->CardID) ?? '', 'Leader') !== false) {
                SWUReturnLeaderToZone($atkOwner, $attackerMzID);
            } else {
                $atkHasSecondChance = _SWUUnitHasUpgrade($attacker, 'SHD_053');
                $atkObj = GetZoneObject($attackerMzID);
                if ($atkObj) {
                    // CR 8.34.4: rescue any captives guarded by the attacker before it leaves play.
                    SWURescueCaptivesOf($atkObj);
                    // If the host carries a leader-pilot upgrade, return that leader to zone before discarding.
                    SWUReturnLeaderPilotSubcards($atkObj, $atkOwner);
                    _SWUDeferPilotDefeatReplacements($atkObj); // JTL_094 pilot-upgrade defeat-replacement
                    SWUDiscardHostSubcards($atkObj);           // remaining upgrades/pilots → each owner's discard
                    $atkObj->removed = true;
                    SWUAddToDiscard($atkOwner, $atkObj->CardID, 'PLAY', $atkHasSecondChance ? 'TPF' : '', $atkObj);
                    AddGlobalEffects($atkOwner, 'SWU_DEFEATED_CARD_' . $atkObj->CardID);
                }
                AddGlobalEffects(GetOpponent($player), 'SWU_ENEMY_DEFEATED');
                AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_FRIENDLY_DEFEATED');
            }
        }
        $defRep = ($defenderHP <= 0 && $target !== null && empty($target->removed) && !SWUImmuneToHpDefeat($target))
            ? _SWUUnitDefeatReplacement($target) : null;
        if ($defRep !== null) {
            // Defeat-replacement (JTL_049): park the would-be-defeated defender; resolved at action end.
            $gDeferredReplacements[] = ['uid' => intval($target->UniqueID ?? 0),
                'controller' => intval($target->Controller ?? ($player === 1 ? 2 : 1)), 'cardID' => $target->CardID, 'kind' => $defRep['kind']];
        } elseif ($defenderHP <= 0 && $target !== null && empty($target->removed) && !SWUImmuneToHpDefeat($target)) {
            $defeatedCards[] = ['player' => intval($target->Controller), 'cardID' => $target->CardID, 'mzID' => $targetMzID];
            $defOwner = intval($target->Owner ?? ($player === 1 ? 2 : 1));
            if (strpos(CardType($target->CardID) ?? '', 'Leader') !== false) {
                SWUReturnLeaderToZone($defOwner, $targetMzID);
            } else {
                $defHasSecondChance = _SWUUnitHasUpgrade($target, 'SHD_053');
                $defObj = GetZoneObject($targetMzID);
                if ($defObj) {
                    // CR 8.34.4: rescue any captives guarded by the defender before it leaves play.
                    SWURescueCaptivesOf($defObj);
                    // If the host carries a leader-pilot upgrade, return that leader to zone before discarding.
                    SWUReturnLeaderPilotSubcards($defObj, $defOwner);
                    _SWUDeferPilotDefeatReplacements($defObj); // JTL_094 pilot-upgrade defeat-replacement
                    SWUDiscardHostSubcards($defObj);           // remaining upgrades/pilots → each owner's discard
                    $defObj->removed = true;
                    SWUAddToDiscard($defOwner, $defObj->CardID, 'PLAY', $defHasSecondChance ? 'TPF' : '', $defObj);
                    AddGlobalEffects($defOwner, 'SWU_DEFEATED_CARD_' . $defObj->CardID);
                }
                AddGlobalEffects($player, 'SWU_ENEMY_DEFEATED');
                AddGlobalEffects(intval($target->Controller ?? GetOpponent($player)), 'SWU_FRIENDLY_DEFEATED');
            }
            // Overwhelm: excess damage (negative $defenderHP) spills to the opponent's base (CR 7.6.4).
            if ($defenderHP < 0 && (HasKeyword_Overwhelm($attacker) || $sor130VsDamaged)) {
                $overflowAmt = -$defenderHP;
                $GLOBALS['gInCombatDamage'] = true;
                SWUDealDamageToBase($overflowAmt, $player === 1 ? 2 : 1);
                $GLOBALS['gInCombatDamage'] = false;
                AddGameLogEntry('OVERWHELM', 'Overwhelm: ' . $overflowAmt . ' damage to P' . (3 - intval($player)) . '\'s base');
            }
        }
    }

    // "For this attack" effects end now that combat damage is fully resolved: drop attack-duration
    // registry tokens (SOR_217 Shoot First's +1/+0 buff, the SHOOT_FIRST deal-first marker). The
    // legacy inline one-shots (SWU_ATK_POWER_*/SWU_DEF_DEBUFF_*) were already consumed above; this
    // is a no-op for them. (Fizzled attacks that never reach here are swept by the phase expiry.)
    SWUExpireTurnEffects(SWU_DUR_ATTACK);

    // Step 3: When Defeated + After Attack triggers — must run before CleanupRemovedCards so
    // GetZoneObject can still find defeated units (marked removed=true but still in the array).
    // Cleanup after this point to avoid PHP auto-vivifying null slots in emptied arena arrays.
    CollectCombatStep3Triggers($player, $attackerMzID, $targetMzID, $defeatedCards, $combatCtx);
    DecisionQueueController::CleanupRemovedCards();

    // Log the attack
    if ($_logAttackerID !== '') {
        $atkRef = GameLogCardRef($_logAttackerID);
        if (str_starts_with($_logTargetZone, 'theirBase')) {
            $targetLabel = 'P' . (3 - $_logPlayer) . '\'s base';
        } else {
            $targetLabel = $_logTargetID !== '' ? GameLogCardRef($_logTargetID) : 'a unit';
        }
        $defeatSuffix = '';
        foreach ($defeatedCards as $d) {
            $defeatSuffix .= ' — ' . GameLogCardRef($d['cardID']) . ' defeated';
        }
        AddGameLogEntry('ATTACK',
            'P' . $_logPlayer . '\'s ' . $atkRef . ' attacked ' . $targetLabel . $defeatSuffix);
    }

    $playerID = $savedPID;
    if (!_SWUInTriggerResumeMode()) SWUAfterAction($player);
};

// Returns valid attack targets for $attacker in $arenaName, respecting Sentinel/Saboteur (CR 5.9).
// $opponent is the opposing player number.
// SOR_142 Sabine Wren — true while there are ≥3 distinct aspects among OTHER friendly units (her
// controller's units, excluding herself). Aspect icons are comma-separated in CardAspect.
function _SWUSabineProtected(object $sabine): bool {
    $ctrl    = intval($sabine->Controller ?? 0);
    $selfUID = intval($sabine->UniqueID ?? -1);
    $aspects = [];
    foreach (GetUnitsInPlay($ctrl) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? -2) === $selfUID) continue;
        foreach (explode(',', CardAspect($u->CardID ?? '') ?? '') as $a) {
            $a = trim($a);
            if ($a !== '') $aspects[$a] = true;
        }
    }
    return count($aspects) >= 3;
}

function SWUGetValidAttackTargets(int $opponent, $attackerObj, string $arenaName, bool $noBases = false): array {
    $opArenaZone = "their{$arenaName}";
    $oppUnits    = [];
    $sentinels   = [];

    $oppArena = GetZone($opArenaZone);
    for ($i = 0; $i < count($oppArena); $i++) {
        $u = $oppArena[$i];
        if ($u === null || !empty($u->removed)) continue;
        // SOR_142 Sabine Wren: "While there are at least 3 aspects among other friendly units, this
        // unit can't be attacked (unless she gains Sentinel)." Exclude her as a valid target — but if
        // she has Sentinel, the protection is off (she becomes a forced Sentinel target instead).
        if (($u->CardID ?? '') === 'SOR_142' && !HasKeyword_Sentinel($u) && _SWUSabineProtected($u)) continue;
        $oppUnits[] = "{$opArenaZone}-{$i}";
        if (HasKeyword_Sentinel($u)) $sentinels[] = "{$opArenaZone}-{$i}";
    }

    // If the attacker has Saboteur, Sentinel is bypassed — all targets valid.
    $hasSentinelRestriction = !empty($sentinels) && !HasKeyword_Saboteur($attackerObj);

    if ($hasSentinelRestriction) {
        // Must attack a Sentinel; base and non-Sentinel units are off-limits.
        return $sentinels;
    }

    // SOR_212 Strafing Gunship: "This unit can attack units in the ground arena." A space unit that
    // ALSO targets enemy GROUND units (cross-arena). Combat resolution is mzID-driven, so adding the
    // ground mzIDs here is all that's needed. (Cross-arena Sentinel/Sabine is an edge — not handled.)
    if ($attackerObj !== null && ($attackerObj->CardID ?? '') === 'SOR_212' && $arenaName === 'SpaceArena') {
        $groundArena = GetZone('theirGroundArena');
        for ($i = 0; $i < count($groundArena); $i++) {
            $u = $groundArena[$i];
            if ($u === null || !empty($u->removed)) continue;
            $oppUnits[] = "theirGroundArena-{$i}";
        }
    }
    // JTL_259 Retrofitted Airspeeder: "This unit can attack space units." A GROUND unit that also
    // targets enemy SPACE units (cross-arena).
    if ($attackerObj !== null && ($attackerObj->CardID ?? '') === 'JTL_259' && $arenaName === 'GroundArena') {
        $spaceArena = GetZone('theirSpaceArena');
        for ($i = 0; $i < count($spaceArena); $i++) {
            $u = $spaceArena[$i];
            if ($u === null || !empty($u->removed)) continue;
            $oppUnits[] = "theirSpaceArena-{$i}";
        }
    }

    // No Sentinel restriction — include all units, plus the base unless the attack forbids
    // it (SOR_110 Frontline Shuttle "for this attack", or SOR_072 Entrenched on the attacker).
    if ($attackerObj !== null && _SWUUnitHasUpgrade($attackerObj, 'SOR_072')) $noBases = true;
    // JTL_092 Scramble Fighters: tokens marked "can't attack bases for this phase".
    if ($attackerObj !== null && is_array($attackerObj->TurnEffects ?? null)
        && in_array('CANT_ATTACK_BASES', $attackerObj->TurnEffects)) $noBases = true;
    if (!$noBases) {
        $oppBase = GetZone("theirBase");
        for ($i = 0; $i < count($oppBase); $i++) {
            $b = $oppBase[$i];
            if ($b === null || !empty($b->removed)) continue;
            $oppUnits[] = "theirBase-{$i}";
        }
    }
    return $oppUnits;
}

// Like SWUGetValidAttackTargets but Ambush-specific: units only, never the base (CR 5.9.a).
function SWUGetValidAmbushTargets(int $opponent, $attackerObj, string $arenaName): array {
    $opArenaZone = "their{$arenaName}";
    $oppUnits    = [];
    $sentinels   = [];

    $oppArena = GetZone($opArenaZone);
    for ($i = 0; $i < count($oppArena); $i++) {
        $u = $oppArena[$i];
        if ($u === null || !empty($u->removed)) continue;
        $oppUnits[] = "{$opArenaZone}-{$i}";
        if (HasKeyword_Sentinel($u)) $sentinels[] = "{$opArenaZone}-{$i}";
    }

    $hasSentinelRestriction = !empty($sentinels) && !HasKeyword_Saboteur($attackerObj);
    return $hasSentinelRestriction ? $sentinels : $oppUnits;
}

// Declare an attack with a ready unit. Exhausts the attacker and queues
// target selection or fires directly if only one target exists.
function BeginSWUAttack($player, $attackerMzID, bool $noBases = false) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $attacker = GetZoneObject($attackerMzID);
    if ($attacker === null || (isset($attacker->removed) && $attacker->removed)) {
        $playerID = $savedPID;
        return;
    }
    // JTL_059 Corporate Defense Shuttle: "This unit can't attack." Hard no-op.
    if (($attacker->CardID ?? '') === 'JTL_059') {
        $playerID = $savedPID;
        return;
    }

    $attackedUid = intval($attacker->UniqueID ?? 0);
    AddGlobalEffects($player, 'SWU_ATTACKED_' . $attackedUid);  // any unit attacked this phase (SOR_245)
    if (HasTrait($attacker->CardID, 'Mandalorian')) {
        AddGlobalEffects($player, 'SWU_ATTACKED_MANDALORIAN_' . $attackedUid);
    }
    // JTL_006 Darth Vader: "attacked with a non-token Vehicle this phase." A deployed Leader Unit
    // counts if it has the Vehicle trait; Token Units (TIE tokens etc.) are excluded.
    if (HasTrait($attacker->CardID, 'Vehicle') && EffectiveCardType($attacker) !== 'Token Unit') {
        AddGlobalEffects($player, 'SWU_ATTACKED_VEHICLE');
    }
    // JTL_012 Luke Skywalker: "attacked with a Fighter unit this phase."
    if (HasTrait($attacker->CardID, 'Fighter')) {
        AddGlobalEffects($player, 'SWU_ATTACKED_FIGHTER');
    }

    // Exhaust the attacker (CR 6.3.1). This function never gates on the attacker being ready
    // (the FSM does, upstream), so a granted "attack even if exhausted" attack (SOR_110)
    // works by calling here directly on an already-exhausted unit.
    $attacker->Status = 0;

    $arena        = $attacker->Location; // "GroundArena" or "SpaceArena"
    $opponent     = OtherPlayer($player);
    $validTargets = SWUGetValidAttackTargets($opponent, $attacker, $arena, $noBases);

    if (empty($validTargets)) {
        // Nothing to attack; undo exhaust
        $attacker->Status = 1;
        $playerID = $savedPID;
        return;
    }

    if (count($validTargets) === 1) {
        ExecuteSWUAttack($player, $attackerMzID, $validTargets[0]);
    } else {
        // Queue target selection
        $targetStr = implode("&", $validTargets);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_an_attack_target");
        DecisionQueueController::AddDecision($player, "CUSTOM",   "SWUResolveAttack|{$attackerMzID}", 1);
    }

    $playerID = $savedPID;
    // Drain the DQ: processes SWUCombatDamage immediately for single-target attacks,
    // stops harmlessly at MZCHOOSE for multi-target attacks (player responds via DECISION).
    (new DecisionQueueController())->ExecuteStaticMethods($player, "-");
}

// DQ handler: resolve the chosen attack target.
global $customDQHandlers;
$customDQHandlers["SWUResolveAttack"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === "PASS" || $lastDecision === "" || $lastDecision === "-") {
        // Player had no valid target or cancelled; undo exhaust and give action back
        $attackerMzID = $parts[0] ?? '';
        if ($attackerMzID !== '') {
            global $playerID;
            $savedPID = $playerID;
            $playerID = intval($player);
            $attacker = GetZoneObject($attackerMzID);
            if ($attacker !== null) $attacker->Status = 1;
            $playerID = $savedPID;
        }
        return;
    }
    $attackerMzID = $parts[0] ?? '';
    ExecuteSWUAttack($player, $attackerMzID, $lastDecision);
};

// Dispatch the OnAttack ability for the given unit mzID.
function OnAttackTrigger($player, $mzID): void {
    global $onAttackAbilities, $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if ($obj !== null && !empty($obj->CardID)) {
        $key = $obj->CardID . ':0';
        if (isset($onAttackAbilities[$key])) $onAttackAbilities[$key]($player, $mzID);
    }
    $playerID = $savedPID;
}

// Dispatch an OnAttack ability granted by an upgrade ($upgradeCardID) attached
// to the attacking unit at $unitMzID. Keyed identically to innate OnAttack
// abilities (cardID:0) but resolved against the host unit's arena mzID.
function OnAttackFromUpgradeTrigger(int $player, string $upgradeCardID, string $unitMzID): void {
    global $onAttackAbilities, $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $key = $upgradeCardID . ':0';
    if (isset($onAttackAbilities[$key])) $onAttackAbilities[$key]($player, $unitMzID);
    $playerID = $savedPID;
}

// Upgrade-granted On-Attack-END dispatch (JTL_197). $unitMzID is the HOST attacker.
function OnAttackEndFromUpgradeTrigger(int $player, string $upgradeCardID, string $unitMzID): void {
    global $onAttackEndFromUpgradeAbilities, $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    if (isset($onAttackEndFromUpgradeAbilities[$upgradeCardID])) {
        $onAttackEndFromUpgradeAbilities[$upgradeCardID]($player, $unitMzID);
    }
    $playerID = $savedPID;
}

// Upgrade-granted "when attached unit is attacked" dispatch (JTL_260). $player = the ATTACKER (active
// player); $defenderMzID is the attacked host, in the attacker's frame.
function OnAttackedFromUpgradeTrigger(int $player, string $upgradeCardID, string $defenderMzID): void {
    global $onAttackedFromUpgradeAbilities, $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    if (isset($onAttackedFromUpgradeAbilities[$upgradeCardID])) {
        $onAttackedFromUpgradeAbilities[$upgradeCardID]($player, $defenderMzID);
    }
    $playerID = $savedPID;
}

// Calculate the total attack power for a combat:
//   base unit power  +  sum of power from all attack cards in the attacker's intent.
function GetTotalAttackPower($attackerObj, $player) {
    $totalPower = ObjectCurrentPower($attackerObj);

    return $totalPower;
}
