<?php

// Overwhelm excess -> the defending player's base. THE single implementation, shared by the two paths
// that can produce it: the normal "defender defeated by combat damage" spill, and the CR step 4.c case
// where the defender left play BEFORE damage (all of it becomes excess). Keeping one copy matters
// because the flag semantics below are subtle and were previously stated in only one place.
function _SWUOverwhelmSpillToBase(int $player, string $targetMzID, $attacker, int $overflowAmt, array &$combatCtx): int {
    if ($overflowAmt <= 0) return 0;
    $GLOBALS['gInCombatDamage'] = true;
    SWUDealDamageToBase($overflowAmt, SWUMzOwner($targetMzID, $player)); // Twin Suns: the overwhelmed defender's own base
    $GLOBALS['gInCombatDamage'] = false;
    $combatCtx['baseCombatDmg'] += max(0, $overflowAmt); // overwhelm counts for LOF_025
    // Overwhelm excess IS combat damage the attacker deals to the base (CR 7.d), so it must trigger
    // "when this unit deals (combat) damage to a base" abilities — JTL_177 Stay on Target's granted draw,
    // ASH_162, SEC_150/147/205, LAW_046 Chirrut, etc. — exactly as a direct base hit would.
    $combatCtx['dealtToBase'] = true;
    // ⚠ CR 7.d/7.f: it is combat damage to the base but the unit is NOT considered to have ATTACKED that
    // base. So set the "damaged" flags and deliberately NOT SWU_DEALT_BASEDMG (the "attacked" flag used by
    // SHD_088/SHD_106). SEC_012 Cassian reads the damaged flag; only when the base owner is an opponent.
    $ovAuid  = intval($attacker->UniqueID ?? 0);
    $ovActrl = intval($attacker->Controller ?? $player);
    $ovOwner = SWUMzOwner($targetMzID, $player);
    if ($ovAuid > 0 && intval($ovOwner) !== $ovActrl) AddGlobalEffects($ovActrl, 'SWU_UNITDMGBASE_' . $ovAuid);
    if ($ovAuid > 0) AddGlobalEffects($ovActrl, 'SWU_DMGDBASE_' . $ovAuid . '_' . intval($ovOwner));
    return $overflowAmt;
}


// "Deals combat damage before the defender" — the colloquial "Shoot First" ordering. Sources: the
// SHOOT_FIRST turn-effect marker (SOR_217 Shoot First's grant, SOR_219's conditional grant), SOR_198
// Han Solo's and SHD_234 Incinerator Trooper's innate deal-first, and ASH_202 Carson Teva's Support
// grant (own + lent). Shared by the LAW_086 offer gate and the damage resolver so the two cannot drift.
function _SWUAttackerDealsDamageFirst($attacker): bool {
    return (is_array($attacker->TurnEffects ?? null) && in_array('SHOOT_FIRST', $attacker->TurnEffects))
        || (($attacker->CardID ?? '') === 'SOR_198')
        || (($attacker->CardID ?? '') === 'SHD_234')
        || _SWUAttackerGrants($attacker, 'ASH_202');
}

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
    // SEC_046 Galen Erso — if the enemy Galen named "Shield", the owner's Shield tokens have lost their
    // damage-prevention ability: don't consume/prevent (the token stays attached but does nothing).
    // ⚠ Keyed on the host's CONTROLLER, not its Owner: per CR 6 a TOKEN has no printed owner — whoever
    // controls it owns it. On a stolen host the two diverge (owner stays with the original player while
    // control moves), and it is the CONTROLLER that owns the shield. Reading Owner here let a shield
    // stolen from Galen's own side keep preventing.
    if (_SWUGalenSuppressesCard(intval($unit->Controller ?? $unit->Owner ?? 0), 'SOR_T02')) return false;
    foreach ($unit->Subcards as $key => $sub) {
        $cardID  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        $removed = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
        if (!$removed && $cardID === 'SOR_T02') {
            array_splice($unit->Subcards, $key, 1);
            // CR: a Shield token consumed to prevent damage is DEFEATED — fire the "a friendly upgrade was
            // defeated this phase" observers (ASH_039 Baylan flag / ASH_161 Zeb deal-1). Token upgrades count.
            $shCtrl  = intval($unit->Controller ?? $unit->Owner ?? 0);
            $shOwner = intval($unit->Owner ?? $shCtrl);
            if ($shCtrl > 0) _SWUOnUpgradeDefeated($shCtrl, 'SOR_T02', $unit, $shOwner);
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
    // Telemetry: this is the universal damage-application hook (base + combat + ability all route here).
    // The absolute mzID is p1/p2-prefixed, so it identifies the seat that took the damage.
    if (function_exists('SWUTelemetryBumpTurn')) {
        $taker = (strpos($abs, 'p1') === 0) ? 1 : ((strpos($abs, 'p2') === 0) ? 2 : 0);
        if ($taker === 1 || $taker === 2) {
            SWUTelemetryBumpTurn($taker, 'damageTaken', intval($amount));
            SWUTelemetryBumpTurn(($taker === 1) ? 2 : 1, 'damageDealt', intval($amount));
        }
    }
}

// Tilt a card to the exhausted angle immediately, without waiting for the board re-render.
// Needed because a SINGLE-TARGET attack declares and resolves in ONE update (see the DQ drain at the
// end of BeginSWUAttack) — so the attacker's exhaust and the target's damage animation arrive together,
// and the render that would show the exhaust is held until the damage animation finishes. Exhausting is
// a COST (CR 6.3.1, paid at declaration), so it has to land first.
// dim: SWU's exhausted look is a 9° tilt AND a shade, so the pre-render catch-up has to do both — a
// tilted-but-bright attacker still darkens abruptly at the re-render. An attacking unit picks this up
// through its lunge: the lunge clones the card AFTER this frame has run, so the clone flies shaded.
function SWUQueueExhaustAnim(string $relMzID, int $perspective, ?int $uniqueID = null): void {
    $abs = ConvertMzIDToAbsolute($relMzID, intval($perspective));
    if ($abs === '') return;
    QueueExhaustAnimation($abs, uniqueID: $uniqueID, dim: true);
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

// ── Card-motion animation helpers ───────────────────────────────────────────
// Thin wrappers over the shared engine primitives (Core/EngineActionRunner.php), matching the
// damage/heal helpers above: $rel* args are perspective-relative and converted to absolute here.
// Queue-only — these never touch gamestate. An unresolvable mzID means NO animation (never a wrong
// one): the board simply renders without motion.
//
// ⚠ Nothing automated can observe these. The schema suite bypasses ProcessInput, and
// SWUSim/TestSchemaStep.php stubs the animation functions to no-ops (ConvertMzIDToAbsolute included,
// which returns '' there — so every call below early-returns under test). Verification is the
// hand-loaded schemas in SWUSim/Tests/Visual/.

// Attacker leans toward what it is attacking. Fires for a unit OR a base target — a base is a real
// board element with its own mzID, and base attacks are the commonest attack in the game.
function SWUQueueLungeAnim(string $attackerRel, string $targetRel, int $perspective,
                           ?int $atkUid = null, ?int $tgtUid = null): void {
    // The animation layer is NOT loaded on every path that runs game logic — CreateGame.php includes
    // GameLogic.php but not Core/EngineActionRunner.php, so pregame setup (QueuePregameSetup ->
    // DoDrawCard) would fatal on an undefined function. Same guard AzukiDeck uses.
    if (!function_exists('ConvertMzIDToAbsolute') || !function_exists('QueueCardLungeAnimation')) return;
    $absAtk = ConvertMzIDToAbsolute($attackerRel, intval($perspective));
    $absTgt = ConvertMzIDToAbsolute($targetRel, intval($perspective));
    if ($absAtk === '' || $absTgt === '') return;
    QueueCardLungeAnimation($absAtk, $absTgt, 360, true, $atkUid, $tgtUid, 0.7);
}

// Slide a card from one zone to another. The client plays all zone moves queued in one frame in
// PARALLEL with a 60ms per-card stagger, and blocks for the MAX rather than the sum — so a burst
// (e.g. returning 3 resources at once) costs ~540ms, not 1.3s. No pacing work is needed here.
function SWUQueueZoneMoveAnim(string $fromRel, string $toRel, int $perspective,
                              int $durationMs = 420, ?int $uid = null, ?int $onlySeat = null): void {
    // See SWUQueueLungeAnim: the animation layer is absent during game creation (the opening-hand
    // DoDrawCard runs there), so this must degrade to a no-op rather than fatal.
    if (!function_exists('ConvertMzIDToAbsolute') || !function_exists('QueueZoneMoveAnimation')) return;
    $absFrom = ConvertMzIDToAbsolute($fromRel, intval($perspective));
    $absTo   = ConvertMzIDToAbsolute($toRel, intval($perspective));
    if ($absFrom === '' || $absTo === '') return;
    // $onlySeat scopes the slide to one viewer — used for moves into a self-visible zone.
    QueueZoneMoveAnimation($absFrom, $absTo, $durationMs, true, $uid, null, 0, $onlySeat);
}

// True when a card CEASES to exist on leaving play instead of entering a discard pile — so there is
// no destination for an arena->discard slide to fly to. There is no general token predicate in the
// codebase (SWUIsCreditToken is credit-only); this mirrors the exact condition SWUAddToDiscard uses
// to enforce the cease rule, factored out so the three defeat sites share one copy.
function _SWUCardCeasesOnLeavePlay(string $cardID): bool {
    $t = (string)(CardType($cardID) ?? '');
    return strncmp($t, 'Token', 5) === 0 || $t === 'Credit Token' || $t === 'Force Token';
}

// Deal $damage to player $targetPlayer's base. Sets flash on win.
// LOF_252 The Daughter — queue the base owner's "may use the Force → heal 2 from your base" reaction when
// they control her (SWUQueueMayUseTheForce no-ops if they don't currently hold the Force).
function _SWUCollectLof252Reaction(int $targetPlayer): void {
    foreach (GetField($targetPlayer) as $u) {
        if (empty($u->removed) && ($u->CardID ?? '') === 'LOF_252') {
            SWUQueueMayUseTheForce($targetPlayer, "Use_the_Force_to_heal_2_damage_from_your_base?", "LOF_252#0");
            return;
        }
    }
}

// ASH_160 Kachirho Militia — ready the base owner's Kachirho(s) when an enemy ground unit attacks their
// base (once each round per copy). Inline, no decision.
function _SWUShd241ShieldOnBaseAttack(int $baseOwner, string $attackerArena): void {
    if ($baseOwner <= 0) return;
    $has = false;
    foreach (GetUnitsInPlay($baseOwner) as $u) {
        if (empty($u->removed) && ($u->CardID ?? '') === 'SHD_241') { $has = true; break; }
    }
    if (!$has) return;
    global $playerID; $playerID = $baseOwner;
    $zone = ($attackerArena === 'Space') ? 'mySpaceArena' : 'myGroundArena';
    $targets = [];
    foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    // Mandatory shield. Single target resolves inline (mid-combat cross-player choices are fragile); a
    // multi-unit choice is queued for the base owner.
    if (count($targets) === 1) {
        DoGiveShieldToken($baseOwner, $targets[0]);
    } else {
        SWUQueueChooseTarget($baseOwner, $targets, "Give_a_Shield_to_a_friendly_unit_in_the_attacker's_arena", "GIVE_SHIELD");
    }
}

// TWI_166 Aurra Sing — "When an enemy ground unit attacks your base: Ready this unit." (No once-per-round.)
function _SWUTwi166ReadyOnBaseAttack(int $baseOwner): void {
    if ($baseOwner <= 0) return;
    foreach (GetUnitsInPlay($baseOwner) as $u) {
        if (empty($u->removed) && ($u->CardID ?? '') === 'TWI_166') $u->Status = 1; // ready this unit
    }
}

function _SWUAsh160ReadyOnBaseAttack(int $baseOwner): void {
    if ($baseOwner <= 0) return;
    foreach (GetUnitsInPlay($baseOwner) as $u) {
        if (empty($u->removed) && ($u->CardID ?? '') === 'ASH_160') {
            $uid = intval($u->UniqueID ?? 0);
            if (GlobalEffectCount($baseOwner, 'SWU_ASH160_USED_' . $uid) > 0) continue;   // once each round
            AddGlobalEffects($baseOwner, 'SWU_ASH160_USED_' . $uid);
            $u->Status = 1;   // ready this unit
        }
    }
}

// ASH_204 Blade Three — "When your base is dealt damage: give an Advantage token to this unit." Controller-
// based field observer; mandatory + non-interactive, so the token is added inline during the damage event
// (works even when the base owner is the non-active player in combat). Each ASH_204 the owner controls.
function _SWUCollectAsh204Reaction(int $targetPlayer): void {
    global $playerID; $savedPID = $playerID; $playerID = intval($targetPlayer);
    foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && ($o->CardID ?? '') === 'ASH_204') DoGiveAdvantageToken(intval($targetPlayer), $mz);
    }
    $playerID = $savedPID;
}

// TWI_017 Chancellor Palpatine (Heroism face) — "If a friendly Heroism unit was defeated this phase, …".
// Set alongside every SWU_FRIENDLY_DEFEATED, but only when the defeated unit's printed aspects include
// Heroism. Cleared at RegroupPhaseStart. (Kept as a per-phase count-flag like SWU_FRIENDLY_DEFEATED.)
function _SWUMarkHeroismDefeated(int $controller, ?string $cardID): void {
    if ($controller > 0 && $cardID && strpos(CardAspect($cardID) ?? '', 'Heroism') !== false) {
        AddGlobalEffects($controller, 'SWU_FRIENDLY_HEROISM_DEFEATED');
    }
}

function SWUDealDamageToBase($damage, $targetPlayer, $damager = null, $isIndirect = false) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($targetPlayer);
    $base = &GetBase($targetPlayer);
    for ($i = 0; $i < count($base); $i++) {
        if (isset($base[$i]->removed) && $base[$i]->removed) continue;
        // Base damage is UNPREVENTABLE when it is indirect ($isIndirect) OR its source is a friendly
        // Underworld card while ASH_196 Gorian's Corsair is in play (_SWUDamageUnpreventable on the threaded
        // combat attacker). Unpreventable base damage bypasses BOTH the Close the Shield Gate one-shot
        // (JTL_074) and the At Attin cap (ASH_070) below.
        $baseDmgUnpreventable = $isIndirect
            || ($damager !== null && is_object($damager) && _SWUDamageUnpreventable($damager));
        // JTL_074 Close the Shield Gate — "the next time damage would be dealt to this base this phase,
        // prevent it." One-shot per chosen base, consumed here; any leftover is cleared at regroup.
        // Unpreventable damage still CONSUMES the one-shot (it is "the next damage") but is NOT prevented —
        // it lands in full and the shield is spent, so subsequent damage lands too.
        if (intval($damage) > 0 && GlobalEffectCount(intval($targetPlayer), 'SWU_SHIELD_GATE') > 0) {
            RemoveGlobalEffect(intval($targetPlayer), 'SWU_SHIELD_GATE');
            if (!$baseDmgUnpreventable) {
                SetFlashMessage("Damage to the base was prevented (Close the Shield Gate).");
                $playerID = $savedPID;
                return;
            }
        }
        // HMW_081 Alliance Shield Generator (Fortify) — "If attached base would be dealt 5 or more damage,
        // prevent that damage. If you do, defeat this upgrade and draw a card." A PREVENTION, so it does
        // not apply to UNPREVENTABLE damage (indirect / ASH_196 Gorian's Corsair); and it is conditional,
        // not a one-shot shield, so a sub-threshold hit neither triggers nor consumes it.
        // Checked BEFORE the ASH_070 cap so the threshold reads the damage as it would be dealt: capping
        // 6 down to 4 first would silently disarm the generator. Both effects belong to the base's
        // controller, and full prevention plus a draw is strictly better for them than a cap at 4, so the
        // fixed order matches the choice that player would make (CR lets them order their own effects).
        if (intval($damage) >= 5 && !$baseDmgUnpreventable) {
            $genIdx = SWUFindUpgradeIndex($base[$i], 'HMW_081');
            if ($genIdx >= 0) {
                SWUQueuePreventedAnim("myBase-0", intval($targetPlayer));
                SWUDefeatUpgrade(intval($targetPlayer), 'myBase-0', $genIdx);
                DoDrawCard(intval($targetPlayer), 1);
                SetFlashMessage("Damage to the base was prevented (Alliance Shield Generator).");
                $playerID = $savedPID;
                return;
            }
        }
        // ASH_070 At Attin Safety Droid — "If your base would be dealt more than 4 damage, prevent all but
        // 4 of that damage." This is a PREVENTION, so it does NOT apply to UNPREVENTABLE damage: indirect
        // damage ($isIndirect) or damage from a source whose damage is unpreventable (ASH_196 Gorian's friendly
        // Underworld cards). Those land in full.
        if (intval($damage) > 4 && !$baseDmgUnpreventable && _SWUControlsCardInPlay(intval($targetPlayer), 'ASH_070')) {
            $damage = 4;
            SetFlashMessage("At Attin Safety Droid: base damage reduced to 4.");
        }
        $base[$i]->Damage = intval($base[$i]->Damage) + $damage;
        SWUQueueDamageAnim("myBase-0", intval($damage), intval($targetPlayer));
        // LOF_252 The Daughter — "When damage is dealt to your base: may use the Force → heal 2 from your
        // base." Post-damage reaction owned by the base owner (often the non-active player in combat); it
        // sits on their queue and resolves after the damage event. Only while the base survives.
        if (intval($damage) > 0 && intval($base[$i]->Damage) < intval(CardHp($base[$i]->CardID))) {
            _SWUCollectLof252Reaction(intval($targetPlayer));
        }
        // ASH_204 Blade Three — "When your base is dealt damage: give an Advantage token to this unit."
        if (intval($damage) > 0) {
            _SWUCollectAsh204Reaction(intval($targetPlayer));
        }
        // SEC_041 Populist Advisor — "When an enemy unit deals COMBAT damage to your base: this unit
        // gains Sentinel for this phase." Combat-gated; the base owner ($targetPlayer) gives its SEC_041s Sentinel.
        if (intval($damage) > 0 && !empty($GLOBALS['gInCombatDamage'])) {
            $savedPID2 = $playerID; $playerID = intval($targetPlayer);
            foreach (ZoneSearch("myGroundArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && ($o->CardID ?? '') === 'SEC_041') AddTurnEffect($mz, 'SENTINEL^SEC_041');
            }
            $playerID = $savedPID2;
        }
        // "Each opponent whose base you've damaged this phase" set (SOR_175) — flag the DAMAGER
        // (the caller's context, i.e. the other player) keyed by the damaged base's owner. Cleared
        // at regroup. Skip self-damage (a player damaging their own base doesn't flag anyone).
        if (intval($damage) > 0) {
            // An explicit $damager (e.g. indirect damage attributes to the ability's controller,
            // CR 35.4) wins; otherwise infer from the caller's context. When the damager equals
            // the target (self-inflicted), the guard below suppresses the enemy-base flags.
            // $damager may be an int (a player, e.g. indirect) OR a unit OBJECT (combat attacker, threaded for
            // the ASH_070 unpreventable check) — normalize an object to its controller player here.
            $damagerPlayer = is_object($damager)
                ? intval($damager->Controller ?? $damager->Owner ?? 0)
                : intval($damager ?? 0);
            // Keep the SOURCE UNIT's identity before $damager is collapsed to a player below: a caller
            // that threads the actual unit object states its source explicitly, which beats the ambient
            // SWU_DMG_SRC var (that one lingers for the whole action, so a later ability damaging a base
            // in the same action would otherwise be misattributed to whoever dispatched first).
            $srcUnitUID  = is_object($damager) ? intval($damager->UniqueID ?? 0) : 0;
            $srcUnitCtrl = is_object($damager) ? $damagerPlayer : 0;
            $damager = ($damager !== null && $damagerPlayer > 0)
                ? $damagerPlayer
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
            // SEC_012 Cassian Andor — "friendly units that have damaged an opponent's base this phase can't
            // be attacked." Mark the ABILITY'S SOURCE UNIT (SWU_DMG_SRC = "uid,ctrl", set at every unit-
            // ability dispatch; empty for event/base sources) when it damages an ENEMY base by any means —
            // When Played (SHD_160), activated/on-attack pings, indirect (JTL_218 survives the async choose).
            // Combat direct-attack + Overwhelm excess arm this flag at their own sites (SWU_DMG_SRC is not
            // set for vanilla combat). Read only by _SWUSec012Protected; a SEPARATE marker from the
            // combat-attack-only SWU_DEALT_BASEDMG (SHD_088/SHD_106 mean "attacked", not "damaged").
            $srcUid = 0; $srcCtrl = 0;
            if ($srcUnitUID > 0 && $srcUnitCtrl > 0) {
                $srcUid = $srcUnitUID; $srcCtrl = $srcUnitCtrl;   // explicit source unit threaded by the caller
            } else {
                $src = GetSWUVar('SWU_DMG_SRC', '');
                if ($src !== '' && strpos($src, ',') !== false) {
                    [$srcUid, $srcCtrl] = array_map('intval', explode(',', $src));
                }
            }
            if ($srcUid > 0 && $srcCtrl > 0) {
                if ($srcCtrl !== intval($targetPlayer)) {
                    AddGlobalEffects($srcCtrl, 'SWU_UNITDMGBASE_' . $srcUid);
                }
                // SEC_012: owner-qualified twin — stamped for ANY base (including the source's own), so
                // that after a control change we can still ask "did this unit damage a base belonging to
                // someone who is NOW its controller's opponent?". See _SWUSec012Protected.
                AddGlobalEffects($srcCtrl, 'SWU_DMGDBASE_' . $srcUid . '_' . intval($targetPlayer));
            }
        }
        $baseHP = intval(CardHp($base[$i]->CardID));
        // Goldfish practice: P2's Echo Base is an infinite damage sponge — never trigger the loss,
        // so the damage counter just keeps climbing past its HP. (P1's own base win-con is untouched.)
        $spongeExempt = ($targetPlayer === 2 && function_exists('SWUGameMode') && SWUGameMode() === 'goldfish');
        if (!$spongeExempt && $base[$i]->Damage >= $baseHP) {
            if (SeatCountForGame() > 2) {
                // Twin Suns: eliminate the seat (heals $damager 5, defers most-HP scoring) — do NOT
                // declare an instant winner. $damager was resolved above when $damage > 0.
                SWUEliminateSeat(intval($targetPlayer), ($damage > 0 ? intval($damager) : null));
            } else {
                $winner = $targetPlayer === 1 ? 2 : 1;
                SWUDeclareGameWinner($winner, "GAMEOVER:Player {$targetPlayer}'s base has been defeated! Player {$winner} wins!");
            }
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
    // Twin Suns: reset the leader whose deployed unit was THIS one (a seat may have two deployed).
    // Fall back to the first live leader when the UID can't be matched (single-leader / UID 0).
    $ldr = SWUFindLeaderByDeployedUID($ownerPlayer, $unitObj !== null ? intval($unitObj->UniqueID ?? 0) : 0);
    if ($ldr === null) $ldr = SWUGetLeaderByIndex($ownerPlayer, 0);
    if ($ldr !== null) {
        $ldr->Deployed        = false;
        $ldr->DeployedUniqueID = 0;
        $ldr->Ready           = false;
        $ldr->Damage          = 0;
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
            // Twin Suns: return the specific leader this pilot subcard is (a pilot has DeployedUniqueID 0,
            // so match by CardID — leader CardIDs are unique per seat). Fall back to first live.
            $ldr = SWUFindLeaderByCardID($subOwner, $subCardID);
            if ($ldr === null) $ldr = SWUGetLeaderByIndex($subOwner, 0);
            if ($ldr !== null) {
                $ldr->Deployed        = false;
                $ldr->DeployedUniqueID = 0;
                $ldr->Ready           = false;
                $ldr->Damage          = 0;
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
// TWI_069 Roger Roger — "When Defeated: Attach this upgrade to a friendly Battle Droid token." Re-attach
// the upgrade to a friendly Battle Droid token (TWI_T01) OTHER than the one leaving play. Returns true if
// re-attached (the caller then skips the normal discard); false if there is no eligible token.
function _SWURogerRogerReattach(int $controller, int $owner, int $excludeUID): bool {
    foreach (array_merge(GetGroundArena($controller), GetSpaceArena($controller)) as $u) {
        if (SWUObjGone($u)) continue;
        if (($u->CardID ?? '') !== 'TWI_T01') continue;
        if (intval($u->UniqueID ?? 0) === $excludeUID) continue;
        if (!is_array($u->Subcards ?? null)) $u->Subcards = [];
        $u->Subcards[] = (object)['CardID' => 'TWI_069', 'Owner' => $owner, 'Controller' => $controller,
            'TurnEffects' => [], 'IsPilot' => false, 'IsCaptive' => false];
        return true;
    }
    return false;
}

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
        // TWI_069 Roger Roger — re-attach to a friendly Battle Droid token instead of discarding (if any).
        if ($subCardID === 'TWI_069') {
            $rrCtrl = is_array($sub) ? intval($sub['Controller'] ?? 0) : intval($sub->Controller ?? 0);
            if ($rrCtrl <= 0) $rrCtrl = intval($host->Controller ?? $subOwner);
            if (_SWURogerRogerReattach($rrCtrl, $subOwner, intval($host->UniqueID ?? 0))) continue;
        }
        SWUAddToDiscard($subOwner, $subCardID, 'PLAY');
        // "A friendly upgrade was defeated" observers (ASH_039 flag, ASH_055 return, ASH_161 deal 1) — this
        // is the host-defeated path (parallel to SWUDefeatUpgrade / _SWUDefeatAllUpgradesOn).
        $subCtrl = is_array($sub) ? intval($sub['Controller'] ?? $subOwner) : intval($sub->Controller ?? $subOwner);
        _SWUOnUpgradeDefeated($subCtrl > 0 ? $subCtrl : intval($host->Controller ?? $subOwner), $subCardID, $host, $subOwner);
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
    // "Can't be defeated by enemy card abilities" (SHD_187 / JTL_103 / LAW_149 / TWI_220).
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
    // RULING (2026-08-07): "a unit ATTACKS AND DEFEATS a unit if it defeats the defender at any point
    // during the attack." A defeat that happens mid-attack — e.g. the attacker's own On-Attack ability
    // finishing the defender before combat damage — must therefore satisfy the attacks-and-defeats
    // observers (LOF_017 Darth Revan et al). Combat-damage defeats never route through this function, so
    // marking here catches exactly the non-combat case; SWUCombatDamage consumes it on the fizzle path.
    if (intval($obj->UniqueID ?? 0) > 0
            && intval($obj->UniqueID ?? 0) === intval(GetSWUVar('SWU_CURRENT_DEFENDER_UID', '0'))) {
        // Stash the defender's identity: by the time SWUCombatDamage notices it is gone, the object is
        // unreachable (and its old slot may hold a DIFFERENT live unit after a reindex), but the
        // attacks-and-defeats consumers need cardID/owner/leader-ness.
        SetSWUVar('SWU_DEFENDER_DEFEATED_IN_ATTACK', ($obj->CardID ?? '') . '|'
            . intval($obj->Owner ?? $obj->Controller ?? 0) . '|'
            . (strpos(CardType($obj->CardID ?? '') ?? '', 'Leader') !== false ? '1' : '0'));
    }
    // Same ruling, TWI_135 Darth Maul's two-defender attack: SWU_CURRENT_DEFENDER_UID holds ONE defender,
    // so a step-1 defeat of either of Maul's two (his granted On Attack ping finishing a 1-HP unit before
    // combat damage) needs its own tally. _SWUMaulDoubleCombat folds this into $defDefeatedCount so the
    // attacks-and-defeats observers still fire once per defender.
    $_maulUids = GetSWUVar('SWU_MAUL_DEF_UIDS', '');
    if ($_maulUids !== '' && intval($obj->UniqueID ?? 0) > 0
            && in_array(strval(intval($obj->UniqueID)), explode(',', $_maulUids), true)) {
        SetSWUVar('SWU_MAUL_DEF_PREKILLED', strval(intval(GetSWUVar('SWU_MAUL_DEF_PREKILLED', '0')) + 1));
    }
    // "A friendly unit was defeated WHILE ATTACKING this phase" (SEC_158 Oppression Breeds Rebellion,
    // SEC_013 Luthen Rael). The combat-damage paths mark this themselves; this covers the unit dying to
    // an ABILITY in the middle of its own attack — its own (SEC_150 Valiant Commando sacrificing itself
    // after hitting the base) or anyone's. Keyed on the live attacker UID so a defeat outside an attack,
    // or of some other unit during one, never sets it.
    if (intval($obj->UniqueID ?? 0) > 0
            && intval($obj->UniqueID ?? 0) === intval(GetSWUVar('SWU_CURRENT_ATTACKER_UID', '0'))) {
        $atkDeadCtrl = intval($obj->Controller ?? $obj->Owner ?? $player);
        if ($atkDeadCtrl > 0) {
            AddGlobalEffects($atkDeadCtrl, 'SWU_ATTACKER_DEFEATED');
            // SEC_013 Luthen Rael — "When a friendly unit is defeated while attacking." Mirrors the two
            // combat-defeat sites; without it an attacker that dies to an ABILITY mid-attack (its own
            // sacrifice, an opponent's removal) never reached him.
            if (($obj->CardID ?? '') === 'SEC_013') {
                AddTrigger($atkDeadCtrl, 'SEC_013', 'SEC_013', 'DEPLOYED_SELF');
            } elseif (_SWULeaderReadyUndeployed($atkDeadCtrl, 'SEC_013') || _SWULeaderDeployed($atkDeadCtrl, 'SEC_013')) {
                AddTrigger($atkDeadCtrl, 'SEC_013', 'SEC_013', '');
            }
        }
    }
    // Fire the defeated unit's WhenDefeated ability AND the leave-play reactions (Gideon/Krell/Boba)
    // for ANY effect-defeat routed through here — direct "defeat target unit" (Takedown/Vanquish),
    // sacrifice, shrink sweep, Rukh, etc. Combat-defeats mark units removed directly (not via this
    // function) and collect separately; the damage paths used to pre-collect before calling this, so
    // those pre-collects were removed (this is now the single collection point for effect-defeats).
    CollectWhenDefeatedTriggers(intval($player), [
        ['player' => intval($obj->Controller ?? $obj->Owner ?? $player), 'cardID' => $obj->CardID, 'mzID' => $unitMzID, 'upgraded' => _SWUIsUpgraded($obj), 'weakened' => (SWUFindUpgradeIndex($obj, 'HMW_T02') >= 0)]
    ]);
    $obj = GetZoneObject($unitMzID);
    if ($obj === null || (isset($obj->removed) && $obj->removed)) { $playerID = $savedPID; return true; }
    $owner = isset($obj->Owner) ? intval($obj->Owner) : intval($player);
    if (strpos(CardType($obj->CardID) ?? '', 'Leader') !== false) {
        // A deployed leader IS a unit — its defeat must register the same "unit defeated this phase" flags
        // as any other unit (ASH_079 Koska / SOR_051 Luke "a friendly unit was defeated", the enemy-defeated
        // flag, and Heroism-defeated for TWI_017), even though it returns to the leader zone rather than a
        // discard. Set them BEFORE the early return (SWUReturnLeaderToZone removes $obj). No SWU_DEFEATED_CARD_
        // — that tracks discard contents for SOR_091-style return-from-discard, and a leader never discards.
        $ldrController = isset($obj->Controller) ? intval($obj->Controller) : $owner;
        $ldrCardID     = $obj->CardID;
        if ($owner !== intval($player)) AddGlobalEffects(intval($player), 'SWU_ENEMY_DEFEATED');
        AddGlobalEffects($ldrController, 'SWU_FRIENDLY_DEFEATED');
        _SWUMarkHeroismDefeated($ldrController, $ldrCardID);
        SWUReturnLeaderToZone($owner, $unitMzID);
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $savedPID;
        return true;
    }
    $cardID = $obj->CardID;
    // Unit slides to its OWNER's discard. Queued here — after the leader early-return above (a defeated
    // leader goes back to its leader zone, not a discard) and before the unit is removed, so the source
    // still resolves. Perspective is the unit's CONTROLLER, not $player: an enemy ability defeating your
    // unit still slides it to YOUR discard. Tokens are skipped — they CEASE rather than being discarded,
    // so there is no destination to fly to.
    if (!_SWUCardCeasesOnLeavePlay($cardID)) {
        SWUQueueZoneMoveAnim($unitMzID, 'myDiscard-0', intval($obj->Controller ?? $player),
            420, intval($obj->UniqueID ?? 0) ?: null);
    }
    $hasSecondChance = _SWUUnitHasUpgrade($obj, 'SHD_053');
    $controller = isset($obj->Controller) ? intval($obj->Controller) : $owner;
    // CR 8.34.4: rescue any captives guarded by this unit before it leaves play.
    SWURescueCaptivesOf($obj);
    // If the host carries a leader-pilot upgrade, return that leader to zone before discarding.
    SWUReturnLeaderPilotSubcards($obj, $owner);
    _SWUDeferPilotDefeatReplacements($obj); // JTL_094: a pilot upgrade that "may instead move to ground"
    SWUDiscardHostSubcards($obj);           // remaining upgrades/pilots → each owner's discard
    $obj->removed = true;
    // Pass $obj so SWUAddToDiscard can revert a TWI_116 Clone copy's CardID to the real card (it leaves
    // play as Clone, not as the card it copied).
    SWUAddToDiscard($owner, $cardID, 'PLAY', $hasSecondChance ? 'TPF' : '', $obj);
    if ($owner !== intval($player)) {
        AddGlobalEffects(intval($player), 'SWU_ENEMY_DEFEATED');
    }
    // "A friendly unit was defeated this phase" (SOR_051 Luke) — friendly = CONTROLLER (a stolen
    // unit is friendly to whoever currently controls it). Cleared at RegroupPhaseStart.
    AddGlobalEffects($controller, 'SWU_FRIENDLY_DEFEATED');
    _SWUMarkHeroismDefeated($controller, $cardID); // TWI_017 Palpatine (Heroism face)
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

    // SEC_061 Willrow Hood — an enemy ability can't defeat (or bounce) the lone friendly upgrade on him.
    if (_SWUWillrowProtectsUpgrade($host, intval($foundCtrl), intval($player))) {
        $playerID = $savedPID;
        return false;
    }

    // JTL_012 Luke Skywalker (Hero of Yavin), deployed as a Pilot — "This unit can't be defeated as an
    // upgrade by enemy card abilities." The immunity protects the pilot UPGRADE itself (not the host)
    // from an ENEMY-controlled DEFEAT only. It does NOT stop a bounce/return (Bamboozle returns the
    // pilot — a leader pilot goes back to the leader zone, not hand), and a FRIENDLY ability (e.g. Power
    // Failure) can still defeat it.
    if (!$bounce && _SWUUpgradeImmuneToEnemyDefeat($foundCardID) && intval($player) !== intval($foundCtrl)) {
        $playerID = $savedPID;
        return false;
    }

    // HMW_060 Vice Admiral Rampart — "If an upgrade on your base would be defeated, you may defeat this
    // unit instead." Applies to ANY ability/cost/effect defeat of a base upgrade (HMW_081 Alliance Shield
    // Generator's effect-defeat, HMW_095 Carbonite Chamber's cost-defeat, HMW_171 Trap Field's self-
    // sacrifice) — per the SWU CR a replacement effect CAN replace a cost (the cost still counts as paid),
    // and when it replaces the text before "If you do" the player still resolves the "If you do" payoff. A
    // BOUNCE is not "would be defeated" ($bounce), and $skipReplacement suppresses re-entry when the flush
    // resolves the declined defeat. Uniqueness enforcement only hosts on arena units, so it never reaches
    // this branch. Deferred to action end (JTL_094 timing, user-specified) so the base controller chooses;
    // the upgrade stays on the base until then, stamped a UID so the flush can re-find it. HMW CR unreleased.
    if (!$skipReplacement && !$bounce && strpos($hostMzID, 'Base') !== false) {
        $baseCtrl = intval($host->Controller ?? $player);
        if ($baseCtrl <= 0) $baseCtrl = intval($player);
        if (_SWUControlsCardInPlay($baseCtrl, 'HMW_060')) {
            if (intval($foundUID) <= 0) {
                $foundUID = NextUniqueID();
                if (is_array($host->Subcards[$foundKey])) $host->Subcards[$foundKey]['UniqueID'] = $foundUID;
                elseif (is_object($host->Subcards[$foundKey])) $host->Subcards[$foundKey]->UniqueID = $foundUID;
            }
            $gDeferredReplacements[] = ['kind' => 'rampart_save', 'controller' => $baseCtrl,
                'hostMz' => $hostMzID, 'uid' => intval($foundUID), 'cardID' => $foundCardID];
            $playerID = $savedPID;
            return true;   // handled via the replacement — the base upgrade stays for now
        }
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
    // host-leaves-play path; the upgrade is already off the host). Only a DEFEAT qualifies — a BOUNCE
    // (return-to-hand, e.g. Bamboozle) is not "would be defeated", so Luke simply goes to hand below.
    if (!$skipReplacement && !$bounce && $foundCardID === 'JTL_094') {
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
        // Twin Suns: return the specific leader this pilot upgrade is (match by CardID; pilots carry
        // DeployedUniqueID 0). Fall back to first live for single-leader.
        $ldr = SWUFindLeaderByCardID($foundOwner, $foundCardID);
        if ($ldr === null) $ldr = SWUGetLeaderByIndex($foundOwner, 0);
        if ($ldr !== null) {
            $ldr->Deployed        = false;
            $ldr->DeployedUniqueID = 0;
            $ldr->Ready           = false;
            $ldr->Damage          = 0;
        }
    // Tokens are set aside (removed from game); non-tokens go to owner's discard or hand.
    } elseif (strpos(strtolower(CardType($foundCardID) ?? ''), 'token') === false) {
        if ($bounce) {
            AddHand($foundOwner, CardID: $foundCardID);
        } elseif ($foundCardID === 'TWI_069'
                && _SWURogerRogerReattach(intval($foundCtrl) > 0 ? intval($foundCtrl) : intval($foundOwner),
                       intval($foundOwner), intval($host->UniqueID ?? 0))) {
            // TWI_069 Roger Roger — When Defeated: re-attach to a friendly Battle Droid token instead of
            // discarding (a directly-defeated upgrade, host survives). No discard / defeated-observer.
        } else {
            SWUAddToDiscard($foundOwner, $foundCardID, 'PLAY');
            // "A friendly upgrade was defeated" observers (ASH_039 flag, ASH_055 return, ASH_161 deal 1).
            // Only a real (non-bounce, non-token) defeat counts.
            _SWUOnUpgradeDefeated(intval($foundCtrl), $foundCardID, $host, intval($foundOwner));
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
    if ($healed > 0 && function_exists('SWUTelemetryBumpTurn')) SWUTelemetryBumpTurn($player, 'restored', $healed);
    // Reactive "When 1 or more damage is healed from this unit" (JTL_062 Silver Angel, LAW_047 Baze).
    if ($healed >= 1 && function_exists('_SWUOnUnitHealed')) _SWUOnUnitHealed($obj, $healed);
    // TWI_042 Barriss Offee — mark the unit as "healed this phase" (phase-duration marker read as a
    // +1/+0 field-presence buff while a Barriss is in play).
    if ($healed >= 1) AddTurnEffect($mzCard, 'SWU_HEALED_PHASE');
}

// Remove up to $amount damage counters from player $targetPlayer's base. Clamps at 0.
function OnHealBase($player, $targetPlayer, $amount) {
    // SOR_160 Wolffe: "Bases can't be healed for this phase." A global lock set by either player —
    // block ALL base healing (including Restore) while it's active.
    if (GlobalEffectCount(1, 'SWU_NOHEAL_BASE') > 0 || GlobalEffectCount(2, 'SWU_NOHEAL_BASE') > 0) return;
    // TWI_132 Confederate Tri-Fighter — "Bases can't be healed." (Continuous while any copy is in play.)
    if (_SWUCountActiveUnitsWithCardID(1, 'TWI_132') > 0 || _SWUCountActiveUnitsWithCardID(2, 'TWI_132') > 0) return;
    $base = &GetBase(intval($targetPlayer));
    for ($i = 0; $i < count($base); $i++) {
        if (isset($base[$i]->removed) && $base[$i]->removed) continue;
        $before = intval($base[$i]->Damage);
        $base[$i]->Damage = max(0, $before - intval($amount));
        $_healedBase = $before - intval($base[$i]->Damage);
        if ($_healedBase > 0) {
            AddGlobalEffects(1, 'SWU_BASE_HEALED_PHASE');   // TS26_38 "if a base was healed this phase" (seat-1 stored; cleared at RGS)
            // IC27_026 Darth Sidious — "When you heal damage from your base: deal that much damage to
            // an enemy unit." Scoped to the healed base's own controller ($targetPlayer), so healing an
            // OPPONENT's base never triggers their Sidious for you. The amount is $_healedBase — the
            // damage ACTUALLY removed, already clamped at 0, so a nearly-full base deals less than the
            // printed Restore value. Routed through a CUSTOM (which ExecuteStaticMethods does not
            // $playerID-restore) because this can fire mid-combat from the Restore keyword.
            if (_SWUCountActiveUnitsWithCardID(intval($targetPlayer), 'IC27_026') > 0) {
                DecisionQueueController::AddDecision(intval($targetPlayer), 'CUSTOM',
                    "IC27_026#0|{$_healedBase}", 1);
            }
        }
        SWUQueueHealAnim("myBase-0", $_healedBase, intval($targetPlayer));
        if ($_healedBase > 0 && function_exists('SWUTelemetryBumpTurn')) SWUTelemetryBumpTurn(intval($targetPlayer), 'restored', $_healedBase);
        break;
    }
}

// Player-optimal Shield vs fixed-reduction ordering for ONE combat hit (after the ASH_196-unpreventable /
// Amidala / Mandalorian branches, which are handled by the caller). Rules: multiple "prevent" replacements
// on one damage event are ordered by the damaged unit's controller (CR replacement effects), so pick the
// order that wastes the least. If a reduction (LOF_220's PREVENT_DMG_2, SEC_067, …) would FULLY cover the
// hit, use it and KEEP the shield (a 2-damage hit into a shield+PREVENT_DMG_2 unit → 0 damage, shield stays);
// otherwise let a Shield token absorb the whole instance (keeping the one-shot marker for a bigger hit);
// otherwise apply any partial reduction and take what's left. Mutates $obj->Damage, consumes the shield/
// marker, queues anims, and RETURNS the damage actually dealt (0 if fully prevented). Callers set combatCtx.
function _SWUShieldOrReduceCombat($obj, string $mzID, int $amount, int $animPlayer): int {
    if ($amount <= 0) return 0;
    if (_SWUApplyDamagePrevention($obj, $amount, true) <= 0) {   // a reduction fully covers → keep the shield
        _SWUApplyDamagePrevention($obj, $amount);                 // consume the one-shot marker(s)
        SWUQueuePreventedAnim($mzID, $animPlayer);
        return 0;
    }
    if (SWUConsumeShieldToken($obj)) {                            // shield absorbs the whole hit (marker kept)
        SWUQueuePreventedAnim($mzID, $animPlayer);
        SWUQueueShieldBreakAnim($mzID, $animPlayer);
        return 0;
    }
    $dmg = _SWUApplyDamagePrevention($obj, $amount);              // no shield → apply any partial reduction
    $obj->Damage = intval($obj->Damage) + $dmg;
    if ($dmg > 0) SWUQueueDamageAnim($mzID, $dmg, $animPlayer);
    return $dmg;
}

// SHD_090 Maul — "all combat damage that would be dealt to this unit during this attack is dealt to the
// chosen unit instead." Returns the CURRENT mzID of the friendly redirect target (resolved by UID, so it
// survives index shifts), or null if this attacker has no redirect this attack. The marker
// "SWU_REDIRECT_DMG-{uid}" is set on Maul in its On Attack; it expires at attack end (SWU_DUR_ATTACK).
function _SWUCombatRedirectTarget($attacker): ?string {
    foreach (($attacker->TurnEffects ?? []) as $te) {
        if (strpos((string)$te, 'SWU_REDIRECT_DMG-') === 0) {
            $uid = intval(substr((string)$te, strlen('SWU_REDIRECT_DMG-')));
            return $uid > 0 ? SWUFindMzByUID($uid) : null;
        }
    }
    return null;
}

// If a redirect is active, apply the attacker's would-be counter-damage ($amount) to the redirect target
// as COMBAT damage (its own shields/prevention apply); the attacker itself takes nothing. Returns true if
// it redirected (so the caller skips the normal attacker prevention/shield chain).
function _SWUMaybeRedirectAttackerDamage($attackerMzID, ?string $redirectMz, int $amount, int $player): bool {
    if ($redirectMz === null) return false;
    if ($amount > 0) {
        $ro = GetZoneObject($redirectMz);
        if ($ro !== null && empty($ro->removed)) _SWUShieldOrReduceCombat($ro, $redirectMz, $amount, $player);
    }
    SWUQueuePreventedAnim($attackerMzID, $player); // Maul takes no combat damage this attack
    return true;
}

// ── Combat trigger collectors ───────────────────────────────────────────────

// True if $obj carries an attack-duration TurnEffect marker with base $base (granted "for this attack").
function _SWUAttackHasMarker($obj, string $base): bool {
    foreach (($obj->TurnEffects ?? []) as $te) {
        if (SWUParseTurnEffect((string)$te)['base'] === $base) return true;
    }
    return false;
}

// ── Support (ASH) ───────────────────────────────────────────────────────────
// "When you play/deploy this unit, you may attack with another unit. It gains this
// unit's other abilities for this attack." S = the played/deployed Support unit;
// A = the chosen other attacker. Keywords are snapshot live onto A (so a conditionally
// granted Restore/Raid — e.g. JTL_047 on a Vehicle — transfers); triggered/constant
// abilities ride the SUPPORT_GRANT marker, resolved by S's CardID at each combat site.

// Read A's SUPPORT_GRANT marker → ['cardID'=>S's CardID, 'uid'=>S's UID] or null.
function _SWUSupportGrant($obj): ?array {
    foreach (($obj->TurnEffects ?? []) as $te) {
        $p = SWUParseTurnEffect((string)$te);
        if ($p['base'] === 'SUPPORT_GRANT') {
            return ['cardID' => $p['params'][0] ?? '', 'uid' => intval($p['params'][1] ?? 0)];
        }
    }
    return null;
}

// True if $obj's abilities include $cardID's — either it IS that card, or it currently bears a
// SUPPORT_GRANT marker for that card (a unit attacking with a supporting unit's lent abilities). Used at
// each combat site for a Support unit's CONTINUOUS/passive ability (cross-arena, defender debuff,
// deal-first, conditional attack buff), which can't ride the triggered-ability dispatch.
function _SWUAttackerGrants($obj, string $cardID): bool {
    if ($obj === null) return false;
    if (($obj->CardID ?? '') === $cardID) return true;
    $sg = _SWUSupportGrant($obj);
    return $sg !== null && ($sg['cardID'] ?? '') === $cardID;
}

// Ready friendly units (other than the Support unit) eligible to make the Support attack.
function SWUGetValidSupportAttackers(int $activePlayer, $supportObj): array {
    global $playerID; $savedPID = $playerID; $playerID = intval($activePlayer);
    $sUid = intval($supportObj->UniqueID ?? 0);
    $out  = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->UniqueID ?? 0) === $sUid) continue;   // "another unit"
            if (intval($o->Status ?? 0) !== 1) continue;          // must be ready (1=ready, 0=exhausted)
            $out[] = $mz;
        }
    }
    $playerID = $savedPID;
    return $out;
}

// Lend S's EFFECTIVE "other abilities" to A for one attack, then begin A's attack.
function _SWUSupportGrantAbilities(int $player, string $supportMz, string $attackerMz): void {
    $S = GetZoneObject($supportMz);
    $A = GetZoneObject($attackerMz);
    if ($S === null || SWUObjGone($A)) return;

    // Combat-relevant keywords — effective reads so conditional/granted keywords on S transfer. Each
    // lent keyword names S as its source (^S CardID) so the Active Effects popup shows the supporting
    // unit's art as the provenance.
    $sSrc = $S->CardID ?? '';
    if (HasKeyword_Grit($S))      AddTurnEffect($attackerMz, SWUMakeTurnEffect('GRIT',      [], SWU_DUR_ATTACK, $sSrc));
    if (HasKeyword_Overwhelm($S)) AddTurnEffect($attackerMz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, $sSrc));
    if (HasKeyword_Saboteur($S))  AddTurnEffect($attackerMz, SWUMakeTurnEffect('SABOTEUR',  [], SWU_DUR_ATTACK, $sSrc));
    if (HasKeyword_Sentinel($S))  AddTurnEffect($attackerMz, SWUMakeTurnEffect('SENTINEL',  [], SWU_DUR_ATTACK, $sSrc));
    $raid = GetKeyword_Raid_Value($S);
    if ($raid !== null && $raid > 0)       AddTurnEffect($attackerMz, SWUMakeTurnEffect('RAID',    [$raid],    SWU_DUR_ATTACK, $sSrc));
    $restore = GetKeyword_Restore_Value($S);
    if ($restore !== null && $restore > 0) AddTurnEffect($attackerMz, SWUMakeTurnEffect('RESTORE', [$restore], SWU_DUR_ATTACK, $sSrc));

    // Triggered + constant abilities ride one marker (S's CardID for closure lookup, UID for provenance).
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('SUPPORT_GRANT', [$S->CardID, intval($S->UniqueID ?? 0)], SWU_DUR_ATTACK, $S->CardID ?? null));

    BeginSWUAttack($player, $attackerMz);   // exhausts A, picks A's target, runs combat (grants now visible)
}

// Step 1: collect On Attack / On Defense triggers after attacker and target are known.
// Collects into $gPendingTriggers but DOES NOT flush — ExecuteSWUAttack flushes via
// FlushCombatTriggerBag so that combat damage runs after the triggers resolve.
// $defenderOnly: collect ONLY the per-defender half (On Defense, upgrade-granted On Defense, the SEC_101 /
// ASH_062 prevention offers, SEC_231, the SEC_157 lose-abilities marker, LOF_205's defender-scoped grant,
// and the defender's OnAttackedFromUpgrade). Used by TWI_135 Darth Maul's two-defender attack, which calls
// this once normally for the first defender and once with $defenderOnly for the second — the attacker-side
// blocks (its own On Attack, Support, Partagaz, Force bases, …) must fire ONCE per attack, not per defender.
// Defaults to false, so every existing single-defender caller is unchanged.
function CollectCombatStep1Triggers($activePlayer, $attackerMzID, $defenderMzID, bool $defenderOnly = false): void {
    global $onAttackAbilities;
    $attacker = GetZoneObject($attackerMzID);
    $defender = GetZoneObject($defenderMzID);

    // SEC_157 One Way Out — "If it attacks a UNIT, the defender loses all abilities for this attack."
    // Apply the attack-duration LOSE_ABILITIES marker to the defending UNIT now, BEFORE its On Defense
    // trigger (below) and its keyword reads in SWUCombatDamage. Gated on the attacker's SEC_157 signal
    // marker and a unit target (base attacks — "theirBase-N" — are excluded).
    if ($attacker !== null && !isset($attacker->removed) && _SWUAttackHasMarker($attacker, 'SEC_157')
        && $defender !== null && empty($defender->removed) && strpos($defenderMzID, 'Base') === false) {
        AddTurnEffect($defenderMzID, SWUMakeTurnEffect('SEC_157_DEF', [], SWU_DUR_ATTACK));
    }

    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && HasOnAttackAbility($attacker->CardID)
        && !LostAbilities($attacker)) {
        AddTrigger($activePlayer, 'OnAttack', $attacker->CardID, $attackerMzID);
    }
    // TS26_78 Barriss Offee — "When an enemy unit attacks: you may give an Experience token to that unit."
    // The reactor is the attacker's opponent (Barriss's controller). Rides the combat trigger bag; the
    // dispatch sets SWU_PENDING_DEF_REACTION so it resolves before combat damage.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed)) {
        // Twin Suns: any opponent controlling Barriss reacts (2-player → the one opponent, byte-identical).
        foreach (OpponentsOf($activePlayer) as $bopp) {
            if (_SWUCountUnitsWithCardID($bopp, 'TS26_78') > 0) {
                AddTrigger($bopp, 'TS26_78', 'TS26_78', strval(intval($attacker->UniqueID ?? 0)));
            }
        }
    }
    // HMW_014 Wicket (leader FRONT) — "When a friendly unit attacks a unit that costs more than it: you
    // may exhaust this leader. If you do, draw a card." The reactor is the ATTACKING player, so this rides
    // the normal trigger bag (no combat pause — a draw cannot affect the damage about to be dealt).
    // Active ONLY while he is the READY, UNDEPLOYED leader: the exhaust is the cost, and once deployed his
    // On Attack side replaces this ability entirely.
    // COST is always the PRINTED cost on both sides (user ruling) — and a deployed enemy leader has a
    // printed cost too, so it participates in the comparison like any other unit. A BASE is not a unit
    // and has no cost, hence the Base guard.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed)
        && $defender !== null && empty($defender->removed) && strpos($defenderMzID, 'Base') === false
        && _SWULeaderReadyUndeployed($activePlayer, 'HMW_014')
        && intval(CardCost($defender->CardID ?? '')) > intval(CardCost($attacker->CardID ?? ''))) {
        AddTrigger($activePlayer, 'HMW_014', 'HMW_014', '');
    }
    // TS26_73 Moralo Eval — "When your base is dealt combat damage: you may deal 1 damage to a unit." The
    // base owner reacts to their base being attacked (this rides the combat pause so it drains cross-player;
    // fires at the base-attack window rather than strictly post-damage — a benign timing simplification).
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && strpos((string)$defenderMzID, 'Base') !== false) {
        $mopp = SWUMzOwner($defenderMzID, $activePlayer);   // base owner = the specific defending seat (N-player)
        if ($mopp > 0 && _SWUCountUnitsWithCardID($mopp, 'TS26_73') > 0) {
            AddTrigger($mopp, 'TS26_73', 'TS26_73', '');
        }
    }
    // LAW_169 Payroll Heist — "For this phase, each friendly unit gains: On Attack: Create a Credit
    // token." Granted via the LAW_169 phase marker; the attacker's controller creates a Credit token.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && !LostAbilities($attacker)
        && is_array($attacker->TurnEffects ?? null) && in_array('LAW_169', $attacker->TurnEffects, true)) {
        SWUCreateCreditToken(intval($attacker->Controller ?? $activePlayer), 1);
    }
    // LOF common Force bases (LOF_020/021/023/024/026/027/029/030) — "When a friendly Force unit
    // attacks: The Force is with you (create your Force token)." Reactive trigger (NOT an Action); it
    // rides the combat trigger bag so it orders correctly with any other On-Attack triggers and with
    // multi-unit plays. The attacker is always controlled by the active player.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && TraitContains($attacker, 'Force')) {
        foreach (GetBase($activePlayer) as $b) {
            if (!empty($b->removed)) continue;
            // SEC_046 Galen Erso — if the enemy Galen named this base, it has lost its abilities.
            if (_SWUForceAttackBase($b->CardID ?? '') && !_SWUGalenSuppressesCard($activePlayer, $b->CardID ?? '')) {
                AddTrigger($activePlayer, $b->CardID, $b->CardID, '');
            }
            break; // only one live base per player
        }
    }
    // SEC_081 Major Partagaz — "When another friendly Official unit attacks: this unit gets +2/+2 for
    // this phase." The attacker is always the active player's; only OTHER Official attackers trigger it.
    // Apply directly (no decision) to each friendly SEC_081 that isn't the attacker.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && TraitContains($attacker, 'Official')) {
        global $playerID; $savedPid81 = $playerID; $playerID = intval($activePlayer);
        $atkUID81 = intval($attacker->UniqueID ?? 0);
        foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && ($o->CardID ?? '') === 'SEC_081'
                && intval($o->UniqueID ?? 0) !== $atkUID81) {
                // STACKS: each Official attack this phase adds another +2/+2. SWUApplyPhaseBuff emits an
                // identical "SWUBUFF-2-2" string that AddTurnEffect de-dupes, so a plain re-apply would
                // collapse to a single +2/+2. Emit a unique-per-trigger token (distinct ^source suffix)
                // that still parses as STAT_BUFF +2/+2, so N attacks give +2N/+2N.
                $stackIdx81 = 0;
                foreach (($o->TurnEffects ?? []) as $te81) {
                    if (strpos((string)$te81, '^SEC81_') !== false) $stackIdx81++;
                }
                AddTurnEffect($mz, SWUMakeTurnEffect('SWUBUFF', [2, 2], SWU_DUR_PHASE, 'SEC81_' . $stackIdx81));
            }
        }
        $playerID = $savedPid81;
    }
    // JTL_156 Trench Run — granted "On Attack: discard 2 from the defending player's deck; deal the
    // cost difference (unpreventable) to this unit." (Marker added for this attack only.)
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && _SWUAttackHasMarker($attacker, 'JTL_156')) {
        AddTrigger($activePlayer, 'JTL_156', 'JTL_156', $attackerMzID);
    }
    // LOF_205 Force Speed — granted "On Attack: return the defender's non-unique upgrades to hand." Pass the
    // DEFENDER's mzID (that's what the trigger acts on).
    if ($attacker !== null && !isset($attacker->removed) && _SWUAttackHasMarker($attacker, 'LOF_205')
        && $defender !== null && empty($defender->removed)) {
        AddTrigger($activePlayer, 'LOF_205', 'LOF_205', $defenderMzID);
    }
    // Support (ASH) — A bears a SUPPORT_GRANT marker, so it fires the supporting unit's On Attack
    // ability for this attack (closure runs against A's mzID, so "this unit" = A). A's own On Attack
    // (collected above) still fires too.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && !LostAbilities($attacker)) {
        $sg = _SWUSupportGrant($attacker);
        if ($sg !== null && $sg['cardID'] !== '' && HasOnAttackAbility($sg['cardID'])) {
            AddTrigger($activePlayer, 'SupportOnAttack', $sg['cardID'], $attackerMzID);
        }
    }
    // Upgrade-granted On Attack abilities (e.g. JTL_172, SOR_137 on a Force unit).
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed)) {
        // SEC_038 Condemn: each Condemn grants its On Attack AND "loses all OTHER abilities" — so when
        // the attacker bears any Condemn, every NON-Condemn upgrade grant is suppressed, and a Condemn's
        // own grant fires ONLY if it is the lone Condemn (2+ Condemns mutually suppress each other's
        // granted On Attack → none fire; the unit attacks for normal power).
        $condemnCount = 0;
        foreach (GetUpgradesOnUnit($attacker) as $u) { if (($u->CardID ?? '') === 'SEC_038') $condemnCount++; }
        foreach (GetUpgradesOnUnit($attacker) as $upgrade) {
            $key = $upgrade->CardID . ':0';
            if (!isset($onAttackAbilities[$key])) continue;
            // SEC_046 Galen Erso — a named upgrade has lost its granted "On Attack" ability.
            if (_SWUGalenSuppressesCard(intval($upgrade->Owner ?? $attacker->Controller ?? $activePlayer), $upgrade->CardID ?? '')) continue;
            if ($condemnCount > 0) {
                if (($upgrade->CardID ?? '') !== 'SEC_038') continue;   // other grants suppressed
                if ($condemnCount !== 1) continue;                       // multiple Condemns cancel out
            }
            AddTrigger($activePlayer, 'OnAttackFromUpgrade', $upgrade->CardID, $attackerMzID);
        }
    }
    if ($defender !== null && !isset($defender->removed) && HasOnDefenseAbility($defender->CardID)
        && !LostAbilities($defender)) {   // a defender that lost its abilities (SEC_157) has no On Defense
        $defController = intval($defender->Controller ?? GetOpponent($activePlayer));
        // $defenderMzID is in the ATTACKER's (active player's) frame — the defender always sits in the
        // attacker's "their..." zone. The OnDefense trigger is dispatched under $defController, so convert
        // the mzID to that frame ("theirGroundArena-N" → "myGroundArena-N") or it resolves to the wrong unit.
        $defMzForDef = preg_replace('/^(their|p\d+)/', 'my', $defenderMzID); // Twin Suns: p{n} defender → its own frame
        AddTrigger($defController, 'OnDefense', $defender->CardID, $defMzForDef);
    }
    // Upgrade-granted "When this unit is attacked: ..." On Defense reactions (SEC_052 Diplomatic
    // Immunity). Mirrors the own-unit OnDefense above: dispatched under the DEFENDER's controller, host
    // mzID flipped to the defender's frame. OnDefenseFromUpgradeTrigger sets SWU_PENDING_DEF_REACTION so
    // the combat-pause holds for the disclose/reaction, exactly like a true On Defense trigger.
    if ($defender !== null && !isset($defender->removed)) {
        global $onDefenseFromUpgradeAbilities;
        $defControllerUp = intval($defender->Controller ?? GetOpponent($activePlayer));
        $defMzForDefUp   = preg_replace('/^(their|p\d+)/', 'my', $defenderMzID); // Twin Suns: p{n} defender → its own frame
        foreach (GetUpgradesOnUnit($defender) as $up) {
            if (isset($onDefenseFromUpgradeAbilities[$up->CardID ?? ''])
                && !_SWUGalenSuppressesCard(intval($up->Owner ?? $defender->Controller ?? $defControllerUp), $up->CardID ?? '')) { // SEC_046 Galen
                AddTrigger($defControllerUp, 'OnDefenseFromUpgrade', $up->CardID, $defMzForDefUp);
            }
        }
    }
    // SEC_101 Queen Amidala — interactive combat-damage prevention. Routed through AddTrigger (NOT a
    // direct decision) so FlushCombatTriggerBag reports a trigger and combat goes through the SWU_TRIGGER_
    // RESUME path — which is what lets the offer resolve BEFORE SWUCombatDamage (the combat-pause for the
    // defender; the pre-resume step-1 resolution for the attacker). SEC101PreventTrigger queues the offer
    // and sets SWU_PENDING_DEF_REACTION. The host mzID is passed in the TRIGGER OWNER's (controller's) frame.
    // Attacker side fires ONLY when combat damage would actually be dealt back to her — i.e. she is
    // attacking a UNIT (not a base — bases deal no counter-damage) that has power to counter. Without this,
    // attacking a base wrongly prompted "prevent combat damage" and wasted the sacrifice on nothing.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && ($attacker->CardID ?? '') === 'SEC_101'
        && $defender !== null && empty($defender->removed) && strpos($defenderMzID, 'Base') === false
        && intval(ObjectCurrentPower($defender)) > 0
        && !empty(_SWUAmidalaPreventTargets($attacker))) {
        AddTrigger($activePlayer, 'SEC_101_PREVENT', 'SEC_101', $attackerMzID); // attacker frame = active player's "my…"
    }
    if ($defender !== null && !isset($defender->removed) && ($defender->CardID ?? '') === 'SEC_101'
        && !empty(_SWUAmidalaPreventTargets($defender))) {
        $defCtrl = intval($defender->Controller ?? GetOpponent($activePlayer));
        AddTrigger($defCtrl, 'SEC_101_PREVENT', 'SEC_101', preg_replace('/^their/', 'my', $defenderMzID));
    }
    // ASH_062 The Mandalorian — interactive prevention of combat damage to ANOTHER friendly unit. Same
    // AddTrigger→combat-pause routing as SEC_101, but the protected unit is the attacker/defender (NOT
    // ASH_062) and its controller defeats a Shield on their ASH_062 to prevent. The trigger host mzID is
    // the PROTECTED unit (in its controller's frame). Skip when the combatant IS an ASH_062 (its own
    // Shielded handles that — "another friendly unit" doesn't apply).
    // Same "only if counter-damage would occur" gate as SEC_101 above: a unit attacking a base takes no
    // counter, so don't offer to defeat a Shield on ASH_062 to prevent nothing.
    if (!$defenderOnly && $attacker !== null && !isset($attacker->removed) && ($attacker->CardID ?? '') !== 'ASH_062'
        && $defender !== null && empty($defender->removed) && strpos($defenderMzID, 'Base') === false
        && intval(ObjectCurrentPower($defender)) > 0
        && _SWUAsh062Provider($attacker) !== null) {
        AddTrigger($activePlayer, 'ASH_062_PREVENT', 'ASH_062', $attackerMzID); // attacker frame = active player's "my…"
    }
    if ($defender !== null && !isset($defender->removed) && ($defender->CardID ?? '') !== 'ASH_062'
        && _SWUAsh062Provider($defender) !== null) {
        $defCtrl062 = intval($defender->Controller ?? GetOpponent($activePlayer));
        AddTrigger($defCtrl062, 'ASH_062_PREVENT', 'ASH_062', preg_replace('/^their/', 'my', $defenderMzID));
    }
    // SEC_231 Implicate — granted "When this unit is attacked: create a Spy token" (via the per-unit
    // SEC_231 phase marker). Non-interactive, so create the Spy directly for the DEFENDER's controller;
    // no combat-pause needed.
    if ($defender !== null && !isset($defender->removed) && in_array('SEC_231', $defender->TurnEffects ?? [], true)) {
        SWUCreateUnitToken(intval($defender->Controller ?? GetOpponent($activePlayer)), 'SEC_T01');
    }
    // Upgrade-granted "When attached unit is attacked: ..." reactive (JTL_260 Death Star Plans — the
    // ATTACKING player steals the upgrade). Fires for the ATTACKER (active player); $defenderMzID is in
    // the attacker's frame, so the handler can locate the upgrade and pick one of the attacker's units.
    if ($defender !== null && !isset($defender->removed)) {
        global $onAttackedFromUpgradeAbilities;
        foreach (GetUpgradesOnUnit($defender) as $up) {
            if (isset($onAttackedFromUpgradeAbilities[$up->CardID ?? ''])
                && !_SWUGalenSuppressesCard(intval($up->Owner ?? $defender->Controller ?? $activePlayer), $up->CardID ?? '')) { // SEC_046 Galen
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
    _SWUCollectOnUnitDamagedReactions($activePlayer, $attackerMzID, $defenderMzID, $combatCtx);
    // Advantage tokens (ASH_T02): "When attached unit's attack or defense ends: Defeat this upgrade."
    // DEFENDER side — its defense just ended. There are no "When Defense Ends" abilities in the engine to
    // order against, so shed all synchronously (equivalent to the "only Advantage left in the bag → all"
    // case). (If an OnDefenseEnd window is ever added, route this through a trigger like the attacker.)
    // ATTACKER side is handled as an ORDERED trigger in CollectAfterAttackTriggers (above) so a power-
    // reading When-Attack-Ends ability — LOF_038 Pong Krell — can resolve before the tokens shed.
    _SWUDefeatAllAdvantageTokens($defenderMzID);
}

// "When damage is dealt to this unit" reactions (post-damage; no combat-pause). Fires for the attacker
// (took counter-damage) and the defender (took the hit) when each survived and took >0 combat damage.
// First consumer: SEC_143 The Elite Squad. The ability/effect damage path is hooked in SWUDealDamageToUnit.
function _SWUCollectOnUnitDamagedReactions(int $activePlayer, string $attackerMzID, string $defenderMzID, array $combatCtx): void {
    if (!empty($combatCtx['attackerTookDmg'])) {
        $a = GetZoneObject($attackerMzID);
        // Call even when defeated (SEC_143 fires regardless; the $survived flag gates the rest). Runs before
        // CleanupRemovedCards, so a lethally-damaged unit is still reachable (marked removed, not yet purged).
        if ($a !== null) _SWUOnUnitDamaged($a, intval($combatCtx['attackerDmgAmt'] ?? 0), true, empty($a->removed));
    }
    if (!empty($combatCtx['dealtToUnit'])) {
        $d = GetZoneObject($defenderMzID);
        if ($d !== null) _SWUOnUnitDamaged($d, intval($combatCtx['defenderDmgAmt'] ?? 0), true, empty($d->removed));
    }
}

// WhenDealsCombatDamage / WhenDefeats windows (Phase 7.2): from the just-resolved attack's $combatCtx,
// queue the attacker's combat-hit abilities. Added to the same trigger bag as OnAttackEnd so they
// ride the EffectStack flush (decisions defer SWUAfterAction). $activePlayer = the attacker's controller.
// How many times unit $uid has been stamped as having damaged a base — ANY base, either player's, by any
// route (combat, Overwhelm spill, ability ping, indirect). Sums the owner-qualified SWU_DMGDBASE_{uid}_{seat}
// markers armed in SWUDealDamageToBase. The stamps are PHASE-scoped, so a caller that needs an ATTACK-scoped
// answer compares this against a snapshot taken when the attack was declared (LAW_205 Flash the Vents).
function _SWUUnitBaseDamageStamps(int $ctrl, int $uid): int {
    if ($uid <= 0 || $ctrl <= 0) return 0;
    $n = 0;
    for ($seat = 1; $seat <= SeatCountForGame(); $seat++) {
        $n += GlobalEffectCount($ctrl, 'SWU_DMGDBASE_' . $uid . '_' . $seat);
    }
    return $n;
}

function SWUCollectCombatHitTriggers($activePlayer, $attackerMzID, $defenderMzID, array $combatCtx): void {
    $attacker = GetZoneObject($attackerMzID);
    // LAW_007 Boba Fett is a FIELD observer owned by BOBA (not by the attacker): "When a friendly Bounty
    // Hunter unit's attack ends, if the defending unit was defeated, [deployed] create a Credit / [leader
    // form] you may exhaust Boba → create a Credit." It must fire even when the attacking Bounty Hunter was
    // DEFEATED in the same combat (or blanked), so compute it here BEFORE the attacker-survival early-returns
    // that gate the attacker's OWN triggers. Keyed on the attacker's (printed) Bounty Hunter trait.
    $law007AtkCardID = ($attacker !== null) ? ($attacker->CardID ?? '') : '';
    if (!empty($combatCtx['defenderDefeated']) && $law007AtkCardID !== '' && HasTrait($law007AtkCardID, 'Bounty Hunter')) {
        if (_SWUCountActiveUnitsWithCardID($activePlayer, 'LAW_007') > 0) {
            SWUCreateCreditToken($activePlayer, 1);   // deployed Boba: mandatory
        } else {
            foreach (GetLeader($activePlayer) as $l) {
                if (empty($l->removed) && ($l->CardID ?? '') === 'LAW_007' && empty($l->Deployed) && !empty($l->Ready)) {
                    AddTrigger($activePlayer, 'LAW_007', 'LAW_007', '');   // leader form: may exhaust → Credit
                    break;
                }
            }
        }
    }
    // LAW_252 Fett's Firespray — "When Attack Ends: If the defending unit was defeated, create a Credit."
    // Its OWN ability, but it has NO "if this unit survived" clause, so the credit is still created when
    // Firespray is defeated in the same combat (the attack still ended + the defender was defeated). Fire it
    // here, above the attacker-survival early-return that gates the "if survived" abilities below.
    if (!empty($combatCtx['defenderDefeated']) && $law007AtkCardID === 'LAW_252') {
        SWUCreateCreditToken($activePlayer, 1);
    }
    // IC27_146 Boba Fett (Compensated If He Dies) — "When Attack Ends: If the defending unit was
    // defeated, you may ready 2 resources." Identical shape to LAW_252 above: his OWN attack-end
    // ability with no "if this unit survived" clause — the subtitle is literally about him dying — so
    // it fires here, ABOVE the attacker-survival early-return. Interactive (a "may"), so it goes
    // through the trigger bag rather than resolving inline.
    if (!empty($combatCtx['defenderDefeated']) && $law007AtkCardID === 'IC27_146') {
        AddTrigger($activePlayer, 'IC27_146', 'IC27_146', '');
    }
    // LAW_046 Chirrut Îmwe — "When Attack Ends: If this unit dealt combat damage to a base, you may heal
    // 4 damage from another unit." No "(and survives)" rider, so CR 16.c's fire-on-death DEFAULT applies:
    // with Overwhelm he can spill combat damage onto the base and die to the defender's counter in the
    // SAME combat, and the heal must still resolve.
    // Why he needs his own hoist: his ability is implemented on THIS (combat-hit) path rather than in
    // $onAttackEndAbilities, and the generic CR 16.c dead-attacker path in CollectAfterAttackTriggers is
    // gated on `isset($onAttackEndAbilities[$cardID.':0'])` — so it never covered him, and the blanket
    // `if (SWUObjGone($attacker)) return;` below silently dropped the trigger.
    // Gated on the attacker being GONE so this never double-fires with the live case in the switch below.
    // Law046Trigger reads its mzID ONLY to exclude ITSELF from the "another unit" pool, which a dead
    // attacker satisfies for free (GetZoneObject → null → excludeUID 0 → nothing excluded).
    // ⚠ Deliberately scoped to LAW_046. Other combat-hit-path attack-end cards may have the same gap, but
    // membership of the fire-on-death set MUST NOT be inferred from card text — that misread ~11 cards
    // before. _SWUAttackEndRequiresSurvival is the roster; audit per card, do not blanket-change.
    // "Is the ORIGINAL attacker still the object at this mzID?" — by UID, because a dead attacker's slot
    // gets reused by the unit that reindexes into it (see 'attackerUID' where the ctx is built).
    $law046UID  = intval($combatCtx['attackerUID'] ?? 0);
    $law046Live = ($attacker !== null && !SWUObjGone($attacker)
                   && $law046UID !== 0 && intval($attacker->UniqueID ?? 0) === $law046UID);
    if (!empty($combatCtx['dealtToBase'])
        && strval($combatCtx['attackerCardID'] ?? '') === 'LAW_046'
        && !$law046Live
        && !_SWUAttackEndRequiresSurvival('LAW_046')) {
        // Pass NO mzID: the slot may now hold an unrelated unit, and Law046Trigger uses it only to
        // exclude itself from "another unit" — which a dead attacker satisfies for free.
        AddTrigger($activePlayer, 'LAW_046', 'LAW_046', '');
    }
    // ASH_013 Ezra / ASH_016 Shin — leader field observers: "When a friendly unit's attack ends [dealing 3+
    // combat / any combat damage to a base]: …". Keyed on the LEADER, not the attacker, so they must fire even
    // when the attacking unit was DEFEATED in the same combat (e.g. dealt Overwhelm base damage then died to
    // the defender's counter). Placed ABOVE the attacker-survival early-return below, like the Boba hooks.
    if (intval($combatCtx['baseCombatDmg'] ?? 0) >= 3 && _SWULeaderReadyUndeployed($activePlayer, 'ASH_013')) {
        AddTrigger($activePlayer, 'ASH_013', 'ASH_013', $attackerMzID);   // undeployed: may exhaust → Advantage to a different unit
    }
    if (intval($combatCtx['baseCombatDmg'] ?? 0) >= 3 && _SWULeaderDeployed($activePlayer, 'ASH_013')) {
        AddTrigger($activePlayer, 'ASH_013#1', 'ASH_013#1', $attackerMzID);   // deployed: may give Advantage (no exhaust)
    }
    if (_SWULeaderReadyUndeployed($activePlayer, 'ASH_016')) {
        AddTrigger($activePlayer, 'ASH_016', 'ASH_016', $attackerMzID, strval(intval($combatCtx['baseCombatDmg'] ?? 0)));   // undeployed
    }
    if (_SWULeaderDeployed($activePlayer, 'ASH_016')) {
        AddTrigger($activePlayer, 'ASH_016#1', 'ASH_016#1', $attackerMzID, strval(intval($combatCtx['baseCombatDmg'] ?? 0)));   // deployed (once/round)
    }
    if (SWUObjGone($attacker)) return; // attacker defeated → its OWN triggers don't fire
    if (LostAbilities($attacker)) return; // SEC_046 Galen — a named attacker fires no "deals combat damage" trigger
    $cardID = $attacker->CardID ?? '';
    // ASH_101 The Great Mothers (Support) — When Attack Ends: if it dealt combat damage to 1+ non-leader
    // units, defeat those units (the defending unit). Support-aware: own CardID, OR the SUPPORT_GRANT captured
    // in combatCtx before expiry (the live marker is already stripped by the time this attack-END path runs).
    $ash101Granted = ($cardID === 'ASH_101') || (($combatCtx['supportGrant']['cardID'] ?? '') === 'ASH_101');
    if ($ash101Granted
        && !empty($combatCtx['dealtToUnit']) && empty($combatCtx['defenderIsLeader'])) {
        AddTrigger($activePlayer, 'ASH_101', 'ASH_101', $defenderMzID);
    }
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
        case 'LAW_033': // Hound's Tooth — "When Attack Ends: if this unit survived, you may defeat a unit
                        // with less power than this unit." (Survival gated at line 665; fires on any attack.)
            AddTrigger($activePlayer, 'LAW_033', 'LAW_033', $attackerMzID);
            break;
        case 'LAW_034': // Chewbacca — "When Attack Ends: if the defending unit was defeated, give it an
                        // Experience token and heal 3 from him." (Attacker survived — gated at line 665.)
            if (!empty($combatCtx['defenderDefeated'])) {
                AddTrigger($activePlayer, 'LAW_034', 'LAW_034', $attackerMzID);
            }
            break;
        // LAW_252 Fett's Firespray — handled ABOVE the survival early-return (its credit fires even when
        // Firespray itself is defeated, since it has no "if this unit survived" clause).
        case 'LAW_046': // Chirrut Îmwe — "When Attack Ends: if this unit dealt combat damage to a base,
                        // you may heal 4 from another unit."
            if (!empty($combatCtx['dealtToBase'])) {
                AddTrigger($activePlayer, 'LAW_046', 'LAW_046', $attackerMzID);
            }
            break;
        case 'SEC_209': // The Mandalorian — "attacks and defeats a unit: may capture an enemy non-leader unit."
            if (!empty($combatCtx['defenderDefeated'])) {
                AddTrigger($activePlayer, 'SEC_209', 'SEC_209', $attackerMzID);
            }
            break;
        case 'SHD_122': // Arquitens Assault Cruiser — attacks & defeats a NON-LEADER unit → put the defeated
                        // unit into play as a resource under YOUR control. Payload (CardID~owner) rides the
                        // mzID slot (FlushTriggerBag drops extraParams); resolved from the owner's discard.
            if (!empty($combatCtx['defenderDefeated']) && empty($combatCtx['defenderIsLeader'])) {
                AddTrigger($activePlayer, 'SHD_122', 'SHD_122',
                    ($combatCtx['defenderCardID'] ?? '') . '~' . intval($combatCtx['defenderOwner'] ?? 0));
            }
            break;
        case 'SEC_088': // First Light — "attacks and defeats a unit: may draw a card."
            if (!empty($combatCtx['defenderDefeated'])) {
                AddTrigger($activePlayer, 'SEC_088', 'SEC_088', $attackerMzID);
            }
            break;
        case 'SOR_133': // Seventh Sister — "deals combat damage to an opponent's base: may deal 3 to a ground unit."
            if (!empty($combatCtx['dealtToBase'])) {
                AddTrigger($activePlayer, 'SOR_133', 'SOR_133', '');
            }
            break;
        case 'LOF_166': // Blockade Runner — deals combat damage to a base: may give Exp to itself.
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'LOF_166', 'LOF_166', $attackerMzID);
            break;
        case 'SEC_150': // Valiant Commando — deals combat damage to a base: may defeat itself → 3 to that base.
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'SEC_150', 'SEC_150', $attackerMzID);
            break;
        case 'SEC_147': // Chopper — deals combat damage to a base: each player discards a card from hand.
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'SEC_147', 'SEC_147', $attackerMzID);
            break;
        case 'SEC_205': // Obi-Wan Kenobi — deals combat damage to a base: mill the defending player's deck,
                        // then may play that card from their discard this phase, ignoring aspect penalties.
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'SEC_205', 'SEC_205', $attackerMzID);
            break;
        case 'JTL_188': // Moff Gideon — deals combat damage to an opponent's base → that opponent's unit
                        // plays cost 1 more this phase (static flag; no decision).
            if (!empty($combatCtx['dealtToBase'])) {
                AddGlobalEffects(SWUMzOwner($defenderMzID, $activePlayer), 'SWU_GIDEON_TAX'); // "that opponent" whose base was hit
            }
            break;
        case 'SOR_088': // Blizzard Assault AT-AT — "attacks and defeats a unit: may deal the excess to an enemy ground unit."
            if (!empty($combatCtx['defenderDefeated']) && intval($combatCtx['excess'] ?? 0) > 0) {
                AddTrigger($activePlayer, 'SOR_088', 'SOR_088', '', strval(intval($combatCtx['excess'])));
            }
            break;
        case 'SHD_138': // Jango Fett — "When this unit attacks and defeats a unit: Draw a card."
            if (!empty($combatCtx['defenderDefeated'])) AddTrigger($activePlayer, 'SHD_138', 'SHD_138', '');
            break;
        case 'SHD_147': // Ketsu Onyo — "deals combat damage to a base: may defeat an upgrade costing 2 or less."
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'SHD_147', 'SHD_147', '');
            break;
        case 'SEC_017': // Sabé (deployed) — deals combat damage to a base: look at the defending player's
                        // hand, may discard a card; if you do, that player draws.
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'SEC_017', 'SEC_017', '');
            break;
        case 'LAW_054': // Maul — "When Attack Ends: if this unit dealt combat damage to a player's base,
                        // you may take control of a non-leader unit that player controls (reverts when
                        // Maul leaves play)."
            if (!empty($combatCtx['dealtToBase'])) AddTrigger($activePlayer, 'LAW_054', 'LAW_054', $attackerMzID);
            break;
    }

    // SHD_143 Ruthlessness (granted upgrade) — "When this unit attacks and defeats a unit: Deal 2 damage
    // to the defending player's base." The attacker bears the upgrade; the defending player is the owner
    // of the just-defeated defender (Twin Suns: derived from the defender mzID, not merely OtherPlayer).
    if (!empty($combatCtx['defenderDefeated']) && _SWUUnitHasUpgrade($attacker, 'SHD_143')) {
        SWUDealDamageToBase(2, SWUMzOwner($defenderMzID, $activePlayer));
    }

    // LAW_056 Cassian Andor (field passive) — "When a friendly unit's attack ends: if the defending unit
    // was defeated, deal 2 damage to a base." Fires once per Cassian the active player controls. Text says
    // "a base" (a free choice) → Twin Suns: choose which opponent's base per Cassian (2-player → the one).
    if (!empty($combatCtx['defenderDefeated'])) {
        // Thread each Cassian object as the damage SOURCE — the 2 damage is dealt BY that unit, so it must
        // stamp his per-unit base-damage markers (SEC_012 "has damaged an opponent's base", and LAW_205's
        // "if that unit damaged a base" when Cassian is himself the attacker). Without a source the damage
        // was attributed to nobody.
        $cassianObjs = [];
        global $playerID; $savedPID056 = $playerID; $playerID = intval($activePlayer);
        foreach (GetUnitsInPlay(intval($activePlayer)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'LAW_056') $cassianObjs[] = $u;
        }
        $playerID = $savedPID056;
        if (SeatCountForGame() <= 2) {
            foreach ($cassianObjs as $cas) SWUDealDamageToBase(2, OtherPlayer($activePlayer), $cas);
        } else {
            foreach ($cassianObjs as $cas)
                SWUQueueChooseOpponent($activePlayer, "LAW_056#0", "Cassian:_deal_2_to_which_opponent's_base?");
        }
    }

    // LAW_088 Anakin Skywalker (field passive) — "When a friendly unit's attack ends: if no other units
    // have attacked this phase, you may return it to its owner's hand. If you do, heal 2 from your base."
    // The attacker survived (gated at line 665). "No other units" = exactly one attack flag this phase.
    if (_SWUCountActiveUnitsWithCardID($activePlayer, 'LAW_088') > 0) {
        $attackers = 0;
        foreach ([1, 2] as $ap) {
            foreach (GetGlobalEffects($ap) as $ge) {
                if (preg_match('/^SWU_ATTACKED_\d+$/', (string)($ge->CardID ?? ''))) $attackers++;
            }
        }
        if ($attackers <= 1) AddTrigger($activePlayer, 'LAW_088', 'LAW_088', $attackerMzID);
    }

    // SEC_017 Sabé (leader FRONT passive) — "When a friendly unit deals combat damage to a base: you may
    // exhaust this leader → look at the top 2 of the defending player's deck, discard 1, other on top."
    // Active only while SEC_017 is the undeployed, ready leader (fires for ANY friendly attacker).
    if (!empty($combatCtx['dealtToBase']) && _SWULeaderReadyUndeployed($activePlayer, 'SEC_017')) {
        AddTrigger($activePlayer, 'SEC_017#1', 'SEC_017#1', '');
    }

    // SOR_150 Heroic Sacrifice — granted "When this unit deals combat damage: Defeat it." The per-attack
    // marker is captured into $combatCtx['attackerSelfDefeat'] at combat start (the SWU_DUR_ATTACK token
    // is already stripped by SWUExpireTurnEffects before this collection runs). Fires on combat damage
    // to a unit OR a base.
    if (!empty($combatCtx['attackerSelfDefeat']) && (!empty($combatCtx['dealtToBase']) || !empty($combatCtx['dealtToUnit']))) {
        AddTrigger($activePlayer, 'SOR_150', 'SOR_150', $attackerMzID);
    }

    // LAW_205 Flash the Vents — granted "after completing this attack, if that unit damaged a base,
    // defeat that unit." Reuses the HeroicSacrificeDefeatTrigger dispatcher (defeats the attacker).
    // "Damaged a base" is ANY damage to ANY base — either player's, by ANY route — not just this attack's
    // combat damage. Combat + Overwhelm spill are already in $combatCtx['dealtToBase']; the ability routes
    // (the attacker's own On Attack ping, or an attack-end ability it is the source of) show up only as the
    // per-unit base-damage stamps, and those are PHASE-scoped — hence the snapshot taken when Flash the
    // Vents declared the attack (LAW_205#0) and the delta here.
    if (!empty($combatCtx['law205SelfDefeat'])) {
        $law205Dmg = !empty($combatCtx['dealtToBase']);
        if (!$law205Dmg) {
            $u205 = intval(GetSWUVar('SWU_LAW205_UID', '0'));
            if ($u205 > 0) {
                $law205Dmg = _SWUUnitBaseDamageStamps($activePlayer, $u205)
                             > intval(GetSWUVar('SWU_LAW205_MARK', '0'));
            }
        }
        if ($law205Dmg) AddTrigger($activePlayer, 'LAW_205', 'LAW_205', $attackerMzID);
    }
    // LAW_062 Defiant Hammerhead — "defeat this unit after completing this attack" (unconditional, when
    // the +4/+0 was taken).
    if (!empty($combatCtx['law062SelfDefeat'])) {
        AddTrigger($activePlayer, 'LAW_205', 'LAW_205', $attackerMzID);   // same defeat-the-attacker dispatcher
    }

    // JTL_177 Stay on Target — granted "When this unit deals damage to a base: Draw a card." (this attack)
    if (!empty($combatCtx['jtl177BaseDraw']) && !empty($combatCtx['dealtToBase'])) {
        DoDrawCard(intval($activePlayer), 1);
    }

    // LOF_063 Oggdo Bogdo — "When this unit attacks and defeats a unit: heal 2 damage from this unit."
    if ($cardID === 'LOF_063' && !empty($combatCtx['defenderDefeated'])) {
        OnHealUnit(intval($activePlayer), $attackerMzID, 2);
    }

    // LOF_017 Darth Revan — "When a friendly unit attacks and defeats a unit: give an Experience token to
    // that friendly unit." Front (leader form, ready): "you may exhaust this leader" is the cost (LOF_017).
    // Deployed (leader unit): "you may" give the token with NO exhaust cost (LOF_017D). Controller-based;
    // the recipient is the attacker ($attackerMzID).
    //
    // Gated on a DEFENDER being defeated, so an ability defeating a NON-defender during the attack does not
    // qualify — but a defender defeated at ANY point during the attack does, including by an ability rather
    // than combat damage (ruling 2026-08-07).
    //
    // COUNT (ruling 2026-08-07): a multi-defender attack (TWI_135 Darth Maul) that defeats BOTH defenders
    // attacked-and-defeated TWO units, so the DEPLOYED side — which has no cost — fires once per defeated
    // defender (2 Experience). The FRONT side stays at ONE regardless: its cost is "exhaust this leader",
    // and an already-exhausted leader cannot pay it a second time.
    $defDefeated = intval($combatCtx['defendersDefeated'] ?? (!empty($combatCtx['defenderDefeated']) ? 1 : 0));
    if ($defDefeated > 0) {
        foreach (GetLeader(intval($activePlayer)) as $l) {
            if (empty($l->removed) && ($l->CardID ?? '') === 'LOF_017') {
                if (empty($l->Deployed) && !empty($l->Ready)) {
                    AddTrigger($activePlayer, 'LOF_017', 'LOF_017', $attackerMzID);   // exhaust cost → once
                } elseif (!empty($l->Deployed)) {
                    for ($i = 0; $i < $defDefeated; $i++) {
                        AddTrigger($activePlayer, 'LOF_017D', 'LOF_017D', $attackerMzID);
                    }
                }
                break;
            }
        }
    }

    // LOF_086 Drengir Spawn — "attacks and defeats a unit: give it Experience tokens equal to the
    // defeated unit's (printed) cost."
    if ($cardID === 'LOF_086' && !empty($combatCtx['defenderDefeated'])) {
        $n = intval(CardCost($combatCtx['defenderCardID'] ?? ''));
        for ($k = 0; $k < $n; $k++) DoGiveExperienceToken(intval($activePlayer), $attackerMzID);
    }

    // JTL_120 Dorsal Turret (upgrade) — host gains "When this unit deals combat damage to a unit while
    // attacking: Defeat that unit." Capture the defender's UniqueID (not its positional mzID): the deferred
    // trigger re-locates the SAME unit at resolution time, so if the defender already died to combat HP
    // damage it simply no-ops — a positional mzID would wrongly defeat whatever unit shifted into its slot.
    if (!empty($combatCtx['dealtToUnit']) && _SWUUnitHasUpgrade($attacker, 'JTL_120')) {
        $j120Def = GetZoneObject($defenderMzID);
        $j120Uid = ($j120Def !== null) ? intval($j120Def->UniqueID ?? 0) : 0;
        AddTrigger($activePlayer, 'JTL_120', 'JTL_120', 'UID:' . $j120Uid);
    }

    // ASH_137 Wipe Them Out (granted, this attack) — "you may deal its excess damage to another unit in
    // the same arena." Marker captured into combatCtx at combat start; fires when there is overkill.
    // NOTE: does NOT currently fire when the attacker TRADES (dies in the same combat) — the trigger fires
    // and its MayChoose queues, but the prompt doesn't surface through the combat trigger flush once the
    // attacker is removed (a combat trigger-ordering / index-shift edge). Deferred — see ash.md.
    if (!empty($combatCtx['ash137Excess']) && intval($combatCtx['excess'] ?? 0) > 0) {
        AddTrigger($activePlayer, 'ASH_137', 'ASH_137', $attackerMzID, strval(intval($combatCtx['excess'])));
    }
    // ASH_162 Rash Action (granted, this attack) — "When Attack Ends: if this unit dealt combat damage to
    // an opponent's base, that opponent discards a card." Non-interactive (the opponent picks the discard).
    if (!empty($combatCtx['ash162Discard']) && !empty($combatCtx['dealtToBase'])) {
        SWUDiscardCards(intval($activePlayer), 1);   // makes the active player's opponent discard 1
    }
    // ASH_005 Luke Skywalker (undeployed leader) — "When a friendly unit's attack ends: you may exhaust this
    // leader; if you do, heal 1 damage from that unit." Fires for the attacking player's ready, undeployed Luke.
    if (_SWULeaderReadyUndeployed($activePlayer, 'ASH_005')) {
        AddTrigger($activePlayer, 'ASH_005', 'ASH_005', $attackerMzID);
    }
    // ASH_005 Luke Skywalker (DEPLOYED unit side) — "When a friendly unit's attack ends: Heal 2 damage from
    // that unit or from your base." Field observer while Luke is deployed; fires for ANY friendly attack.
    if (_SWULeaderDeployed($activePlayer, 'ASH_005')) {
        AddTrigger($activePlayer, 'ASH_005#1', 'ASH_005#1', $attackerMzID);
    }
    // (ASH_013 Ezra + ASH_016 Shin leader-observer hooks moved ABOVE the attacker-survival early-return —
    //  they are field observers on the leader and must fire even when the attacker dies dealing base damage.)

    // LOF_025 Temple of Destruction (base) — "When a friendly unit deals 3 or more combat damage to an
    // enemy base: The Force is with you." Controller-based (any friendly attacker), so it rides this
    // collection point. baseCombatDmg accumulates both the direct base hit and any overwhelm spill.
    if (intval($combatCtx['baseCombatDmg'] ?? 0) >= 3) {
        $ldBase = GetBase(intval($activePlayer));
        if (!empty($ldBase) && ($ldBase[0]->CardID ?? '') === 'LOF_025') {
            TheForceIsWithYou(intval($activePlayer));
        }
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

// Does this card's attack-end ability require the attacker to SURVIVE the attack?
//
// CR 16.c makes firing-on-death the DEFAULT for "When Attack Ends"; survival is an opt-in carried by the
// "(and survives)" rider on the older "completes an attack" templating. Two sources, because the printed
// text is not always explicit:
//   1. the text rider, read from the dictionary (covers any future card without touching this list), and
//   2. this explicit roster, for cards whose printed text omits the rider but which still require it.
// ⚠ Do NOT infer membership from the phrase "When Attack Ends" or a bare "completes an attack" — those
// say nothing about survival, and a text-only heuristic wrongly marks ~11 of these as fire-on-death.
function _SWUAttackEndRequiresSurvival(string $cardID): bool {
    static $mustSurvive = [
        'SEC_096' => 1,  // Ahsoka Tano — I Learned It From You
        'SOR_015' => 1,  // Boba Fett — Collecting the Bounty
        'SEC_107' => 1,  // Chancellor Valorum — Civil Servant
        'SEC_006' => 1,  // Colonel Yularen — This Is Why We Plan
        'SHD_059' => 1,  // Embo — Stoic and Resolute
        'SOR_192' => 1,  // Ezra Bridger — Resourceful Troublemaker
        'TWI_053' => 1,  // Finn — On the Run
        'LAW_033' => 1,  // Hound's Tooth — Hunters' Approach
        'LOF_156' => 1,  // Infused Brawler
        'SEC_003' => 1,  // Lama Su — We Modified Their Genetics
        'SOR_009' => 1,  // Leia Organa — Alliance General
        'LAW_054' => 1,  // Maul — Master of the Shadow Collective
        'LAW_074' => 1,  // Maz Kanata — Where's My Boyfriend?
        'LOF_038' => 1,  // Pong Krell — It's Treason Then
        'LOF_016' => 1,  // Qui-Gon Jinn — Student of the Living Force
        'LAW_001' => 1,  // Saw Gerrera — Bring Down the Empire
        'SEC_174' => 1,  // Saw Gerrera's U-Wing — Breaking the Rules
        'JTL_070' => 1,  // U-Wing Lander
        'LAW_215' => 1,  // Vermillion — Qi'ra's Auction House
        'SOR_146' => 1,  // Zeb Orrelios — Headstrong Warrior
        'JTL_089' => 1,  // The Invisible Hand — Crawling With Vultures ("(and survives)" in its text)
    ];
    if (isset($mustSurvive[$cardID])) return true;
    $txt = (string)(CardText($cardID) ?? '') . ' ' . (string)(CardDeployText($cardID) ?? '');
    return stripos($txt, 'completes an attack (and survives)') !== false;
}

// After-attack: fires unconditionally so handlers can inspect the outcome.
function CollectAfterAttackTriggers($activePlayer, $attackerMzID, $defenderMzID, array $combatCtx = []): void {
    $attacker = GetZoneObject($attackerMzID);
    // Expose the attacker's base-combat-damage so an OnAttackEnd-from-upgrade ability (ASH_183) can gate
    // on "dealt combat damage to an opponent's base" (the upgrade dispatch carries no combatCtx).
    SetSWUVar('SWU_LAST_ATTACKER_BASEHIT', strval(intval($combatCtx['baseCombatDmg'] ?? 0)));
    // ASH_144 Vane's Snub Fighter — "When a friendly unit's attack ends: if it dealt combat damage to a
    // base, give an Advantage token to this unit." Field observer (any friendly attacker); non-interactive.
    if (!empty($combatCtx['dealtToBase'])) {
        global $playerID; $savedPID144 = $playerID; $playerID = intval($activePlayer);
        foreach (GetUnitsInPlay(intval($activePlayer)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'ASH_144') {
                $mz144 = SWUFindMzByUID(intval($u->UniqueID ?? 0));
                if ($mz144 !== null) DoGiveAdvantageToken(intval($activePlayer), $mz144);
            }
        }
        $playerID = $savedPID144;
    }
    // ASH_031 Hera Syndulla — When Attack Ends: if this unit dealt combat damage to a base, heal that
    // much damage from your base. (Own-attacker; uses the base-combat-damage amount from combatCtx.)
    if ($attacker !== null && ($attacker->CardID ?? '') === 'ASH_031' && intval($combatCtx['baseCombatDmg'] ?? 0) > 0) {
        $ctrl031 = intval($attacker->Controller ?? $activePlayer);
        OnHealBase($ctrl031, $ctrl031, intval($combatCtx['baseCombatDmg']));
    }
    // SEC_046 Galen Erso — a named attacker that lost its abilities fires no "completes an attack" trigger.
    global $onAttackEndAbilities;
    // Only collect the OnAttackEnd trigger when a REAL handler is registered. A card can carry a
    // HasOnAttackEndAbility stub with NO $onAttackEndAbilities handler — either a silent no-op, or
    // (LAW_034/ASH_101/LAW_046/LAW_054, etc.) because its "When Attack Ends" is implemented on the
    // combat-hit path (SWUCollectCombatHitTriggers, gated on $combatCtx). Collecting a handler-less
    // OnAttackEnd trigger would add a phantom second trigger → an unanswered trigger-ordering MZCHOOSE.
    if ($attacker !== null && !isset($attacker->removed) && HasOnAttackEndAbility($attacker->CardID)
        && isset($onAttackEndAbilities[$attacker->CardID . ':0']) && !LostAbilities($attacker)) {
        // SOR_146 Zeb Orrelios — "completes an attack: IF the defender was defeated, ...". The "if" is
        // a trigger condition; gate collection on the combat outcome so nothing fires (no decision)
        // when the defender survived. Other OnAttackEnd cards (SOR_009/015/192) are unconditional.
        if ($attacker->CardID !== 'SOR_146' || !empty($combatCtx['defenderDefeated'])) {
            AddTrigger($activePlayer, 'OnAttackEnd', $attacker->CardID, $attackerMzID);
        }
    }
    // CR 16.c — a "When Attack Ends" ability STILL triggers when the unit it is on is defeated by combat
    // damage. That is the DEFAULT; requiring survival is the exception, opted into per-card by the
    // "(and survives)" rider (see _SWUAttackEndRequiresSurvival). The surviving-attacker gate above stays
    // as-is for the opt-in cards; everything else needs this second path.
    // The trigger resolves after the dead attacker has been purged, so it carries NO mzID and the CardID
    // comes from combatCtx — the object may already be unreachable. Every handler on this path either
    // ignores its mzID, or is self-targeting (ready/shield THIS unit) and already no-ops via SWUObjGone,
    // or uses the mzID purely to exclude ITSELF from an "another friendly unit" pool, which a dead
    // attacker satisfies for free.
    $deadEndCardID = strval($combatCtx['attackerCardID'] ?? '');
    if (($attacker === null || isset($attacker->removed))
        && $deadEndCardID !== ''
        && !_SWUAttackEndRequiresSurvival($deadEndCardID)
        && isset($onAttackEndAbilities[$deadEndCardID . ':0'])
        && ($attacker === null || !LostAbilities($attacker))) {
        AddTrigger($activePlayer, 'OnAttackEnd', $deadEndCardID, '');
    }
    // Upgrade-granted "When attached unit completes an attack (and survives): ..." — the surviving-
    // attacker null check above is the "and survives" gate (a defeated attacker is removed by now).
    // JTL_197 Anakin Skywalker (may return itself to hand). Mirrors the OnAttack-from-upgrade scan.
    if ($attacker !== null && !isset($attacker->removed)) {
        global $onAttackEndFromUpgradeAbilities;
        foreach (GetUpgradesOnUnit($attacker) as $upgrade) {
            if (($upgrade->CardID ?? '') === 'ASH_183') continue;   // ASH_183 fires from combatCtx below (survives host death)
            if (isset($onAttackEndFromUpgradeAbilities[$upgrade->CardID])
                && !_SWUGalenSuppressesCard(intval($upgrade->Owner ?? $attacker->Controller ?? $activePlayer), $upgrade->CardID ?? '')) { // SEC_046 Galen
                AddTrigger($activePlayer, 'OnAttackEndFromUpgrade', $upgrade->CardID, $attackerMzID);
            }
        }
    }
    // Support (ASH) — fire the supporting unit's "When Attack Ends" ability for this attack (closure
    // runs against A's mzID). The surviving-attacker null check above also gates this naturally.
    if ($attacker !== null && !isset($attacker->removed) && !LostAbilities($attacker)) {
        // Read the SUPPORT_GRANT from combatCtx (captured before the SWU_DUR_ATTACK expiry that runs before
        // this collection), falling back to the live marker for any caller that didn't capture it.
        $sgEnd = $combatCtx['supportGrant'] ?? _SWUSupportGrant($attacker);
        if ($sgEnd !== null && ($sgEnd['cardID'] ?? '') !== '' && HasOnAttackEndAbility($sgEnd['cardID'])) {
            AddTrigger($activePlayer, 'SupportOnAttackEnd', $sgEnd['cardID'], $attackerMzID);
        }
    }
    // Advantage tokens (ASH_T02) on the attacker shed when its attack ends — as an ORDERED trigger so a
    // power-reading When-Attack-Ends ability (LOF_038 Pong Krell) can resolve first while the tokens still
    // buff power. The shed itself is one bag slot ("defeat 1 / defeat all", auto-all if nothing else is
    // pending) rather than one trigger per token. (Defender side sheds synchronously — see Step3.)
    if ($attacker !== null && !isset($attacker->removed) && _SWUCountAdvantageSubcards($attacker) > 0) {
        AddTrigger($activePlayer, 'AdvantageShed', 'ASH_T02', $attackerMzID);
    }
    // ASH_184 Follow Me — "Attack with a unit. After completing the attack, give 3 Advantage tokens to a
    // unit." The marker was captured into combatCtx at combat start, so this fires even if the attacker
    // was defeated this attack (the grant targets any unit, not the attacker). Rides the EffectStack.
    if (!empty($combatCtx['ash184GiveAdv'])) {
        AddTrigger($activePlayer, 'ASH_184', 'ASH_184', '');
    }
    // ASH_183 Whistling Birds — deal 2 to each enemy in the captured arena if the host dealt combat damage to a
    // base this attack. Fires from combatCtx (captured at combat start) so it still resolves when the host died.
    if (!empty($combatCtx['ash183Arena']) && intval($combatCtx['baseCombatDmg'] ?? 0) > 0) {
        global $playerID; $spAsh183 = $playerID; $playerID = intval($activePlayer);
        $arena183 = $combatCtx['ash183Arena'];
        $uids183 = [];
        foreach (ZoneSearch("their{$arena183}", AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $uids183[] = intval($o->UniqueID ?? 0);
        }
        foreach ($uids183 as $u183) {
            $mz183 = SWUFindMzByUID($u183);
            if ($mz183 !== null) SWUDealDamageToUnit($mz183, 2, intval($activePlayer));
        }
        $playerID = $spAsh183;
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
    if ($flushed === 0 && (GetSWUVar('SWU_CHAINED_ATTACK', '') !== '' || GetSWUVar('SWU_MONMOTHMA_LOOP', '') !== ''
            || GetSWUVar('SWU_SHD145_LOOP', '') !== '' || GetSWUVar('SWU_TS26059_LOOP', '') !== '')) {   // SHD_145 / TS26_59 Brothers count-capped loops
        _SWUQueueOrchestration($activePlayer, "SWU_TRIGGER_RESUME|{$activePlayer}", 20);
    }
}

// Execute the attack: fire Step 1 triggers then queue SWUCombatDamage to run after they resolve.
// $attackerMzID and $targetMzID are perspective-relative from $player.
// SEC_038 Condemn — "While attached unit is ATTACKING, it gains … and loses all other abilities."
// Tags the attacker with an attack-duration LOSE_ABILITIES marker.
//
// ⚠ TIMING (CR 3.3): "while attacking" abilities become active at the BEGIN ATTACK step — which is
// AFTER "Check restrictions" (CR 3.2), where the Sentinel/Saboteur gate lives (CR 3.2.b: a Sentinel
// must be chosen "unless the attacker has Saboteur"). So the suppression must NOT be in force while the
// legal-target list is built, or a Saboteur attacker wearing Condemn wrongly loses its ignore-Sentinel
// and gets redirected onto the Sentinel. Saboteur's OTHER half — defeating the defender's Shields —
// resolves during Begin attack (CR 3.3 triggers "including Restore and Saboteur") and IS suppressed.
//
// Called from every begin-attack entry point (the normal path, Ambush, and Maul's two-unit combat) so
// no attack route silently skips the suppression. Auto-expires via SWUExpireTurnEffects('attack').
// Any number of Condemns ⇒ one marker; the granted On Attack's mutual-suppression is handled in the
// upgrade scan in SWUCollectCombatStep1Triggers.
function _SWUApplyCondemnSuppression($attacker, string $attackerMzID): void {
    if ($attacker === null || $attackerMzID === '') return;
    foreach (GetUpgradesOnUnit($attacker) as $cu) {
        if (($cu->CardID ?? '') === 'SEC_038') {
            AddTurnEffect($attackerMzID, SWUMakeTurnEffect('SEC_038', [], SWU_DUR_ATTACK));
            return;
        }
    }
}

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

    // Attacker leans toward its target. Queued here, once the attacker is known valid but before any
    // of the combat mutations below, so both endpoints still resolve on the pre-render board. Fires
    // for BOTH unit and base targets: a base has a real mzID, and base attacks are the commonest
    // attack in the game. Queue-only — the exhaust/damage animations queued later play alongside it.
    // $lungeTgt uses a plain null check, NOT SWUObjGone: a base object is never "removed" and must
    // not be filtered out.
    $lungeTgt = GetZoneObject($targetMzID);
    if ($lungeTgt !== null) {
        SWUQueueLungeAnim(
            $attackerMzID,
            $targetMzID,
            intval($player),
            intval($attacker->UniqueID ?? 0) ?: null,
            intval($lungeTgt->UniqueID ?? 0) ?: null
        );
    }

    // Mark this unit as having attacked this phase (per-unit, cleared at RegroupPhaseStart). Used by
    // "ready a unit that didn't attack this phase" effects (SEC_177).
    $atkUID = intval($attacker->UniqueID ?? 0);
    if ($atkUID > 0) AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_UNIT_ATTACKED_' . $atkUID);
    // Per-player "a friendly unit attacked this phase" flag (TWI_007 Captain Rex). Cleared at RegroupPhaseStart.
    AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_FRIENDLY_ATTACKED');

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
    SetSWUVar('SWU_PENDING_DEF_REACTION', ''); // combat-pause flag — clear stale state each attack
    SetSWUVar('SWU_LAST_DEFENDER_DEFEATED', ''); // "When Attack Ends: if the defending unit was defeated" (ASH_033/036/223) — set in SWUCombatDamage
    SetSWUVar('SWU_LAST_ATTACKER_BASEHIT', '0'); // "if this unit dealt combat damage to a base" (ASH_183) — set in SWUCombatDamage
    SetSWUVar('SWU_CURRENT_DEFENDER', $targetMzID);
    // Capture the defender UNIT's UniqueID (0 for a base target) so SWUCombatDamage can re-resolve a
    // reindexed target OR detect a target that LEFT PLAY before damage (e.g. SEC_187 Grievous bounces
    // himself On Defense) — in which case the attack fizzles instead of hitting whatever unit shifted
    // into the stale mzID slot.
    $_defObj = (strpos($targetMzID, 'Arena') !== false) ? GetZoneObject($targetMzID) : null;
    SetSWUVar('SWU_CURRENT_DEFENDER_UID', strval($_defObj !== null ? intval($_defObj->UniqueID ?? 0) : 0));
    SetSWUVar('SWU_DEFENDER_DEFEATED_IN_ATTACK', ''); // per-attack; set if the defender dies mid-attack
    // SEC_194 per-action base-attack tracking: a transient recording that THIS action attacks a base
    // (and whose). SWUAfterAction reads it to finalize SWU_LAST_ACTION. The base attacked is the
    // opponent's (a unit can only attack an enemy base).
    if (strpos($targetMzID, 'Base') !== false) {
        // Twin Suns: the base being attacked belongs to the specific defender named by $targetMzID
        // (p{n}Base in N-player; theirBase in 2-player → the one opponent). Not merely OtherPlayer.
        $baseOwner = SWUMzOwner($targetMzID, intval($player));
        SetSWUVar('SWU_ACTION_BASEATK', strval($baseOwner));
        AddGlobalEffects($baseOwner, 'SWU_BASE_ATTACKED'); // per-phase "your base was attacked" (ASH_119); cleared at RegroupPhaseStart
        // ASH_160 Kachirho Militia — "When an enemy GROUND unit attacks your base: ready this unit. Once
        // each round." Inline ready for the base owner's Kachirho(s); per-UID once-per-round flag.
        if (strpos($attackerMzID, 'GroundArena') !== false) {
            _SWUAsh160ReadyOnBaseAttack($baseOwner);
            _SWUTwi166ReadyOnBaseAttack($baseOwner); // TWI_166 Aurra Sing
        }
        // SHD_241 Kragan Gorr — "When an enemy unit attacks your base: Give a Shield token to a friendly
        // unit in the same arena as the attacker." (Base owner from the target mzID; any arena.)
        _SWUShd241ShieldOnBaseAttack($baseOwner,
            strpos($attackerMzID, 'SpaceArena') !== false ? 'Space' : 'Ground');
    }
    // TWI_012 Anakin (front action) — "If it's attacking a unit, it gets +2/+0 for this attack." The
    // marker was placed on the attacker by TWI_012#0; apply the bonus only vs a unit target.
    if (strpos((string)$targetMzID, 'Base') === false && is_array($attacker->TurnEffects ?? null)
        && in_array('TWI_012_ATK', $attacker->TurnEffects, true)) {
        SWUAddAttackPowerBonus($attackerMzID, 2);
    }

    // Expose the current attacker (in the active player's frame, "my…") so an OnDefense ability that
    // affects "the attacker" (LOF_067 Chirrut) can resolve it — flip "my"→"their" for the defender frame.
    SetSWUVar('SWU_CURRENT_ATTACKER', $attackerMzID);
    // …and its UniqueID, so a mid-attack defeat of the ATTACKER ITSELF (its own ability killing it, e.g.
    // SEC_150 Valiant Commando's sacrifice) can still be recognised as "defeated while attacking".
    SetSWUVar('SWU_CURRENT_ATTACKER_UID', strval(intval($attacker->UniqueID ?? 0)));
    _SWUApplyCondemnSuppression($attacker, $attackerMzID);   // CR 3.3 — "while attacking" starts HERE

    // ASH_186 Treacherous Minefield — granted "On Attack: deal 2 damage to this unit" (phase). Applied
    // here at attack time (the marker was placed on each unit in the chosen arena when the event resolved).
    if (is_array($attacker->TurnEffects ?? null) && in_array('ASH_186', $attacker->TurnEffects, true)) {
        SWUDealDamageToUnit($attackerMzID, 2, intval($attacker->Controller ?? $player));
    }

    // SOR_212 Strafing Gunship: "While this unit is attacking a ground unit, the defender gets -2/-0."
    // The SWU_DEF_DEBUFF_N marker lives on the ATTACKER (SWUCombatDamage reads it from the attacker to
    // reduce the defender's counter-power); it's consumed there, so it's one-shot per attack. Ground-only
    // — a space defender is unaffected (the -0 HP is a no-op).
    if (($attacker->CardID ?? '') === 'SOR_212' && strpos($targetMzID, 'GroundArena') !== false) {
        AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2');
    }
    // TWI_205 Clone Dive Trooper: "Coordinate - While this unit is attacking, the defender gets -2/-0."
    if (($attacker->CardID ?? '') === 'TWI_205' && IsCoordinateActive(intval($attacker->Controller ?? 0))) {
        AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2');
    }
    // TWI_139 Corner the Prey — the granted attacker gets +1/+0 for each damage on the defender at the
    // start of this attack. The marker was placed on the chosen attacker when the event resolved.
    if (is_array($attacker->TurnEffects ?? null) && in_array('TWI_139', $attacker->TurnEffects, true)) {
        $defObj = GetZoneObject($targetMzID);
        $defDmg = ($defObj !== null) ? intval($defObj->Damage ?? 0) : 0; // works for a unit or a base defender
        if ($defDmg > 0) SWUAddAttackPowerBonus($attackerMzID, $defDmg); // attack-duration marker auto-expires
    }
    // SHD_230 Swoop Down — the granted attacker attacking a ground unit: +2/+0 self, defender -2/-0.
    if (is_array($attacker->TurnEffects ?? null) && in_array('SHD_230', $attacker->TurnEffects, true)
        && strpos($targetMzID, 'GroundArena') !== false) {
        AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2');
        SWUAddAttackPowerBonus($attackerMzID, 2);
    }
    // ASH_046 Scion Shuttle (Support) — "While this unit is attacking, the defending unit gets -1/-1."
    // Apply a real attack-duration -1/-1 STAT_DEBUFF to the DEFENDER (covers both its counter-power via
    // ObjectCurrentPower and its lethality via ObjectCurrentHP). Own attack OR Support-lent (_SWUAttackerGrants).
    if (_SWUAttackerGrants($attacker, 'ASH_046') && strpos($targetMzID, 'Arena') !== false) {
        $d46 = GetZoneObject($targetMzID);
        if ($d46 !== null && empty($d46->removed)) {
            AddTurnEffect($targetMzID, SWUMakeTurnEffect('ASH_046', [1, 1], SWU_DUR_ATTACK));
            // If the -1 HP drops the defender to no remaining HP, it is defeated by SBA BEFORE combat damage
            // (so it deals no counter). Run the shrink-defeat sweep now, while the -1/-1 marker is live.
            SWUCheckShrinkDefeats();
        }
    }
    // LOF_014 Grand Inquisitor (deployed) — On Attack: "the defender gets -2/-0 for this attack." Applied
    // synchronously here (not via the deferred OnAttack trigger) so the marker exists before SWUCombatDamage
    // reads it. (The $onAttackAbilities["LOF_014:0"] stub-handler is a no-op.)
    if (($attacker->CardID ?? '') === 'LOF_014' && IsLeaderUnit($attacker)) {
        AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2');
    }
    // IBH_010 / IBH_042 Han Solo — Raid 2 (keyword, auto) + On Attack: the defender gets -2/-0 for this
    // attack. Synchronous marker (the deferred OnAttack trigger is too late). Stub handler is a no-op.
    if (in_array($attacker->CardID ?? '', ['IBH_010', 'IBH_042'], true)) {
        AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2');
    }
    // SEC_208 Hunter — On Attack: if the defender is exhausted, it gets -4/-0 for this attack. Applied
    // synchronously (the deferred OnAttack trigger is too late — SWUCombatDamage reads the marker first).
    if (($attacker->CardID ?? '') === 'SEC_208') {
        $defObj = GetZoneObject($targetMzID);
        if ($defObj !== null && empty($defObj->removed) && intval($defObj->Status ?? 0) === 0) {
            AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_4');
        }
    }
    // SHD_216 Chain Code Collector — On Attack: if the defender has a Bounty, it gets -4/-0 for this attack.
    // Synchronous (the deferred OnAttack trigger is too late — SWUCombatDamage reads the marker first).
    if (($attacker->CardID ?? '') === 'SHD_216') {
        $d216 = GetZoneObject($targetMzID);
        if ($d216 !== null && empty($d216->removed) && ObjectHasBounty($d216) > 0) {
            AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_4');
        }
    }
    // SHD_219 Enfys Nest — "While a friendly unit (including this one) is attacking using Ambush, the
    // defender gets -3/-0." Field passive gated on the attacker's Ambush-attack marker + controlling SHD_219.
    if (is_array($attacker->TurnEffects ?? null) && in_array('SWU_AMBUSH_ATTACK', $attacker->TurnEffects, true)) {
        foreach (GetUnitsInPlay(intval($attacker->Controller ?? $player)) as $u219) {
            if (empty($u219->removed) && ($u219->CardID ?? '') === 'SHD_219') {
                AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_3'); break;
            }
        }
    }
    // SEC_224 Saw's Renegades — "Each exhausted enemy unit gets -2/-0 while defending." Field passive:
    // while its controller has any unit attacking an EXHAUSTED enemy, that defender gets -2/-0.
    $def224 = GetZoneObject($targetMzID);
    if ($def224 !== null && empty($def224->removed) && intval($def224->Status ?? 0) === 0) {
        foreach (GetUnitsInPlay(intval($attacker->Controller ?? $player)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'SEC_224') {
                AddTurnEffect($attackerMzID, 'SWU_DEF_DEBUFF_2'); break;
            }
        }
    }

    // Restore (CR 7.6.6 / keyword text "When this unit attacks, heal X damage from your base"):
    // fires on EVERY attack — a unit OR a base target — not just base attacks. Heal the attacker's
    // own base by the Restore value once per attack, here at attack time.
    $restoreVal = LostAbilities($attacker) ? null : GetKeyword_Restore_Value($attacker);
    if ($restoreVal !== null && $restoreVal > 0) OnHealBase($player, $player, $restoreVal);

    // LAW_086 The Stranger — "While attacking, you may have the defending unit deal combat damage before
    // this unit." Offer the attacker the choice when attacking a UNIT (not a base), before combat damage.
    // YES sets the DEFENDER_FIRST marker (read in SWUCombatDamage); these block-1 decisions resolve ahead
    // of the SWUCombatDamage commit below.
    // NOT offered when the attacker ALREADY deals its combat damage first (SOR_217 Shoot First's
    // SHOOT_FIRST marker, SOR_198 / SHD_234 innate, ASH_202 Carson Teva's grant): DEFENDER_FIRST is the
    // exact REVERSE of that ordering and the two are mutually exclusive where they are read, so the
    // offer could only ever be a no-op. Same "skip the pointless offer" rule as SEC_186/SEC_210.
    // The predicate is shared with the resolver below rather than duplicated — an offer gated on one
    // rule and resolved by another is how this diverges again.
    if (($attacker->CardID ?? '') === 'LAW_086' && strpos($targetMzID, 'Arena') !== false
        && !_SWUAttackerDealsDamageFirst($attacker)) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
            tooltip: "Have_the_defending_unit_deal_combat_damage_first?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_086#0|{$attackerMzID}", 1);
    }

    // Step 1: collect On Attack / On Defense triggers, then flush them onto the
    // EffectStack with a COMBAT continuation that queues SWUCombatDamage once the
    // triggers fully resolve. With no triggers, queue combat damage directly.
    CollectCombatStep1Triggers($player, $attackerMzID, $targetMzID);
    $triggered = FlushCombatTriggerBag($player, $attackerMzID, $targetMzID);
    if ($triggered === 0) {
        $attackerUID = intval($attacker->UniqueID ?? 0);
        _SWUQueueOrchestration($player, "SWUCombatDamage|{$attackerMzID}|{$targetMzID}|{$attackerUID}", 1);
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

// A combat's terminal After Action must be skipped when the attack is a BONUS attack nested inside a
// larger action (a Support attack launched by a deploy/play trigger) — that outer action owns the single
// After Action via its deferred SWU_TRIGGER_RESUME terminal. _SWUInTriggerResumeMode() detects a pending
// resume in the live queue, but across a request boundary (an interactive target/On-Attack decision mid
// bonus-attack) the resume may already have executed by the time combat damage resolves, so the live scan
// returns false and combat would run a SECOND After Action → the turn double-swaps = a free extra action
// (masked only when the seat/initiative state makes the second swap idempotent). So ALSO honor a PERSISTED
// flag set at the bonus attack's launch (BeginSWUAttack, when the attacker bears a SUPPORT_GRANT marker),
// which survives the boundary in the serialized gamestate. Consume it here so it can't leak to a later attack.
function _SWUCombatFinishAction($player): void {
    $skip = _SWUInTriggerResumeMode();
    if (GetSWUVar('SWU_COMBAT_SKIP_AFTERACTION', '') === '1') $skip = true;
    SetSWUVar('SWU_COMBAT_SKIP_AFTERACTION', '');   // consume regardless of which combat terminal is reached
    if (!$skip) SWUAfterAction($player);
}

// DQ handler: resolve combat damage after Step 1 triggers have fully resolved.
global $customDQHandlers;
$customDQHandlers["SWUCombatDamage"] = function($player, $parts, $lastDecision) {
    global $playerID, $gDeferredReplacements;
    // parts[3] (optional) = the active/attacker player. When a combat-pause resume commits damage from the
    // DEFENDER's drain, this CUSTOM is queued on the defender's queue ($player = defender), so re-derive
    // the attacker frame from parts[3] — "my…" mzIDs below resolve relative to the attacker, not $player.
    $player       = intval($parts[3] ?? $player);
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
    $_logOverwhelm  = 0;  // Overwhelm spill amount, logged AFTER the attack line (see below)

    if ($attacker === null || (isset($attacker->removed) && $attacker->removed)) {
        $playerID = $savedPID;
        _SWUCombatFinishAction($player);
        return;
    }

    // Frame animations play on the PRE-mutation board (before the re-render), where the defender is still
    // at the slot it occupied when the attack was declared. If an On-Attack effect defeated an EARLIER unit
    // (reindexing the defender down), the re-resolved slot below would point the "damage" flash at whatever
    // now sits in the old slot (e.g. the just-defeated unit) — misleading. So animate the defender at its
    // PRE-action slot ($targetAnimMzID) while all game logic uses the re-resolved $targetMzID. In the common
    // (no-reindex) case the two are identical.
    $targetAnimMzID = $targetMzID;
    // Defender re-validation (unit targets only). If the target mzID is stale because the defender
    // reindexed (another unit left during step-1 triggers), re-resolve it by UniqueID. If the defender
    // genuinely LEFT PLAY before damage (bounced/defeated by an On-Defense reaction — SEC_187 Grievous),
    // the attack has no target and fizzles: deal NO combat damage (don't hit whatever shifted into the
    // slot). A base target (expected UID 0) skips this entirely.
    $defUidExpected = intval(GetSWUVar('SWU_CURRENT_DEFENDER_UID', '0'));
    if ($defUidExpected > 0 && (
            SWUObjGone($target)
            || intval($target->UniqueID ?? 0) !== $defUidExpected)) {
        $tzone = explode('-', $targetMzID)[0];
        $found = null;
        foreach (GetZone($tzone) as $idx => $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? 0) === $defUidExpected) {
                $found = $u; $targetMzID = $tzone . '-' . $idx; break;
            }
        }
        if ($found === null) {  // defender left play before damage → attack fizzles
            // …but if it left play because it was DEFEATED during this attack, the attacker still
            // "attacked and defeated" it (ruling 2026-08-07), so the attack-end observers must run.
            // Deliberately CollectAfterAttackTriggers, not the full CollectCombatStep3Triggers: there are
            // no defeated cards to collect (SWUDefeatUnit already fired the defender's When Defeated) and
            // no combat damage was dealt, and Step 3's trailing _SWUDefeatAllAdvantageTokens($targetMzID)
            // would shed the tokens of whatever unit reindexed INTO the dead defender's slot.
            // CR step 4.c — "If the defending unit is no longer in-play, no combat damage is dealt
            // UNLESS the attacker has Overwhelm." CR §9.11 / 7.f then say ALL of the attacker's combat
            // damage is treated as excess and dealt to the enemy base (and, per 7.f, the attacker is NOT
            // considered to have attacked that base — _SWUOverwhelmSpillToBase encodes that distinction).
            // Power is recomputed here rather than reusing the main block below, because that block is
            // past this early return. Only the modifiers that DO NOT depend on a live defender are
            // applied — which is not a shortcut but the correct set:
            //   • included: current power, Raid, LOF_206's HP-as-damage, ASH_207's Ambush +2 (all
            //     target-independent);
            //   • excluded by the rules: ASH_054 / SEC_033 Sly Moore "-X while attacking a BASE" — this
            //     is NOT a base attack (7.f), so they must not apply;
            //   • excluded of necessity: SOR_130 / SHD_138 / ASH_241, whose conditions inspect the
            //     defender (damaged / has a Bounty) and cannot be evaluated once it is gone. They also
            //     GRANT Overwhelm conditionally, so a unit relying solely on them is not covered here.
            $owAmt = 0;
            if (HasKeyword_Overwhelm($attacker) && !LostAbilities($attacker)) {
                $owAmt = intval(ObjectCurrentPower($attacker));
                $owRaid = GetKeyword_Raid_Value($attacker);
                if ($owRaid !== null && $owRaid > 0) $owAmt += $owRaid;
                if (is_array($attacker->TurnEffects ?? null) && in_array('SWU_HP_AS_DAMAGE', $attacker->TurnEffects)) {
                    $owAmt = max(0, intval(ObjectCurrentHP($attacker)) - intval($attacker->Damage ?? 0));
                }
                if (($attacker->CardID ?? '') === 'ASH_207' && is_array($attacker->TurnEffects ?? null)
                    && in_array('SWU_AMBUSH_ATTACK', $attacker->TurnEffects, true)) {
                    $owAmt += 2;
                }
            }
            $_ddia = GetSWUVar('SWU_DEFENDER_DEFEATED_IN_ATTACK', '');
            if ($_ddia !== '' || $owAmt > 0) {
                SetSWUVar('SWU_DEFENDER_DEFEATED_IN_ATTACK', '');
                $_dparts = explode('|', $_ddia);
                $fizzCtx = [
                    'dealtToBase' => false, 'dealtToUnit' => false,
                    'defenderDefeated' => ($_ddia !== ''), 'defendersDefeated' => ($_ddia !== '' ? 1 : 0),
                    'defenderCardID' => $_dparts[0] ?? '',
                    'defenderOwner' => intval($_dparts[1] ?? 0),
                    'defenderIsLeader' => !empty($_dparts[2]),
                    'excess' => 0, 'baseCombatDmg' => 0,
                    'attackerCardID' => ($attacker !== null) ? ($attacker->CardID ?? '') : '',
                    'attackerUID' => ($attacker !== null) ? intval($attacker->UniqueID ?? 0) : 0,
                ];
                if ($owAmt > 0) {
                    $spilled = _SWUOverwhelmSpillToBase($player, $targetMzID, $attacker, $owAmt, $fizzCtx);
                    $fizzCtx['excess'] = $spilled;
                    if ($spilled > 0) {
                        AddGameLogEntry('OVERWHELM', 'Overwhelm: ' . $spilled . ' damage to P'
                            . (3 - intval($player)) . '\'s base');
                    }
                }
                CollectAfterAttackTriggers($player, $attackerMzID, $targetMzID, $fizzCtx);
            }
            $playerID = $savedPID;
            _SWUCombatFinishAction($player);
            return;
        }
        $target = $found;
    }

    $attackPower = intval(ObjectCurrentPower($attacker));
    $attackBasePower = $attackPower; // LAW_086: snapshot pre-modifier power to recompute Grit after a defender-first hit
    // Raid: +N power for this attack only (CR 7.6.7). A unit that has lost all abilities (e.g. SEC_038
    // Condemn while attacking, SEC_054) gets no Raid — value keywords honor suppression here, since the
    // generated GetKeyword_*_Value readers don't gate on SWUKeywordSuppressed/LostAbilities themselves.
    $raidVal = LostAbilities($attacker) ? null : GetKeyword_Raid_Value($attacker);
    if ($raidVal !== null && $raidVal > 0) $attackPower += $raidVal;
    // LOF_206 Battle Droid action: "For this attack, it deals damage equal to its remaining HP
    // instead of its power." Replace power entirely with current remaining HP (measured here, at
    // damage-deal, before the defender's simultaneous counter resolves). Marker is attack-duration.
    if (is_array($attacker->TurnEffects ?? null) && in_array('SWU_HP_AS_DAMAGE', $attacker->TurnEffects)) {
        $attackPower = max(0, intval(ObjectCurrentHP($attacker)) - intval($attacker->Damage ?? 0));
    }
    // SOR_130 First Legion Snowtrooper: while attacking a DAMAGED unit, +2/+0 and
    // gains Overwhelm (both combat-time, depend on the defender's damage at declaration).
    $sor130VsDamaged = ($attacker->CardID === 'SOR_130'
        && $target !== null && empty($target->removed)
        && intval($target->Damage ?? 0) > 0);
    if ($sor130VsDamaged) $attackPower += 2;
    // SHD_138 Jango Fett: while attacking a unit WITH A BOUNTY, +3/+0 and gains Overwhelm (both combat-time,
    // depend on the defender having a Bounty at declaration).
    $shd138VsBounty = ($attacker->CardID === 'SHD_138'
        && $target !== null && empty($target->removed)
        && ObjectHasBounty($target) > 0);
    if ($shd138VsBounty) $attackPower += 3;
    // SHD_007 Moff Gideon — front Action's chosen attacker gets +1/+0 while attacking a unit; the deployed
    // passive gives each friendly ≤3-cost unit +1/+0 AND Overwhelm while attacking an enemy unit.
    $shd007VsUnitTarget = $target !== null && empty($target->removed)
        && strpos((string)$targetMzID, 'Base') === false;
    $shd007Front = $shd007VsUnitTarget && is_array($attacker->TurnEffects ?? null)
        && in_array('SHD_007_FRONT', $attacker->TurnEffects, true);
    $shd007Deployed = $shd007VsUnitTarget
        && intval(CardCost($attacker->CardID ?? '')) <= 3
        && _SWULeaderDeployed(intval($attacker->Controller ?? $player), 'SHD_007');
    if ($shd007Front || $shd007Deployed) $attackPower += 1;
    // ASH_207 Heroic Purrgil — "While attacking using Ambush, this unit gets +2/+0." Marker set when the
    // Ambush entry-trigger attack proceeds; attack-duration.
    if (($attacker->CardID ?? '') === 'ASH_207' && is_array($attacker->TurnEffects ?? null)
        && in_array('SWU_AMBUSH_ATTACK', $attacker->TurnEffects, true)) {
        $attackPower += 2;
    }
    // ASH_241 Marrok's Fiend Fighter (Support) — "+2/+0 while attacking a damaged unit." Own or Support-
    // lent (Overwhelm rides the keyword snapshot separately). Combat-time, depends on defender's damage now.
    if (_SWUAttackerGrants($attacker, 'ASH_241')
        && $target !== null && empty($target->removed) && intval($target->Damage ?? 0) > 0) {
        $attackPower += 2;
    }
    // SEC_033 Sly Moore: "each enemy unit gets -2/-0 while attacking a base this phase." The marker sits
    // on the (enemy) attacker; reduce its power only when the target is a base.
    if (_SWUSlyMooreDebuffs($attacker) && strpos((string)$targetMzID, 'Base') !== false) {
        $attackPower = max(0, $attackPower - 2);
    }
    // ASH_054 Pointless to Resist (upgrade) — "Attached unit gets -3/-0 while attacking a base."
    if (strpos((string)$targetMzID, 'Base') !== false && _SWUUnitHasUpgrade($attacker, 'ASH_054')) {
        $attackPower = max(0, $attackPower - 3);
    }
    // SEC_139 Miraj Scintel: "While a friendly unit is attacking a damaged unit, the attacker gains
    // Overwhelm." Field-passive — any friendly attacker, while its controller controls SEC_139.
    $sec139Overwhelm = false;
    if ($target !== null && empty($target->removed) && intval($target->Damage ?? 0) > 0) {
        foreach (GetUnitsInPlay(intval($attacker->Controller ?? $player)) as $u) {
            if (!empty($u->removed)) continue;
            if (($u->CardID ?? '') === 'SEC_139') { $sec139Overwhelm = true; break; }
        }
    }
    // LOF_090 Inquisitor's Lightsaber: attached unit gains "While attacking a Force unit, +2/+0."
    if (_SWUUnitHasUpgrade($attacker, 'LOF_090') && $target !== null && empty($target->removed)
        && TraitContains($target, 'Force')) $attackPower += 2;
    // SOR_071 Electrostaff: "While attached unit is defending, the attacker gets -1/-0." If the
    // defender (host of this upgrade) is being attacked, reduce the attacker's power by 1.
    if ($target !== null && empty($target->removed) && _SWUUnitHasUpgrade($target, 'SOR_071')) {
        $attackPower = max(0, $attackPower - 1);
    }
    // TWI_072 I Have the High Ground: "Each enemy unit gets -4/-0 while attacking that unit this phase."
    // The marker sits on the protected (defending) unit; reduce the attacker's power by 4 vs it.
    if ($target !== null && empty($target->removed)
        && is_array($target->TurnEffects ?? null) && in_array('TWI_072', $target->TurnEffects, true)) {
        $attackPower = max(0, $attackPower - 4);
    }
    // LAW_108 Lando Calrissian: "While this unit is defending, the attacker gets -1/-0."
    if ($target !== null && empty($target->removed) && ($target->CardID ?? '') === 'LAW_108') {
        $attackPower = max(0, $attackPower - 1);
    }
    // JTL_054 Gold Leader: "While this unit is defending, the attacker gets -1/-0."
    if ($target !== null && empty($target->removed) && ($target->CardID ?? '') === 'JTL_054') {
        $attackPower = max(0, $attackPower - 1);
    }
    // SEC_042 Cassian Andor: "While this unit is defending, the attacker gets -2/-0."
    if ($target !== null && empty($target->removed) && ($target->CardID ?? '') === 'SEC_042') {
        $attackPower = max(0, $attackPower - 2);
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
    $hasShootFirst = _SWUAttackerDealsDamageFirst($attacker);
    // LAW_086 The Stranger: "you may have the defending unit deal combat damage before this unit" — the
    // REVERSE of Shoot First (the attacker chose it via the DEFENDER_FIRST marker). Mutually exclusive
    // with shoot-first; only meaningful in unit combat (handled in the unit branch below).
    $defenderFirst = !$hasShootFirst
        && is_array($attacker->TurnEffects ?? null) && in_array('DEFENDER_FIRST', $attacker->TurnEffects);
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
    // SEC_035 Darth Sion — "When Defeated: if this unit HAD 7+ power, return to hand." His power AT DEFEAT
    // includes any "for this attack" bonus (Surprise Strike +3), but that SWU_ATK_POWER bonus is consumed
    // here (before the defeat/collection point), so stash his buffed attack power now for the When-Defeated
    // snapshot to read. Keyed by UniqueID; cleared after the snapshot consumes it.
    if (($attacker->CardID ?? '') === 'SEC_035') {
        global $gSec035AttackPower;
        if (!isset($gSec035AttackPower) || !is_array($gSec035AttackPower)) $gSec035AttackPower = [];
        $gSec035AttackPower[intval($attacker->UniqueID ?? 0)] = intval($attackPower);
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
    // ASH_018 Grogu (deployed): "While ANOTHER friendly unit is attacking, the defending unit gets -1/-0."
    // (Grogu himself attacking does not count — it must be another friendly unit.)
    if (($attacker->CardID ?? '') !== 'ASH_018' && _SWULeaderDeployed(intval($attacker->Controller ?? $player), 'ASH_018')) $defenderPowerDebuff += 1;
    $defeatedCards = [];
    $pendingCaptiveRescues = [];   // defender's captives: detached at defeat, materialized after the observers
    $pendingCaptiveHost    = null;
    // Combat-hit context (Phase 7.2): captured here, consumed by CollectCombatStep3Triggers to fire
    // WhenDealsCombatDamage / WhenDefeats abilities (Rukh, Mace, Seventh Sister, SOR_088).
    $combatCtx = ['dealtToBase' => false, 'dealtToUnit' => false, 'defenderDefeated' => false,
                  'defenderIsLeader' => false, 'excess' => 0, 'attackerSelfDefeat' => false,
                  'law205SelfDefeat' => false, 'baseCombatDmg' => 0, 'ash184GiveAdv' => false,
                  // Attacker identity captured BEFORE damage, so a "When Attack Ends" ability that must
                  // still fire when its own unit dies (CR 16.c) can be recognised after the object is gone.
                  'attackerCardID' => ($attacker !== null) ? ($attacker->CardID ?? '') : '',
                  // …and its UID. When the attacker dies its arena SLOT is REUSED by whichever unit
                  // reindexes into it, so GetZoneObject($attackerMzID) later returns a DIFFERENT LIVE
                  // unit — not null, and not SWUObjGone. Identity checks after damage must compare UIDs;
                  // an mzID or a bare CardID silently matches the wrong object (and with two copies of
                  // the same card in the arena, a CardID check matches the survivor).
                  'attackerUID' => ($attacker !== null) ? intval($attacker->UniqueID ?? 0) : 0];
    // Capture attack-duration markers NOW (before SWUExpireTurnEffects('attack') strips them later):
    // SOR_150 Heroic Sacrifice's granted "when it deals combat damage: defeat it"; JTL_177 Stay on
    // Target's "deal damage to a base → draw"; JTL_193 I Have You Now's damage prevention on the attacker.
    $preventAttackerDmg = false;
    foreach (($attacker->TurnEffects ?? []) as $te) {
        $teBase = SWUParseTurnEffect((string)$te)['base'];
        if ($teBase === 'SOR_150') $combatCtx['attackerSelfDefeat'] = true;
        if ($teBase === 'LAW_205') $combatCtx['law205SelfDefeat']   = true;
        if ($teBase === 'LAW_062') $combatCtx['law062SelfDefeat']   = true;
        if ($teBase === 'ASH_184') $combatCtx['ash184GiveAdv']      = true;   // Follow Me — post-attack grant
        if ($teBase === 'ASH_137') $combatCtx['ash137Excess']       = true;   // Wipe Them Out — excess to another unit
        if ($teBase === 'ASH_162') $combatCtx['ash162Discard']      = true;   // Rash Action — opp discards on base hit
        if ($teBase === 'JTL_177') $combatCtx['jtl177BaseDraw']    = true;
        if ($teBase === 'JTL_193') $preventAttackerDmg            = true;
        if ($teBase === 'TWI_096') $preventAttackerDmg            = true;   // Aayla Secura — prevent all combat damage to her this attack
        if ($teBase === 'TS26_59') $preventAttackerDmg          = true;   // Brothers — prevent all combat damage to the chosen attacker this attack
    }
    // Support (ASH) — capture the SUPPORT_GRANT marker NOW (before SWUExpireTurnEffects strips it). The
    // attack-END consumers (the When-Attack-Ends graft + ASH_101's combat-hit defeat) run AFTER expiry, so
    // reading the live marker there returns nothing — the lent card's identity must ride combatCtx instead.
    $combatCtx['supportGrant'] = _SWUSupportGrant($attacker);   // ['cardID'=>..,'uid'=>..] or null
    // ASH_183 Whistling Birds (upgrade) — "When Attack Ends: if this unit dealt combat damage to a base, deal
    // 2 to each enemy in this unit's arena." No "and survives" clause, so capture the arena NOW (the upgrade
    // is defeated with the host if it dies) and fire from combatCtx at attack-end regardless of host survival.
    if (_SWUUnitHasUpgrade($attacker, 'ASH_183')) {
        $combatCtx['ash183Arena'] = strpos($attackerMzID, 'SpaceArena') !== false ? 'SpaceArena' : 'GroundArena';
    }

    // "Can't deal combat damage this phase" (LAW_130 Betrayed Trust) — zero the attacker's outgoing
    // combat damage (to a unit OR a base). The same marker on the defender zeroes its counter (below).
    if (is_array($attacker->TurnEffects ?? null) && in_array('NO_COMBAT_DAMAGE', $attacker->TurnEffects, true)) {
        $attackPower = 0;
    }

    $targetZone = explode('-', $targetMzID)[0];
    // Twin Suns (Phase 3): a base target is "theirBase" (2-player) OR "p{n}Base" (a specific opponent's
    // base in N-player). Damage goes to that base's owner — derived from the mzID, not a 2-player ?1:2.
    if (str_ends_with($targetZone, 'Base') && $targetZone !== 'myBase') {
        $GLOBALS['gInCombatDamage'] = true;
        SWUDealDamageToBase($attackPower, SWUMzOwner($targetMzID, $player), $attacker);   // thread attacker for ASH_070 unpreventable check
        $GLOBALS['gInCombatDamage'] = false;
        $combatCtx['dealtToBase'] = ($attackPower > 0);
        // SEC_077 Retaliation — mark a unit that dealt damage to a base this phase (per-unit, cleared at
        // RegroupPhaseStart).
        if ($attackPower > 0) {
            $auid = intval($attacker->UniqueID ?? 0);
            if ($auid > 0) {
                $actrl = intval($attacker->Controller ?? $player);
                AddGlobalEffects($actrl, 'SWU_DEALT_BASEDMG_' . $auid);   // SHD_088/SHD_106 "attacked" (combat-only)
                AddGlobalEffects($actrl, 'SWU_UNITDMGBASE_' . $auid);     // SEC_012 "damaged" (any source)
                // Owner-qualified twin (a direct base attack is always the enemy base).
                AddGlobalEffects($actrl, 'SWU_DMGDBASE_' . $auid . '_' . OtherPlayer($actrl));
            }
        }
        $combatCtx['baseCombatDmg'] += max(0, intval($attackPower)); // LOF_025 threshold (incl. overwhelm below)
        // (Restore now fires once per attack in ExecuteSWUAttack — for unit AND base targets.)
    } elseif ($target !== null && !isset($target->removed)) {
        $defendPower = max(0, intval(ObjectCurrentPower($target)) - $defenderPowerDebuff);
        // LOF_049 Jedi Guardian: "While this unit is defending, it gets +2/+0." (counter-damage only.)
        if (($target->CardID ?? '') === 'LOF_049') $defendPower += 2;
        // SHD_042 Concord Dawn Interceptors: "This unit gets +2/+0 while defending." (counter-damage only.)
        if (($target->CardID ?? '') === 'SHD_042') $defendPower += 2;
        // ASH_073 Palace Chef Droid: "This unit gets +2/+0 while defending." (counter-damage only.)
        if (($target->CardID ?? '') === 'ASH_073') $defendPower += 2;
        // ASH_018 Grogu (deployed): "While ANOTHER friendly unit is defending, it gets +1/+0." (counter-damage only.)
        if (($target->CardID ?? '') !== 'ASH_018' && _SWULeaderDeployed(intval($target->Controller ?? 0), 'ASH_018')) $defendPower += 1;
        // "Can't deal combat damage this phase" (LAW_130) on the defender → it deals no counter-damage.
        if (is_array($target->TurnEffects ?? null) && in_array('NO_COMBAT_DAMAGE', $target->TurnEffects, true)) {
            $defendPower = 0;
        }
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
                SWUQueueShieldBreakAnim($targetAnimMzID, intval($player), $shieldSlot);
                $shieldSlot++;
            }
            unset($sub);
        }

        // SHD_090 Maul — resolve the counter-damage redirect target (if any) for this attack.
        $redirectMz = _SWUCombatRedirectTarget($attacker);

        if ($hasShootFirst) {
            // Shoot First (SOR_217): attacker deals damage before defender.
            // If the defender is defeated, it deals no combat damage (CR card text).
            if (_SWUDamageUnpreventable($attacker)) {   // ASH_196 — bypass Shield + all prevention
                $target->Damage = intval($target->Damage) + $attackPower;
                SWUQueueDamageAnim($targetAnimMzID, $attackPower, intval($player));
                $combatCtx['dealtToUnit'] = ($attackPower > 0);
                $combatCtx['defenderDmgAmt'] = $attackPower;
            } elseif (_SWUConsumeAmidalaPrevent($target)) {
                SWUQueuePreventedAnim($targetAnimMzID, intval($player));   // SEC_101 Queen Amidala prevention
            } elseif (_SWUConsumeAsh062Prevent($target)) {
                SWUQueuePreventedAnim($targetAnimMzID, intval($player));   // ASH_062 The Mandalorian prevention
            } else {
                $tgtDmg = _SWUShieldOrReduceCombat($target, $targetAnimMzID, $attackPower, intval($player));
                $combatCtx['dealtToUnit'] = ($tgtDmg > 0);
                $combatCtx['defenderDmgAmt'] = $tgtDmg;          // SEC_002 "deal that much"
            }
            $defenderRemainingHP = intval(ObjectCurrentHP($target)) - intval($target->Damage);
            if ($defenderRemainingHP > 0) {
                // Defender survived — take counter-damage normally (unless prevented this attack).
                if (_SWUMaybeRedirectAttackerDamage($attackerMzID, $redirectMz, $defendPower, intval($player))) {
                    // SHD_090 Maul — redirected to the chosen friendly unit; Maul takes nothing.
                } elseif ($preventAttackerDmg) {
                    SWUQueuePreventedAnim($attackerMzID, intval($player));
                } elseif (_SWUDamageUnpreventable($target)) {   // ASH_196 — defender's counter bypasses Shield + prevention
                    $attacker->Damage = intval($attacker->Damage) + $defendPower;
                    $combatCtx['attackerTookDmg'] = ($defendPower > 0);
                    $combatCtx['attackerDmgAmt']  = $defendPower;
                    SWUQueueDamageAnim($attackerMzID, $defendPower, intval($player));
                } elseif (_SWUConsumeAmidalaPrevent($attacker)) {
                    SWUQueuePreventedAnim($attackerMzID, intval($player));   // SEC_101 Queen Amidala prevention
                } elseif (_SWUConsumeAsh062Prevent($attacker)) {
                    SWUQueuePreventedAnim($attackerMzID, intval($player));   // ASH_062 The Mandalorian prevention
                } elseif (!SWUConsumeShieldToken($attacker)) {
                    $attacker->Damage = intval($attacker->Damage) + $defendPower;
                    $combatCtx['attackerTookDmg'] = ($defendPower > 0);
                    $combatCtx['attackerDmgAmt']  = $defendPower;   // SEC_002 "deal that much"
                    SWUQueueDamageAnim($attackerMzID, $defendPower, intval($player));
                } else {
                    SWUQueuePreventedAnim($attackerMzID, intval($player));
                    SWUQueueShieldBreakAnim($attackerMzID, intval($player));
                }
            }
            // Defender at 0 HP → attacker takes no counter-damage.
        } elseif ($defenderFirst) {
            // LAW_086 The Stranger: the DEFENDER deals combat damage first (mirror of Shoot First, roles
            // swapped). The attacker then recomputes its power (Grit grows with the damage just taken) and
            // deals — but only if it survived.
            // 1. Defender deals to the attacker first.
            if (_SWUMaybeRedirectAttackerDamage($attackerMzID, $redirectMz, $defendPower, intval($player))) {
                // SHD_090 Maul — redirected to the chosen friendly unit; Maul takes nothing (still deals below).
            } elseif ($preventAttackerDmg) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));
            } elseif (_SWUDamageUnpreventable($target)) {   // ASH_196 — defender's hit bypasses Shield + prevention
                $attacker->Damage = intval($attacker->Damage) + $defendPower;
                $combatCtx['attackerTookDmg'] = ($defendPower > 0);
                $combatCtx['attackerDmgAmt']  = $defendPower;
                SWUQueueDamageAnim($attackerMzID, $defendPower, intval($player));
            } elseif (_SWUConsumeAmidalaPrevent($attacker)) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));
            } elseif (_SWUConsumeAsh062Prevent($attacker)) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));   // ASH_062 The Mandalorian prevention
            } else {
                $atkDmg = _SWUShieldOrReduceCombat($attacker, $attackerMzID, $defendPower, intval($player));
                $combatCtx['attackerTookDmg'] = ($atkDmg > 0);
                $combatCtx['attackerDmgAmt']  = $atkDmg;
            }
            // 2. If the attacker survived, recompute its power (Grit/while-damaged grows with the new
            //    damage) and deal to the defender. A defeated attacker deals no combat damage.
            $attackerRemainingHP = intval(ObjectCurrentHP($attacker)) - intval($attacker->Damage);
            if ($attackerRemainingHP > 0) {
                $attackPower += max(0, intval(ObjectCurrentPower($attacker)) - $attackBasePower); // Grit delta
                if (_SWUDamageUnpreventable($attacker)) {   // ASH_196 — bypass Shield + all prevention
                    $target->Damage = intval($target->Damage) + $attackPower;
                    SWUQueueDamageAnim($targetAnimMzID, $attackPower, intval($player));
                    $combatCtx['dealtToUnit'] = ($attackPower > 0);
                    $combatCtx['defenderDmgAmt'] = $attackPower;
                } elseif (_SWUConsumeAmidalaPrevent($target)) {
                    SWUQueuePreventedAnim($targetAnimMzID, intval($player));
                } elseif (_SWUConsumeAsh062Prevent($target)) {
                    SWUQueuePreventedAnim($targetAnimMzID, intval($player));   // ASH_062 The Mandalorian prevention
                } else {
                    $tgtDmg = _SWUShieldOrReduceCombat($target, $targetAnimMzID, $attackPower, intval($player));
                    $combatCtx['dealtToUnit'] = ($tgtDmg > 0);
                    $combatCtx['defenderDmgAmt'] = $tgtDmg;
                }
            }
        } else {
            // Normal simultaneous combat damage (CR 7.6.3).
            // Shield (CR 7.6.5): attacker's shield absorbs all counter-damage in one hit.
            if (_SWUMaybeRedirectAttackerDamage($attackerMzID, $redirectMz, $defendPower, intval($player))) {
                // SHD_090 Maul — redirected to the chosen friendly unit; Maul takes nothing.
            } elseif ($preventAttackerDmg) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));   // JTL_193: all damage to it prevented
            } elseif (_SWUDamageUnpreventable($target)) {   // ASH_196 — defender's counter bypasses Shield + prevention
                $attacker->Damage = intval($attacker->Damage) + $defendPower;
                $combatCtx['attackerTookDmg'] = ($defendPower > 0);
                $combatCtx['attackerDmgAmt']  = $defendPower;
                SWUQueueDamageAnim($attackerMzID, $defendPower, intval($player));
            } elseif (_SWUConsumeAmidalaPrevent($attacker)) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));   // SEC_101 Queen Amidala prevention
            } elseif (_SWUConsumeAsh062Prevent($attacker)) {
                SWUQueuePreventedAnim($attackerMzID, intval($player));   // ASH_062 The Mandalorian prevention
            } else {
                $atkDmg = _SWUShieldOrReduceCombat($attacker, $attackerMzID, $defendPower, intval($player));
                $combatCtx['attackerTookDmg'] = ($atkDmg > 0);
                $combatCtx['attackerDmgAmt']  = $atkDmg;          // SEC_002 "deal that much"
            }
            // Shielded (CR 7.6.5): a shield token absorbs all combat damage in one hit; consume it instead.
            if (_SWUDamageUnpreventable($attacker)) {   // ASH_196 — bypass Shield + all prevention
                $target->Damage = intval($target->Damage) + $attackPower;
                SWUQueueDamageAnim($targetAnimMzID, $attackPower, intval($player));
                $combatCtx['dealtToUnit'] = ($attackPower > 0);
                $combatCtx['defenderDmgAmt'] = $attackPower;
            } elseif (_SWUConsumeAmidalaPrevent($target)) {
                SWUQueuePreventedAnim($targetAnimMzID, intval($player));   // SEC_101 Queen Amidala prevention
            } elseif (_SWUConsumeAsh062Prevent($target)) {
                SWUQueuePreventedAnim($targetAnimMzID, intval($player));   // ASH_062 The Mandalorian prevention
            } else {
                $tgtDmg = _SWUShieldOrReduceCombat($target, $targetAnimMzID, $attackPower, intval($player));
                $combatCtx['dealtToUnit'] = ($tgtDmg > 0);
                $combatCtx['defenderDmgAmt'] = $tgtDmg;          // SEC_002 "deal that much"
            }
        }

        // Lethality uses CURRENT HP (printed + upgrades + "for this phase" buffs − debuffs), not
        // printed HP — so a +HP buff/upgrade keeps a unit alive in combat. When such a buff expires
        // at RegroupPhaseStart, the now-over-damaged unit is defeated by the sweep there.
        $attackerHP = intval(ObjectCurrentHP($attacker)) - intval($attacker->Damage);
        $defenderHP = intval(ObjectCurrentHP($target))   - intval($target->Damage);
        $combatCtx['defenderDefeated'] = ($defenderHP <= 0 && !SWUImmuneToHpDefeat($target));
        $combatCtx['defendersDefeated'] = $combatCtx['defenderDefeated'] ? 1 : 0;
        if (!empty($combatCtx['defenderDefeated'])) SetSWUVar('SWU_LAST_DEFENDER_DEFEATED', '1'); // ASH_033/036/223 OnAttackEnd condition (own + Support-lent)
        $combatCtx['excess'] = $combatCtx['defenderDefeated'] ? max(0, -$defenderHP) : 0;
        $combatCtx['defenderCardID'] = $target->CardID ?? ''; // for "equal to the defeated unit's cost" (LOF_086)
        $combatCtx['defenderOwner']  = intval($target->Owner ?? 0); // SHD_122 — put the defeated unit into play as a resource under your control

        // Keep $playerID = $player (attacker's perspective) throughout so that
        // mzIDs like "myGroundArena-0" / "theirGroundArena-0" resolve correctly.
        $atkRep = ($attackerHP <= 0 && !SWUImmuneToHpDefeat($attacker)) ? _SWUUnitDefeatReplacement($attacker) : null;
        if ($atkRep !== null) {
            // Defeat-replacement (JTL_049): park the would-be-defeated attacker; resolved at action end.
            $gDeferredReplacements[] = ['uid' => intval($attacker->UniqueID ?? 0),
                'controller' => intval($attacker->Controller ?? $player), 'cardID' => $attacker->CardID, 'kind' => $atkRep['kind']];
        } elseif ($attackerHP <= 0 && !SWUImmuneToHpDefeat($attacker)) {
            $defeatedCards[] = ['player' => intval($attacker->Controller), 'cardID' => $attacker->CardID, 'mzID' => $attackerMzID, 'upgraded' => _SWUIsUpgraded($attacker), 'weakened' => (SWUFindUpgradeIndex($attacker, 'HMW_T02') >= 0)];
            AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_COMBATDEF_' . intval($attacker->UniqueID ?? 0)); // "defeated by combat damage" marker (ASH_028/191)
            $atkOwner = intval($attacker->Owner ?? $player);
            if (strpos(CardType($attacker->CardID) ?? '', 'Leader') !== false) {
                // A deployed leader-unit attacker died in combat and returns to its leader zone.
                $attCtrl = intval($attacker->Controller ?? $player);
                AddGlobalEffects($attCtrl, 'SWU_ATTACKER_DEFEATED');   // SEC_158 — a friendly unit died while attacking
                // SEC_013 Luthen Rael — "When a friendly unit is defeated while attacking." Luthen HIMSELF is
                // that friendly unit; force the deployed-self branch (he has now left the deployed state).
                if (($attacker->CardID ?? '') === 'SEC_013') {
                    AddTrigger($attCtrl, 'SEC_013', 'SEC_013', 'DEPLOYED_SELF');
                }
                // A deployed leader IS a unit — register the same unit-defeat flags as the non-leader branch
                // below (ASH_079 Koska / SOR_051 "a friendly unit was defeated", enemy-defeated from the
                // defender's side, Heroism-defeated) even though it returns to its leader zone. No
                // SWU_DEFEATED_CARD_ — that tracks discard contents (SOR_091), and a leader never discards.
                AddGlobalEffects(SWUMzOwner($targetMzID, $player), 'SWU_ENEMY_DEFEATED');
                AddGlobalEffects($attCtrl, 'SWU_FRIENDLY_DEFEATED');
                _SWUMarkHeroismDefeated($attCtrl, $attacker->CardID ?? '');
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
                    // Unit slides to its OWNER's discard. Tokens CEASE rather than being discarded,
                    // so they get no slide. Perspective is the unit's own controller.
                    if (!_SWUCardCeasesOnLeavePlay($atkObj->CardID ?? '')) {
                        SWUQueueZoneMoveAnim($attackerMzID, 'myDiscard-0', intval($atkObj->Controller ?? $player),
                            420, intval($atkObj->UniqueID ?? 0) ?: null);
                    }
                    $atkObj->removed = true;
                    SWUAddToDiscard($atkOwner, $atkObj->CardID, 'PLAY', $atkHasSecondChance ? 'TPF' : '', $atkObj);
                    AddGlobalEffects($atkOwner, 'SWU_DEFEATED_CARD_' . $atkObj->CardID);
                }
                AddGlobalEffects(SWUMzOwner($targetMzID, $player), 'SWU_ENEMY_DEFEATED'); // the defender (whose enemy — the attacker — died)
                AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_FRIENDLY_DEFEATED');
                _SWUMarkHeroismDefeated(intval($attacker->Controller ?? $player), $attacker->CardID ?? ''); // TWI_017
                // SEC_158 — "a friendly unit was defeated WHILE ATTACKING this phase." This block is the
                // attacker's death in combat, so it is unambiguously "while attacking." Cleared at RGS.
                $attCtrl = intval($attacker->Controller ?? $player);
                AddGlobalEffects($attCtrl, 'SWU_ATTACKER_DEFEATED');
                // SEC_013 Luthen Rael — "When a friendly unit is defeated while attacking: ..." (front side
                // = may exhaust leader → deal 1; deployed = may deal 2). Rides the after-attack flush.
                if (_SWULeaderReadyUndeployed($attCtrl, 'SEC_013') || _SWULeaderDeployed($attCtrl, 'SEC_013')) {
                    AddTrigger($attCtrl, 'SEC_013', 'SEC_013', '');
                }
            }
        }
        $defRep = ($defenderHP <= 0 && $target !== null && empty($target->removed) && !SWUImmuneToHpDefeat($target))
            ? _SWUUnitDefeatReplacement($target) : null;
        if ($defRep !== null) {
            // Defeat-replacement (JTL_049): park the would-be-defeated defender; resolved at action end.
            $gDeferredReplacements[] = ['uid' => intval($target->UniqueID ?? 0),
                'controller' => intval($target->Controller ?? ($player === 1 ? 2 : 1)), 'cardID' => $target->CardID, 'kind' => $defRep['kind']];
        } elseif ($defenderHP <= 0 && $target !== null && empty($target->removed) && !SWUImmuneToHpDefeat($target)) {
            $defeatedCards[] = ['player' => intval($target->Controller), 'cardID' => $target->CardID, 'mzID' => $targetMzID, 'upgraded' => _SWUIsUpgraded($target), 'weakened' => (SWUFindUpgradeIndex($target, 'HMW_T02') >= 0)];
            AddGlobalEffects(intval($target->Controller ?? ($player === 1 ? 2 : 1)), 'SWU_COMBATDEF_' . intval($target->UniqueID ?? 0)); // "defeated by combat damage" marker (ASH_028/191)
            $defOwner = intval($target->Owner ?? ($player === 1 ? 2 : 1));
            if (strpos(CardType($target->CardID) ?? '', 'Leader') !== false) {
                // A deployed leader IS a unit — register the same unit-defeat flags as the non-leader branch
                // (enemy-defeated for the attacker, ASH_079/SOR_051 friendly-defeated for the leader's
                // controller, Heroism-defeated) even though it returns to its leader zone. No
                // SWU_DEFEATED_CARD_ — that tracks discard contents (SOR_091), and a leader never discards.
                AddGlobalEffects($player, 'SWU_ENEMY_DEFEATED');
                AddGlobalEffects(intval($target->Controller ?? GetOpponent($player)), 'SWU_FRIENDLY_DEFEATED');
                _SWUMarkHeroismDefeated(intval($target->Controller ?? GetOpponent($player)), $target->CardID ?? '');
                SWUReturnLeaderToZone($defOwner, $targetMzID);
            } else {
                $defHasSecondChance = _SWUUnitHasUpgrade($target, 'SHD_053');
                $defObj = GetZoneObject($targetMzID);
                if ($defObj) {
                    // CR 8.34.4: rescue any captives guarded by the defender before it leaves play.
                    // DETACH now (must precede SWUDiscardHostSubcards or the captives would be discarded
                    // as upgrades), but MATERIALIZE only after the defeat observers are collected below:
                    // a captive is not in play at the moment of the defeat that frees it, so it must not
                    // satisfy "when an enemy unit is defeated" (LOF_130 HK-47). The effect-defeat path
                    // (SWUDefeatUnit) already collects its triggers before rescuing, so it was correct.
                    $pendingCaptiveRescues = SWUDetachCaptivesOf($defObj);
                    $pendingCaptiveHost    = $defObj;
                    // If the host carries a leader-pilot upgrade, return that leader to zone before discarding.
                    SWUReturnLeaderPilotSubcards($defObj, $defOwner);
                    _SWUDeferPilotDefeatReplacements($defObj); // JTL_094 pilot-upgrade defeat-replacement
                    SWUDiscardHostSubcards($defObj);           // remaining upgrades/pilots → each owner's discard
                    // Unit slides to its OWNER's discard. Tokens CEASE rather than being discarded,
                    // so they get no slide. Perspective is the unit's own controller.
                    if (!_SWUCardCeasesOnLeavePlay($defObj->CardID ?? '')) {
                        SWUQueueZoneMoveAnim($targetMzID, 'myDiscard-0', intval($defObj->Controller ?? $player),
                            420, intval($defObj->UniqueID ?? 0) ?: null);
                    }
                    $defObj->removed = true;
                    SWUAddToDiscard($defOwner, $defObj->CardID, 'PLAY', $defHasSecondChance ? 'TPF' : '', $defObj);
                    AddGlobalEffects($defOwner, 'SWU_DEFEATED_CARD_' . $defObj->CardID);
                }
                AddGlobalEffects($player, 'SWU_ENEMY_DEFEATED');
                AddGlobalEffects(intval($target->Controller ?? GetOpponent($player)), 'SWU_FRIENDLY_DEFEATED');
                _SWUMarkHeroismDefeated(intval($target->Controller ?? GetOpponent($player)), $target->CardID ?? ''); // TWI_017
            }
            // Overwhelm: excess damage (negative $defenderHP) spills to the opponent's base (CR 7.6.4).
            // ASH_150 Deadly Vulnerability — "While attached unit is defending, the attacker loses Overwhelm."
            if ($defenderHP < 0 && (HasKeyword_Overwhelm($attacker) || $sor130VsDamaged || $shd138VsBounty || $sec139Overwhelm || $shd007Deployed)
                && !_SWUUnitHasUpgrade($target, 'ASH_150')) {
                $_logOverwhelm = _SWUOverwhelmSpillToBase($player, $targetMzID, $attacker, -$defenderHP, $combatCtx);
            }
        }
    }

    // SHD_090 Maul — if the counter-damage was redirected and the redirect target is now lethal, defeat it
    // (friendly unit → owner's discard, or return a leader to its zone). Mirrors the defender-defeat path
    // (mark removed + add to $defeatedCards) so its When Defeated batches with the combat triggers below and
    // the deferred CleanupRemovedCards runs once — avoiding a mid-combat index shift on the attacker mzID.
    if (($redirectMz ?? null) !== null) {   // ?? null: $redirectMz is only set on a unit attack (base attacks deal no counter)
        $rObj = GetZoneObject($redirectMz);
        if ($rObj !== null && empty($rObj->removed)
            && (intval(ObjectCurrentHP($rObj)) - intval($rObj->Damage)) <= 0 && !SWUImmuneToHpDefeat($rObj)) {
            $rOwner = intval($rObj->Owner ?? $player);
            $defeatedCards[] = ['player' => intval($rObj->Controller ?? $player), 'cardID' => $rObj->CardID,
                                'mzID' => $redirectMz, 'upgraded' => _SWUIsUpgraded($rObj), 'weakened' => (SWUFindUpgradeIndex($rObj, 'HMW_T02') >= 0)];
            AddGlobalEffects(intval($rObj->Controller ?? $player), 'SWU_COMBATDEF_' . intval($rObj->UniqueID ?? 0));
            if (strpos(CardType($rObj->CardID) ?? '', 'Leader') !== false) {
                SWUReturnLeaderToZone($rOwner, $redirectMz);
            } else {
                SWURescueCaptivesOf($rObj);
                SWUReturnLeaderPilotSubcards($rObj, $rOwner);
                _SWUDeferPilotDefeatReplacements($rObj);
                SWUDiscardHostSubcards($rObj);
                // Unit slides to its OWNER's discard (tokens cease, so no slide).
                if (!_SWUCardCeasesOnLeavePlay($rObj->CardID ?? '')) {
                    SWUQueueZoneMoveAnim($redirectMz, 'myDiscard-0', intval($rObj->Controller ?? $player),
                        420, intval($rObj->UniqueID ?? 0) ?: null);
                }
                $rObj->removed = true;
                SWUAddToDiscard($rOwner, $rObj->CardID, 'PLAY', _SWUUnitHasUpgrade($rObj, 'SHD_053') ? 'TPF' : '', $rObj);
                AddGlobalEffects($rOwner, 'SWU_DEFEATED_CARD_' . $rObj->CardID);
            }
            AddGlobalEffects(intval($rObj->Controller ?? $player), 'SWU_FRIENDLY_DEFEATED');
            _SWUMarkHeroismDefeated(intval($rObj->Controller ?? $player), $rObj->CardID ?? ''); // TWI_017
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
    // Defender's captives now return to play — after the observers above, per the detach/materialize
    // split at the defender-defeat branch.
    if (!empty($pendingCaptiveRescues)) {
        foreach ($pendingCaptiveRescues as $_cap) DoRescueUnit($_cap, $pendingCaptiveHost);
        $pendingCaptiveRescues = [];
    }
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
    // Overwhelm spill is logged AFTER the attack line so the log reads in event order
    // (attack/defeat first, then the excess damage to the base).
    if ($_logOverwhelm > 0) {
        AddGameLogEntry('OVERWHELM', 'Overwhelm: ' . $_logOverwhelm . ' damage to P' . (3 - $_logPlayer) . '\'s base');
    }

    $playerID = $savedPID;
    _SWUCombatFinishAction($player);
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

// Hidden (LOF keyword) — "This unit can't be attacked if it was played this phase." True when $u has
// Hidden AND its current in-play instance was played this phase. "Played this phase" = the
// SWU_PLAYED_UNIT_{uid} flag (set on a real play in ActivateCard, cleared at RegroupPhaseStart). A
// rescued or token-created instance gets a fresh UID with NO flag — so a captured-then-rescued Hidden
// unit (even rescued the same phase) is attackable. Used to exclude it from valid attack/Ambush targets.
function _SWUHiddenBlocksAttack($u): bool {
    if ($u === null || !HasKeyword_Hidden($u)) return false;
    // CR 757b — "If a unit has both Hidden and Sentinel, it can be attacked, as abilities can't prevent
    // units with Sentinel from being attacked." Sentinel overrides Hidden's "can't be attacked".
    if (HasKeyword_Sentinel($u)) return false;
    return GlobalEffectCount(intval($u->Controller ?? 0), 'SWU_PLAYED_UNIT_' . intval($u->UniqueID ?? 0)) > 0;
}

// SEC_012 Cassian Andor (leader front passive) — "Friendly units that have damaged an opponent's base
// this phase can't be attacked (unless they have Sentinel)." Active while the unit's controller has
// SEC_012 as their (undeployed) leader. Uses the per-unit SWU_UNITDMGBASE_{uid} marker — "damaged an
// enemy base by ANY means this phase" (combat direct-attack, Overwhelm excess, When-Played/activated
// pings via SWU_DMG_SRC, indirect); NOT the combat-attack-only SWU_DEALT_BASEDMG (which SHD_088/SHD_106
// use for "attacked your base"). Both cleared at RegroupPhaseStart. Sentinel exception checked at the
// call site (mirrors SOR_142).
// ASH_035 Tatooine Repulsor Train — protected while its controller controls 2+ exhausted units.
function _SWUAsh035Protected($u): bool {
    $ctrl = intval($u->Controller ?? 0);
    if ($ctrl <= 0) return false;
    $exhausted = 0;
    foreach (GetUnitsInPlay($ctrl) as $unit) {
        if (empty($unit->removed) && intval($unit->Status ?? 1) === 0) $exhausted++;
    }
    return $exhausted >= 2;
}

function _SWUSec012Protected($u): bool {
    if ($u === null) return false;
    $ctrl = intval($u->Controller ?? 0);
    if ($ctrl <= 0) return false;
    $hasCassian = false;
    foreach (GetLeader($ctrl) as $l) {
        if ($l !== null && empty($l->removed) && ($l->CardID ?? '') === 'SEC_012' && empty($l->Deployed)) {
            $hasCassian = true; break;
        }
    }
    if (!$hasCassian) return false;
    // BOTH halves of "friendly units that have damaged AN OPPONENT'S base" are relative to the unit's
    // CURRENT controller, so a control change can switch the protection on or off:
    //   • a unit that damaged OUR base and is then stolen BY us is NOT protected (it damaged our base,
    //     which is not an opponent's), and
    //   • a unit that damaged its own controller's base and is then stolen by us IS protected (that base
    //     now belongs to an opponent).
    // The plain SWU_UNITDMGBASE_ marker can answer neither: it is boolean (no record of WHICH base) and
    // it is only stamped for damage to an ENEMY base as judged at the time. Use the owner-qualified twin,
    // and scan EVERY seat — the marker lives on the seat that controlled the unit when the damage was
    // dealt, which is not the current controller after a steal. (Same shape as SEC_042 Retaliation's fix.)
    $uid = intval($u->UniqueID ?? 0);
    if ($uid <= 0) return false;
    for ($seat = 1; $seat <= SeatCountForGame(); $seat++) {
        for ($owner = 1; $owner <= SeatCountForGame(); $owner++) {
            if ($owner === $ctrl) continue;                       // our own base is not "an opponent's"
            if (GlobalEffectCount($seat, 'SWU_DMGDBASE_' . $uid . '_' . $owner) > 0) return true;
        }
    }
    return false;
}

// True if a unit carries the phase-duration "can't be attacked" marker (LOF_211 Dooku, LOF_262).
function _SWUUnitCantBeAttacked($u): bool {
    if ($u === null) return false;
    // CR 11.a / 705d / 867a — Sentinel overrides any "can't be attacked" ability or effect: a unit with
    // Sentinel can always be attacked (LOF_262 Go Into Hiding prints "(unless it has Sentinel)" as a
    // reminder of this general rule; it also applies to LOF_211 and SEC_135).
    if (HasKeyword_Sentinel($u)) return false;
    // SEC_135 Muckraker Crab Droid — "While this unit is ready, it can't be attacked."
    if (($u->CardID ?? '') === 'SEC_135' && intval($u->Status ?? 0) === 1) return true;
    foreach (SWUParsedTurnEffects($u) as $e) { if (($e['base'] ?? '') === 'CANT_BE_ATTACKED') return true; }
    return false;
}

// Twin Suns (Phase 3): $targetSeat pins the enumerated opponent. null → "their<Zone>" (2-player: the one
// opponent; byte-identical). A seat number → "p{seat}<Zone>", so the returned mzIDs (and any cross-arena/
// base mzIDs) name that specific opponent's board. The union across all opponents is built by the wrapper
// SWUGetAllValidAttackTargets, which calls this once per opponent — so per-opponent Sentinel (a Sentinel on
// opponent A doesn't force attacks vs opponent B, CR §11.4.4) and per-opponent base both fall out naturally.
function SWUGetValidAttackTargets(int $opponent, $attackerObj, string $arenaName, bool $noBases = false, ?int $targetSeat = null): array {
    $tp          = $targetSeat === null ? 'their' : "p{$targetSeat}";
    $opArenaZone = "{$tp}{$arenaName}";
    $oppUnits    = [];
    $sentinels   = [];

    $oppArena = GetZone($opArenaZone);
    for ($i = 0; $i < count($oppArena); $i++) {
        $u = $oppArena[$i];
        if (SWUObjGone($u)) continue;
        // SOR_142 Sabine Wren: "While there are at least 3 aspects among other friendly units, this
        // unit can't be attacked (unless she gains Sentinel)." Exclude her as a valid target — but if
        // she has Sentinel, the protection is off (she becomes a forced Sentinel target instead).
        if (($u->CardID ?? '') === 'SOR_142' && !HasKeyword_Sentinel($u) && _SWUSabineProtected($u)) continue;
        // TWI_195 Sabine Wren: "While this unit is exhausted, she can't be attacked (unless she gains
        // Sentinel)." Exclude her while exhausted (Status 0) and without Sentinel.
        if (($u->CardID ?? '') === 'TWI_195' && !HasKeyword_Sentinel($u) && intval($u->Status ?? 1) === 0) continue;
        // Hidden (LOF) — "This unit can't be attacked if it was played this phase." Exclude a Hidden
        // unit whose current in-play instance was played this phase.
        if (_SWUHiddenBlocksAttack($u)) continue;
        if (_SWUUnitCantBeAttacked($u)) continue; // LOF_211/LOF_262 phase-duration "can't be attacked"
        if (_SWUSec012Protected($u) && !HasKeyword_Sentinel($u)) continue; // SEC_012 — damaged a base this phase
        if (($u->CardID ?? '') === 'ASH_035' && !HasKeyword_Sentinel($u) && _SWUAsh035Protected($u)) continue; // ASH_035 — 2+ exhausted units
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
        $groundArena = GetZone("{$tp}GroundArena");
        for ($i = 0; $i < count($groundArena); $i++) {
            $u = $groundArena[$i];
            if (SWUObjGone($u)) continue;
            $oppUnits[] = "{$tp}GroundArena-{$i}";
        }
    }
    // SHD_230 Swoop Down — a granted space unit "can attack ground units for this attack" (SHD_230 marker).
    if ($attackerObj !== null && $arenaName === 'SpaceArena'
        && is_array($attackerObj->TurnEffects ?? null) && in_array('SHD_230', $attackerObj->TurnEffects, true)) {
        $groundArena = GetZone("{$tp}GroundArena");
        for ($i = 0; $i < count($groundArena); $i++) {
            $u = $groundArena[$i];
            if (SWUObjGone($u)) continue;
            $oppUnits[] = "{$tp}GroundArena-{$i}";
        }
    }
    // JTL_259 Retrofitted Airspeeder: "This unit can attack space units." A GROUND unit that also
    // targets enemy SPACE units (cross-arena).
    if ($attackerObj !== null && ($attackerObj->CardID ?? '') === 'JTL_259' && $arenaName === 'GroundArena') {
        $spaceArena = GetZone("{$tp}SpaceArena");
        for ($i = 0; $i < count($spaceArena); $i++) {
            $u = $spaceArena[$i];
            if (SWUObjGone($u)) continue;
            $oppUnits[] = "{$tp}SpaceArena-{$i}";
        }
    }
    // ASH_037 Red Leader (Support) — "This unit may attack units in either arena." Add the OTHER arena's
    // enemy units (own attack OR Support-lent via the SUPPORT_GRANT marker).
    if (_SWUAttackerGrants($attackerObj, 'ASH_037')) {
        $otherZone = $arenaName === 'SpaceArena' ? "{$tp}GroundArena" : "{$tp}SpaceArena";
        $otherArena = GetZone($otherZone);
        for ($i = 0; $i < count($otherArena); $i++) {
            $u = $otherArena[$i];
            if (SWUObjGone($u)) continue;
            $oppUnits[] = "{$otherZone}-{$i}";
        }
    }

    // No Sentinel restriction — include all units, plus the base unless the attack forbids
    // it (SOR_110 Frontline Shuttle "for this attack", or SOR_072 Entrenched on the attacker).
    if ($attackerObj !== null && _SWUUnitHasUpgrade($attackerObj, 'SOR_072')) $noBases = true;
    // ASH_034 Wicket — "This unit can't attack bases."
    if ($attackerObj !== null && ($attackerObj->CardID ?? '') === 'ASH_034') $noBases = true;
    // JTL_092 Scramble Fighters: tokens marked "can't attack bases for this phase".
    if ($attackerObj !== null && is_array($attackerObj->TurnEffects ?? null)
        && in_array('CANT_ATTACK_BASES', $attackerObj->TurnEffects)) $noBases = true;
    // "Can't attack this phase" marker → no valid attacks at all (glow off). The directional
    // CANT_ATTACK_VS variant does the same only while it names this unit's sole opponent.
    if ($attackerObj !== null && is_array($attackerObj->TurnEffects ?? null)
        && in_array('CANT_ATTACK', $attackerObj->TurnEffects, true)) return [];
    if (_SWUDirectionalCantAttackBlocksAll($attackerObj)) return [];
    if (!$noBases) {
        $oppBase = GetZone("{$tp}Base");
        for ($i = 0; $i < count($oppBase); $i++) {
            $b = $oppBase[$i];
            if (SWUObjGone($b)) continue;
            $oppUnits[] = "{$tp}Base-{$i}";
        }
    }
    return $oppUnits;
}

// Twin Suns (Phase 3): all valid attack targets for $attackerCtrl's unit, UNIONED across every live
// opponent. 2-player (or 1 opponent) → the single "their<Zone>" enumeration, byte-identical. N-player →
// one SWUGetValidAttackTargets call per opponent (each pinned via $targetSeat), so the returned mzIDs are
// seat-specific (p{n}<Zone>) and per-opponent Sentinel/base are respected (a Sentinel on one opponent
// doesn't restrict attacks against another). This is the enumeration BeginSWUAttack / the attacker glow use.
function SWUGetAllValidAttackTargets(int $attackerCtrl, $attackerObj, string $arenaName, bool $noBases = false): array {
    if (SeatCountForGame() <= 2) {
        return SWUGetValidAttackTargets(OtherPlayer($attackerCtrl), $attackerObj, $arenaName, $noBases);
    }
    $all = [];
    foreach (OpponentsOf($attackerCtrl) as $opp) {
        foreach (SWUGetValidAttackTargets($opp, $attackerObj, $arenaName, $noBases, $opp) as $t) {
            $all[] = $t;
        }
    }
    return $all;
}

// True if $unit has a HARD "can't attack" restriction (independent of readiness / targets): the printed
// "This unit can't attack" units (JTL_059 Corporate Defense Shuttle, LOF_044) or a CANT_ATTACK turn-effect.
// Used to exclude ineligible attackers from event-granted "attack with a unit" pickers (Outflank etc.),
// matching normal attack-declaration legality — not just at resolution time.
// TS26_31 Chaotic Diversion — "it can't attack YOUR base or units YOU control for this phase". The
// restriction names a PLAYER, not the unit's ability to attack at all, so a plain CANT_ATTACK marker is
// wrong here: once the unit changes controller (SOR_224 Change of Heart) the protected player is no
// longer one of its opponents and it can attack normally again. Returns the seats it may not attack.
function _SWUCantAttackSeats($unit): array {
    if ($unit === null || !is_array($unit->TurnEffects ?? null)) return [];
    $seats = [];
    foreach ($unit->TurnEffects as $te) {
        $p = SWUParseTurnEffect((string)$te);
        if ($p['base'] !== 'CANT_ATTACK_VS') continue;
        $n = intval($p['params'][0] ?? 0);
        if ($n > 0) $seats[$n] = true;
    }
    return array_keys($seats);
}

// True when the directional marker above bars every side this unit could attack — i.e. it names the
// unit's ONLY opponent. With more than one opponent (Twin Suns) some target is still legal, so the unit
// is not hard-blocked and the ordinary target filtering applies.
function _SWUDirectionalCantAttackBlocksAll($unit): bool {
    $seats = _SWUCantAttackSeats($unit);
    if (empty($seats)) return false;
    $ctrl = intval($unit->Controller ?? 0);
    if ($ctrl <= 0) return false;
    $opps = OpponentsOf($ctrl);
    if (count($opps) !== 1) return false;
    return in_array(intval($opps[0]), array_map('intval', $seats), true);
}

function _SWUUnitHardCantAttack($unit): bool {
    if ($unit === null) return false;
    $cid = $unit->CardID ?? '';
    // The printed "This unit can't attack" is the unit's OWN constant ability, so a blank (SEC_054
    // Exiled from the Force) removes it. The CANT_ATTACK turn-effect below is an external restriction,
    // not an ability of the unit, and survives blanking.
    if (($cid === 'JTL_059' || $cid === 'LOF_044') && !LostAbilities($unit)) return true;
    if (is_array($unit->TurnEffects ?? null) && in_array('CANT_ATTACK', $unit->TurnEffects, true)) return true;
    if (_SWUDirectionalCantAttackBlocksAll($unit)) return true;
    return false;
}

// True if $unit (controlled by $player, in $arenaName) could declare an attack RIGHT NOW: ready, allowed
// to attack, and with at least one valid target. Mirrors BeginSWUAttack's hard "can't attack" no-ops so
// the attacker glow matches what a click would actually permit. Caller must have global $playerID = $player
// (SWUGetValidAttackTargets reads "their{arena}" relative to it).
function _SWUUnitCanAttackNow(int $player, $unit, string $arenaName): bool {
    if (SWUObjGone($unit)) return false;
    if (intval($unit->Status ?? 0) !== 1) return false;                        // exhausted
    $cid = $unit->CardID ?? '';
    $blanked = LostAbilities($unit);   // a blanked unit keeps none of these printed restrictions
    if (!$blanked && ($cid === 'JTL_059' || $cid === 'LOF_044')) return false; // "This unit can't attack."
    if (!$blanked && $cid === 'LOF_063' && intval($unit->Damage ?? 0) <= 0) return false;   // Oggdo Bogdo — only while damaged
    return !empty(SWUGetAllValidAttackTargets($player, $unit, $arenaName));
}

// Like SWUGetValidAttackTargets but Ambush-specific: units only, never the base (CR 5.9.a). $targetSeat
// pins a specific opponent (p{seat}<Zone>) for the N-player union; null → "their" (2-player, byte-identical).
function SWUGetValidAmbushTargets(int $opponent, $attackerObj, string $arenaName, ?int $targetSeat = null): array {
    $tp          = $targetSeat === null ? 'their' : "p{$targetSeat}";
    $opArenaZone = "{$tp}{$arenaName}";
    $oppUnits    = [];
    $sentinels   = [];

    $oppArena = GetZone($opArenaZone);
    for ($i = 0; $i < count($oppArena); $i++) {
        $u = $oppArena[$i];
        if (SWUObjGone($u)) continue;
        if (_SWUHiddenBlocksAttack($u)) continue; // Hidden — can't be attacked (incl. Ambush) the phase it was played
        if (_SWUUnitCantBeAttacked($u)) continue; // LOF_211/LOF_262 phase-duration "can't be attacked"
        if (_SWUSec012Protected($u) && !HasKeyword_Sentinel($u)) continue; // SEC_012 — damaged a base this phase
        if (($u->CardID ?? '') === 'ASH_035' && !HasKeyword_Sentinel($u) && _SWUAsh035Protected($u)) continue; // ASH_035 — 2+ exhausted units
        $oppUnits[] = "{$opArenaZone}-{$i}";
        if (HasKeyword_Sentinel($u)) $sentinels[] = "{$opArenaZone}-{$i}";
    }

    $hasSentinelRestriction = !empty($sentinels) && !HasKeyword_Saboteur($attackerObj);
    return $hasSentinelRestriction ? $sentinels : $oppUnits;
}

// Twin Suns (Phase 3): Ambush targets unioned across ALL live opponents (per-opponent Sentinel), mirroring
// SWUGetAllValidAttackTargets. 2-player → the single "their" enumeration, byte-identical.
function SWUGetAllValidAmbushTargets(int $attackerCtrl, $attackerObj, string $arenaName): array {
    if (SeatCountForGame() <= 2) {
        return SWUGetValidAmbushTargets(OtherPlayer($attackerCtrl), $attackerObj, $arenaName);
    }
    $all = [];
    foreach (OpponentsOf($attackerCtrl) as $opp) {
        foreach (SWUGetValidAmbushTargets($opp, $attackerObj, $arenaName, $opp) as $t) $all[] = $t;
    }
    return $all;
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
    // JTL_059 Corporate Defense Shuttle: "This unit can't attack." Hard no-op. Skipped while the unit is
    // blanked — these are its own printed constant abilities (SEC_054 Exiled from the Force).
    if (($attacker->CardID ?? '') === 'JTL_059' && !LostAbilities($attacker)) {
        $playerID = $savedPID;
        return;
    }
    // LOF_063 Oggdo Bogdo: "This unit can't attack unless it's damaged." No-op while undamaged.
    if (($attacker->CardID ?? '') === 'LOF_063' && intval($attacker->Damage ?? 0) <= 0 && !LostAbilities($attacker)) {
        $playerID = $savedPID;
        return;
    }
    // LOF_044 Loth-Wolf: "This unit can't attack." Hard no-op.
    if (($attacker->CardID ?? '') === 'LOF_044' && !LostAbilities($attacker)) {
        $playerID = $savedPID;
        return;
    }
    // "This unit can't attack for this phase" marker (TWI_039 Malevolence). Hard no-op.
    if (is_array($attacker->TurnEffects ?? null) && in_array('CANT_ATTACK', $attacker->TurnEffects, true)) {
        $playerID = $savedPID;
        return;
    }
    // Directional "can't attack <player>'s base or units" (TS26_31 Chaotic Diversion) — a no-op only
    // while that player is the attacker's sole opponent; a control change lifts it.
    if (_SWUDirectionalCantAttackBlocksAll($attacker)) {
        $playerID = $savedPID;
        return;
    }
    // EVENT-INITIATED ATTACK: if a FINISH_PLAY_CARD terminator is pending on the attacking player's queue,
    // this attack was launched by an "Attack with a unit" EVENT (SOR_168 Precision Fire, SOR_150 Heroic
    // Sacrifice, ASH_234 Masterstroke, LAW_202, ASH_162, …) or a Support unit played from hand. This combat
    // owns the after-action, so flag FINISH_PLAY_CARD to skip its duplicate turn pass — otherwise the combat
    // swaps the turn and FINISH_PLAY_CARD swaps it right back (net: the turn never passes = a free extra
    // action). Set only once the attack is committed to proceed (past the can't-attack no-ops above), so a
    // fizzled attack still lets FINISH_PLAY_CARD close the action.
    //
    // ⚠ PERSIST this via an SWUVar ($gDecisionQueueVariables), NOT a transient in-memory global: an
    // interactive target/defender/On-Attack decision mid-attack ends the HTTP request, and the answer
    // arrives in a FRESH PHP process where a transient global is gone — so FINISH_PLAY_CARD would run its
    // own after-action and double-swap the turn. The SWUVar rides the serialized gamestate across the
    // boundary. Compute unconditionally (set OR clear) so a stale flag from a prior action can't leak.
    // (Shoot First SOR_217 / Trust Your Instincts LOF_221 no longer set a flag by hand — this detection
    // is authoritative for every event-initiated attack.)
    $combatOwnsAfterAction = false;
    foreach (GetDecisionQueue(intval($player)) as $d) {
        if ($d !== null && strpos((string)($d->Param ?? ''), 'FINISH_PLAY_CARD') !== false) {
            $combatOwnsAfterAction = true;
            break;
        }
    }
    SetSWUVar('SWU_COMBAT_OWNS_AFTERACTION', $combatOwnsAfterAction ? '1' : '');

    // A SUPPORT bonus attack (the attacker bears a SUPPORT_GRANT marker) is nested inside a deploy/play
    // action whose deferred SWU_TRIGGER_RESUME terminal owns the single After Action — so THIS combat must
    // skip its own. In-process the live _SWUInTriggerResumeMode() scan catches it, but across the request
    // boundary a mid-attack decision creates, the resume may run before combat damage resolves and the scan
    // returns false → a double turn-swap (a free extra action, seen only when initiative isn't yet claimed).
    // Persist the skip decision here (committed attack, past the can't-attack no-ops) so it survives the
    // boundary; _SWUCombatFinishAction consumes it. NOT set for a plain event-attack (no SUPPORT_GRANT) —
    // that path uses SWU_COMBAT_OWNS_AFTERACTION (combat owns, FINISH_PLAY_CARD skips) instead.
    SetSWUVar('SWU_COMBAT_SKIP_AFTERACTION', _SWUSupportGrant($attacker) !== null ? '1' : '');

    $attackedUid = intval($attacker->UniqueID ?? 0);
    AddGlobalEffects($player, 'SWU_ATTACKED_' . $attackedUid);  // any unit attacked this phase (SOR_245)
    if (TraitContains($attacker, 'Mandalorian')) {
        AddGlobalEffects($player, 'SWU_ATTACKED_MANDALORIAN_' . $attackedUid);
    }
    // LAW_112 Boonta Eve Flagbearer — "When a friendly unit attacks: if no other units have attacked
    // this phase (including enemy units), heal 2 from your base." This attack's flag was just set, so
    // exactly one attack flag = first attack. Heal once per Flagbearer the active player controls.
    if (_SWUCountActiveUnitsWithCardID($player, 'LAW_112') > 0) {
        $atkCount = 0;
        foreach ([1, 2] as $ap) {
            foreach (GetGlobalEffects($ap) as $ge) {
                if (preg_match('/^SWU_ATTACKED_\d+$/', (string)($ge->CardID ?? ''))) $atkCount++;
            }
        }
        if ($atkCount <= 1) {
            $flags = _SWUCountActiveUnitsWithCardID($player, 'LAW_112');
            for ($i = 0; $i < $flags; $i++) OnHealBase($player, $player, 2);
        }
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
    // LOF_011 Kit Fisto: "attacked with a Jedi unit this phase."
    if (TraitContains($attacker, 'Jedi')) {
        AddGlobalEffects($player, 'SWU_ATTACKED_JEDI');
    }
    // TWI_134 Asajj Ventress: "attacked with a Separatist unit this phase" (count-based, incl. this attack).
    if (HasTrait($attacker->CardID, 'Separatist')) {
        AddGlobalEffects($player, 'SWU_ATTACKED_SEPARATIST');
    }
    // TS26_07 Asajj Ventress (deployed): "While you've attacked with a token unit this phase, +2/+0."
    if (EffectiveCardType($attacker) === 'Token Unit') {
        AddGlobalEffects($player, 'SWU_ATTACKED_TOKEN');
    }
    // LAW_219 Anakin's Podracer — "While attacking, if no other units have attacked this phase, this
    // unit deals combat damage before the defending unit." Conditional SHOOT_FIRST, set at declaration
    // (before SWUCombatDamage reads it). This attack's flag was just added above, so exactly one attack
    // flag this phase (either player) = this is the first attacker.
    if (($attacker->CardID ?? '') === 'LAW_219') {
        $atk219 = 0;
        foreach ([1, 2] as $ap) {
            foreach (GetGlobalEffects($ap) as $ge) {
                if (preg_match('/^SWU_ATTACKED_\d+$/', (string)($ge->CardID ?? ''))) $atk219++;
            }
        }
        if ($atk219 <= 1) AddTurnEffect($attackerMzID, 'SHOOT_FIRST');
    }

    // Exhaust the attacker (CR 6.3.1). This function never gates on the attacker being ready
    // (the FSM does, upstream), so a granted "attack even if exhausted" attack (SOR_110)
    // works by calling here directly on an already-exhausted unit.
    $attacker->Status = 0;

    $arena        = $attacker->Location; // "GroundArena" or "SpaceArena"
    // Twin Suns (Phase 3): union valid targets across ALL live opponents (2-player → the one opponent).
    $validTargets = SWUGetAllValidAttackTargets($player, $attacker, $arena, $noBases);

    if (empty($validTargets)) {
        // Nothing to attack; undo exhaust
        $attacker->Status = 1;
        $playerID = $savedPID;
        return;
    }

    // The exhaust is committed (past every no-op / no-target bail), so show it NOW. A single-target
    // attack resolves its damage later in THIS same request, and the damage animation blocks the board
    // re-render — without this the attacker stayed upright for the whole animation and only tipped over
    // afterwards, reading as though exhausting were an effect of the attack rather than its cost.
    SWUQueueExhaustAnim($attackerMzID, intval($player), intval($attacker->UniqueID ?? 0) ?: null);

    // TWI_135 Darth Maul — "This unit can attack 2 units instead of 1." UX by number of LEGAL targets
    // (Maul has no Saboteur, so $validTargets is already Sentinel-restricted — a Sentinel present drops the
    // base + non-Sentinels). $legalUnits = the legal UNIT targets; $baseMz = the base if it's legal.
    //   • ≥2 legal units + base legal → OPTIONCHOOSE Base-vs-Units; "Units" → MZMULTICHOOSE (1 or 2).
    //   • ≥2 legal units + base NOT legal (2+ Sentinels) → straight to the MZMULTICHOOSE (1 or 2).
    //   • ≤1 legal unit → fall through to the ordinary single-attack path (1 unit + base = a normal
    //     2-target prompt; a lone Sentinel = auto-resolve).
    if (($attacker->CardID ?? '') === 'TWI_135') {
        $baseMz = null; $legalUnits = [];
        foreach ($validTargets as $t) {
            if (strpos((string)$t, 'Base') !== false) $baseMz = $t;
            else $legalUnits[] = $t;
        }
        if (count($legalUnits) >= 2) {
            if ($baseMz !== null) {
                DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Base&Units", 1,
                    tooltip:"Attack_the_base_or_two_units?");
                DecisionQueueController::AddDecision($player, "CUSTOM", "TWI135_MODE|{$attackerMzID}|{$baseMz}", 1);
            } else {
                $maxPick = min(2, count($legalUnits));
                DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "1|{$maxPick}|" . implode("&", $legalUnits), 1,
                    tooltip:"Choose_1_or_2_units_to_attack");
                DecisionQueueController::AddDecision($player, "CUSTOM", "TWI135_PICK|{$attackerMzID}", 1);
            }
            $playerID = $savedPID;
            (new DecisionQueueController())->ExecuteStaticMethods($player, "-");
            return;
        }
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

// ===== TWI_135 Darth Maul — "This unit can attack 2 units instead of 1." =====
// Target selection reads the legal-target list from SWUGetValidAttackTargets (Sentinel-restricted for a
// no-Saboteur attacker); see the BeginSWUAttack branch. The continuations below drive the choice.

// Base-vs-Units mode pick (only offered when the base is a legal target): "Base" → ordinary single attack
// on the base; "Units" → the unit multi-select (1 or 2 of the legal units, Sentinel-restricted already).
$customDQHandlers["TWI135_MODE"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $attackerMzID = $parts[0] ?? '';
    $baseMz       = $parts[1] ?? '';
    if ($lastDecision === 'Base') {
        $playerID = $savedPID;
        ExecuteSWUAttack($player, $attackerMzID, $baseMz);
        return;
    }
    if ($lastDecision !== 'Units') {   // defensive: unrecognized answer → undo the exhaust
        $a = GetZoneObject($attackerMzID);
        if ($a !== null) $a->Status = 1;
        $playerID = $savedPID;
        return;
    }
    // "Units": re-derive the legal unit targets (board unchanged since the mode prompt) and offer the pick.
    $attacker = GetZoneObject($attackerMzID);
    if (SWUObjGone($attacker)) { $playerID = $savedPID; return; }
    $valid = SWUGetAllValidAttackTargets(intval($player), $attacker, $attacker->Location, true); // noBases; union all opponents
    $legalUnits = array_values(array_filter($valid, fn($t) => strpos((string)$t, 'Base') === false));
    if (empty($legalUnits)) { $playerID = $savedPID; return; }
    if (count($legalUnits) === 1) {   // degenerate (a unit left play) — single attack
        $playerID = $savedPID;
        ExecuteSWUAttack($player, $attackerMzID, $legalUnits[0]);
        return;
    }
    $maxPick = min(2, count($legalUnits));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "1|{$maxPick}|" . implode("&", $legalUnits), 1,
        tooltip:"Choose_1_or_2_units_to_attack");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TWI135_PICK|{$attackerMzID}", 1);
    // Leave $playerID = $player so MZCountChoices validates the relative mzIDs against the right arena.
};

// Resolve the 1-or-2 unit multi-select: 0 → undo exhaust; 1 → single attack; 2 → 2-defender combat.
$customDQHandlers["TWI135_PICK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $attackerMzID = $parts[0] ?? '';
    $picks = array_values(array_filter(explode('&', (string)$lastDecision),
        fn($p) => $p !== '' && $p !== '-' && $p !== 'PASS'));
    if (empty($picks)) {   // chose none (scripted) — undo the exhaust
        $a = GetZoneObject($attackerMzID);
        if ($a !== null) $a->Status = 1;
        $playerID = $savedPID;
        return;
    }
    $playerID = $savedPID;
    if (count($picks) === 1) {
        ExecuteSWUAttack($player, $attackerMzID, $picks[0]);
    } else {
        _SWUMaulBeginDoubleAttack(intval($player), $attackerMzID, $picks[0], $picks[1]);
    }
};

// TWI_135 Darth Maul, two-defender attack — the STEP 1 half, mirroring the tail of ExecuteSWUAttack.
// This path used to jump straight to _SWUMaulDoubleCombat, which meant a 2-unit Maul attack silently
// skipped EVERY combat step-1 trigger: both defenders' On Defense, upgrade-granted On Defense, the
// SEC_101 Queen Amidala / ASH_062 damage-prevention offers, SEC_231's Spy, and the SEC_157 lose-abilities
// marker. Collect them here (attacker-side once, defender-side for each defender) and flush with a
// MAULCOMBAT continuation so damage commits only after they resolve.
function _SWUMaulBeginDoubleAttack(int $player, string $attackerMzID, string $def1Mz, string $def2Mz): void {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);

    $attacker = GetZoneObject($attackerMzID);
    if (SWUObjGone($attacker)) {
        $playerID = $savedPID;
        _SWUCombatFinishAction($player);
        return;
    }

    // "Attacked this phase" bookkeeping + CR 3.3 "while attacking", normally done in ExecuteSWUAttack.
    // Must precede trigger collection so an On Defense reaction reads the same state a normal attack shows.
    $atkUID = intval($attacker->UniqueID ?? 0);
    if ($atkUID > 0) AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_UNIT_ATTACKED_' . $atkUID);
    AddGlobalEffects(intval($attacker->Controller ?? $player), 'SWU_FRIENDLY_ATTACKED');
    SetSWUVar('SWU_CURRENT_ATTACKER', $attackerMzID);
    SetSWUVar('SWU_CURRENT_ATTACKER_UID', strval($atkUID));
    _SWUApplyCondemnSuppression($attacker, $attackerMzID);

    // The attacker-side blocks run on the FULL call only. Their "would counter-damage actually happen?"
    // gate (SEC_101 / ASH_062) reads the defender passed in, so lead with a defender that actually has
    // power — otherwise a 0-power first defender would suppress an offer the second defender earns.
    $d1 = GetZoneObject($def1Mz);
    $leadFirst = !(SWUObjGone($d1) || intval(ObjectCurrentPower($d1)) <= 0);
    [$fullMz, $extraMz] = $leadFirst ? [$def1Mz, $def2Mz] : [$def2Mz, $def1Mz];
    CollectCombatStep1Triggers($player, $attackerMzID, $fullMz);
    CollectCombatStep1Triggers($player, $attackerMzID, $extraMz, defenderOnly: true);

    // FlushCombatTriggerBag's continuation is the single-target "COMBAT|attacker|target|uid"; a two-
    // defender attack needs both defenders carried through, so pass MAULCOMBAT and let SWU_TRIGGER_RESUME
    // dispatch _SWUMaulDoubleCombat instead of SWUCombatDamage.
    // ⚠ Carry all three combatants as UniqueIDs, never raw mzIDs. A step-1 trigger can defeat a unit —
    // SEC_101's prevention defeats a trait-sharing friendly — and the cleanup RE-INDEXES the arena, so a
    // positional "theirGroundArena-2" captured before the pause points at a different unit (or nothing)
    // by the time damage commits. UIDs are stable across that.
    $d2 = GetZoneObject($def2Mz);
    $u1 = ($d1 !== null) ? intval($d1->UniqueID ?? 0) : 0;
    $u2 = ($d2 !== null) ? intval($d2->UniqueID ?? 0) : 0;
    // Per-attack tally of defenders killed by a step-1 trigger, before combat damage (see SWUDefeatUnit).
    SetSWUVar('SWU_MAUL_DEF_UIDS', "{$u1},{$u2}");
    SetSWUVar('SWU_MAUL_DEF_PREKILLED', '0');
    $triggered = FlushCombatTriggerBag($player, $attackerMzID, $def1Mz, "MAULCOMBAT|{$atkUID}|{$u1}|{$u2}");
    if ($triggered === 0) {
        _SWUQueueOrchestration($player, "SWUMaulCombatDamage|{$atkUID}|{$u1}|{$u2}|{$player}", 1);
    }

    $playerID = $savedPID;
}

// Commits a two-defender Maul attack once its step-1 triggers have resolved. Args are UniqueIDs (see the
// re-indexing note above); re-resolve each to a live mzID in the ATTACKER's frame. A combatant that is
// already gone resolves to '' and _SWUMaulDoubleCombat treats it as absent.
$customDQHandlers["SWUMaulCombatDamage"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $active   = intval($parts[3] ?? $player);
    $savedPID = $playerID;
    $playerID = $active;
    $atkMz = SWUFindMzByUID(intval($parts[0] ?? 0)) ?? '';
    $d1Mz  = SWUFindMzByUID(intval($parts[1] ?? 0)) ?? '';
    $d2Mz  = SWUFindMzByUID(intval($parts[2] ?? 0)) ?? '';
    $playerID = $savedPID;
    if ($atkMz === '') { _SWUCombatFinishAction($active); return; }   // attacker died during step 1
    _SWUMaulDoubleCombat($active, $atkMz, $d1Mz, $d2Mz);
};

// Apply one instance of combat damage from $source to $target through the standard prevention/shield
// chain (mirrors the "normal simultaneous" block of SWUCombatDamage). Returns the damage actually dealt.
function _SWUMaulDealCombat($source, $target, string $mzID, int $amount, int $animPlayer): int {
    if ($amount <= 0 || SWUObjGone($target)) return 0;
    if ($source !== null && _SWUDamageUnpreventable($source)) {   // ASH_196 — bypass Shield + all prevention
        $target->Damage = intval($target->Damage) + $amount;
        SWUQueueDamageAnim($mzID, $amount, $animPlayer);
        return $amount;
    }
    if (_SWUConsumeAmidalaPrevent($target)) { SWUQueuePreventedAnim($mzID, $animPlayer); return 0; }
    if (_SWUConsumeAsh062Prevent($target)) { SWUQueuePreventedAnim($mzID, $animPlayer); return 0; }
    return _SWUShieldOrReduceCombat($target, $mzID, $amount, $animPlayer);
}

// Combat defeat of a unit (attacker or a defender) mirroring SWUCombatDamage's defeat blocks:
// discard/leader-return, captive rescue, subcard handling, and the "defeated" global flags. Appends to
// $defeatedCards for the batched When Defeated pass.
function _SWUMaulCombatDefeat($obj, string $mzID, int $player, bool $isAttacker, array &$defeatedCards): void {
    $ctrl  = intval($obj->Controller ?? ($isAttacker ? $player : OtherPlayer($player)));
    $owner = intval($obj->Owner ?? $ctrl);
    $defeatedCards[] = ['player' => $ctrl, 'cardID' => $obj->CardID, 'mzID' => $mzID, 'upgraded' => _SWUIsUpgraded($obj), 'weakened' => (SWUFindUpgradeIndex($obj, 'HMW_T02') >= 0)];
    AddGlobalEffects($ctrl, 'SWU_COMBATDEF_' . intval($obj->UniqueID ?? 0));
    if (strpos(CardType($obj->CardID) ?? '', 'Leader') !== false) {
        SWUReturnLeaderToZone($owner, $mzID);
    } else {
        $hasSecondChance = _SWUUnitHasUpgrade($obj, 'SHD_053');
        SWURescueCaptivesOf($obj);
        SWUReturnLeaderPilotSubcards($obj, $owner);
        _SWUDeferPilotDefeatReplacements($obj);
        SWUDiscardHostSubcards($obj);
        // Unit slides to its OWNER's discard (tokens cease, so no slide).
        if (!_SWUCardCeasesOnLeavePlay($obj->CardID ?? '')) {
            SWUQueueZoneMoveAnim($mzID, 'myDiscard-0', intval($obj->Controller ?? $owner),
                420, intval($obj->UniqueID ?? 0) ?: null);
        }
        $obj->removed = true;
        SWUAddToDiscard($owner, $obj->CardID, 'PLAY', $hasSecondChance ? 'TPF' : '', $obj);
        AddGlobalEffects($owner, 'SWU_DEFEATED_CARD_' . $obj->CardID);
    }
    if ($isAttacker) {
        // The attacker died — an enemy left play from each opponent's view (2-player → the one opponent).
        foreach (OpponentsOf($player) as $enemyObserver) AddGlobalEffects($enemyObserver, 'SWU_ENEMY_DEFEATED');
        AddGlobalEffects($ctrl, 'SWU_FRIENDLY_DEFEATED');
        _SWUMarkHeroismDefeated($ctrl, $obj->CardID ?? ''); // TWI_017
        AddGlobalEffects($ctrl, 'SWU_ATTACKER_DEFEATED');
        // SEC_013 Luthen Rael — see the ExecuteSWUAttack site: force the deployed-self branch when Luthen
        // himself was the dying attacker (already returned to the leader zone by now).
        if (($obj->CardID ?? '') === 'SEC_013') {
            AddTrigger($ctrl, 'SEC_013', 'SEC_013', 'DEPLOYED_SELF');
        } elseif (_SWULeaderReadyUndeployed($ctrl, 'SEC_013') || _SWULeaderDeployed($ctrl, 'SEC_013')) {
            AddTrigger($ctrl, 'SEC_013', 'SEC_013', '');
        }
    } else {
        AddGlobalEffects($player, 'SWU_ENEMY_DEFEATED');
        AddGlobalEffects($ctrl, 'SWU_FRIENDLY_DEFEATED');
        _SWUMarkHeroismDefeated($ctrl, $obj->CardID ?? ''); // TWI_017
    }
}

// The 2-defender simultaneous attack. Maul deals his FULL power to each defender; both defenders deal
// their combat damage back to him as a single simultaneous event (one Shield absorbs it all). All damage
// is snapshotted up front and applied before any defeat, so a defeated defender still contributes its
// counter-damage (CR simultaneity). Pragmatic scope: reuses power/HP, Shield tokens, standard damage
// prevention (Amidala/Mandalorian), defeats, and the batched When Defeated pass; it does NOT run the
// mid-combat On Attack / On Defense pause windows or Overwhelm (Maul has neither).
function _SWUMaulDoubleCombat(int $player, string $attackerMzID, string $def1Mz, string $def2Mz): void {
    global $playerID, $gDeferredReplacements;
    $savedPID = $playerID;
    $playerID = intval($player);

    $attacker = GetZoneObject($attackerMzID);
    if (SWUObjGone($attacker)) {
        $playerID = $savedPID;
        _SWUCombatFinishAction($player);
        return;
    }
    // '' means "already gone" — the UID no longer resolves to a live arena unit (see SWUMaulCombatDamage).
    $def1 = ($def1Mz !== '') ? GetZoneObject($def1Mz) : null;
    $def2 = ($def2Mz !== '') ? GetZoneObject($def2Mz) : null;
    $d1Gone = (SWUObjGone($def1));
    $d2Gone = (SWUObjGone($def2));
    if ($d1Gone && $d2Gone) {
        $playerID = $savedPID;
        _SWUCombatFinishAction($player);
        return;
    }
    // A defender can vanish DURING step-1 trigger resolution — e.g. SEC_101 Queen Amidala is one defender
    // and her controller defeats the other defender (a trait-sharer) to pay for the prevention. Do NOT
    // degrade to ExecuteSWUAttack here: step 1 has already run for this attack, and re-entering it would
    // fire every On Attack / On Defense trigger a second time. The math below already tolerates a missing
    // defender (0 power, 0 damage dealt, skipped in the defeat sweep), so just carry on.

    // The "attacked this phase" bookkeeping and CR 3.3 Condemn suppression happen in
    // _SWUMaulBeginDoubleAttack, before step-1 trigger collection — not here.

    // Snapshot all powers BEFORE any damage (simultaneity).
    $P  = intval(ObjectCurrentPower($attacker));
    $D1 = $d1Gone ? 0 : intval(ObjectCurrentPower($def1));
    $D2 = $d2Gone ? 0 : intval(ObjectCurrentPower($def2));
    // SEC_139 Miraj Scintel — "While a friendly unit is attacking a DAMAGED unit, the attacker gains
    // Overwhelm." In a 2-defender attack the attacker is attacking a damaged unit if EITHER defender was
    // already damaged before this attack; capture that here (pre-combat) for the Overwhelm check below.
    $anyDefPreDamaged = ((!$d1Gone && intval($def1->Damage ?? 0) > 0) || (!$d2Gone && intval($def2->Damage ?? 0) > 0));

    // "The defender gets -X/-0 for this attack" one-shots (LOF_014 Grand Inquisitor SWU_DEF_DEBUFF_N) +
    // deployed passives (SOR_018 Jyn / ASH_018 Grogu) reduce the counter-damage of EACH defender in a
    // multi-defender attack — the single-defender path applies this too. Consume the one-shot marker from
    // the attacker. -X/-0 is power-only, so it lowers each defender's counter (D1/D2), not their HP.
    $defDebuff = 0;
    if (is_array($attacker->TurnEffects ?? null)) {
        $keptDbl = [];
        foreach ($attacker->TurnEffects as $te) {
            if (preg_match('/^SWU_DEF_DEBUFF_(\d+)$/', (string)$te, $dm)) $defDebuff += intval($dm[1]);
            else $keptDbl[] = $te;
        }
        $attacker->TurnEffects = $keptDbl;
    }
    if (_SWULeaderDeployed(intval($attacker->Controller ?? $player), 'SOR_018')) $defDebuff += 1;
    if (($attacker->CardID ?? '') !== 'ASH_018' && _SWULeaderDeployed(intval($attacker->Controller ?? $player), 'ASH_018')) $defDebuff += 1;
    if ($defDebuff > 0) { $D1 = max(0, $D1 - $defDebuff); $D2 = max(0, $D2 - $defDebuff); }
    // "Can't deal combat damage this phase" (LAW_130 Betrayed Trust) — zero the marked unit's outgoing
    // combat damage. On the attacker → 0 to each defender; on a defender → that defender deals no counter.
    // (Mirrors the single-defender path, which the 2-defender path previously skipped.)
    if (is_array($attacker->TurnEffects ?? null) && in_array('NO_COMBAT_DAMAGE', $attacker->TurnEffects, true)) $P = 0;
    if (!$d1Gone && is_array($def1->TurnEffects ?? null) && in_array('NO_COMBAT_DAMAGE', $def1->TurnEffects, true)) $D1 = 0;
    if (!$d2Gone && is_array($def2->TurnEffects ?? null) && in_array('NO_COMBAT_DAMAGE', $def2->TurnEffects, true)) $D2 = 0;

    // Maul → each defender (his full power to each, not split).
    _SWUMaulDealCombat($attacker, $def1, $def1Mz, $P, $player);
    _SWUMaulDealCombat($attacker, $def2, $def2Mz, $P, $player);

    // Both defenders → Maul, combined into one simultaneous event (a single Shield absorbs all of it).
    $counter = $D1 + $D2;
    if ($counter > 0) {
        if ((!$d1Gone && _SWUDamageUnpreventable($def1)) || (!$d2Gone && _SWUDamageUnpreventable($def2))) {
            $attacker->Damage = intval($attacker->Damage) + $counter;
            SWUQueueDamageAnim($attackerMzID, $counter, $player);
        } elseif (_SWUConsumeAmidalaPrevent($attacker)) {
            SWUQueuePreventedAnim($attackerMzID, $player);
        } elseif (_SWUConsumeAsh062Prevent($attacker)) {
            SWUQueuePreventedAnim($attackerMzID, $player);
        } else {
            _SWUShieldOrReduceCombat($attacker, $attackerMzID, $counter, $player);
        }
    }

    // Resolve all defeats simultaneously (damage already applied; a defeat doesn't change another's HP).
    $defeatedCards = [];
    // Defenders already killed by a step-1 trigger count as "attacked and defeated" (ruling 2026-08-07):
    // seed the tally with them, since they are no longer around for the defeat sweep below.
    $defDefeatedCount = intval(GetSWUVar('SWU_MAUL_DEF_PREKILLED', '0'));   // deployed LOF_017 fires per defeat
    $anyDefDefeated = $defDefeatedCount > 0;
    SetSWUVar('SWU_MAUL_DEF_UIDS', '');
    SetSWUVar('SWU_MAUL_DEF_PREKILLED', '0');
    // Overwhelm excess, accumulated PER defending-player (ruling 2024-10-31: COMBINED excess to the
    // defending player's base). Twin Suns: Maul's two units may belong to DIFFERENT opponents, so each
    // owner's excess spills to that owner's own base. 2-player → both share the one opponent → one entry
    // == the old combined value → byte-identical.
    $excessByOwner = [];
    foreach ([[$def1, $def1Mz, false], [$def2, $def2Mz, false], [$attacker, $attackerMzID, true]] as $ent) {
        [$o, $mz, $isAtk] = $ent;
        if (SWUObjGone($o)) continue;
        $hp = intval(ObjectCurrentHP($o)) - intval($o->Damage);
        if ($hp > 0 || SWUImmuneToHpDefeat($o)) continue;
        $rep = _SWUUnitDefeatReplacement($o);
        if ($rep !== null) {
            $gDeferredReplacements[] = ['uid' => intval($o->UniqueID ?? 0),
                'controller' => intval($o->Controller ?? ($isAtk ? $player : OtherPlayer($player))),
                'cardID' => $o->CardID, 'kind' => $rep['kind']];
            continue;
        }
        _SWUMaulCombatDefeat($o, $mz, $player, $isAtk, $defeatedCards);
        if (!$isAtk) {
            $anyDefDefeated = true;
            $defDefeatedCount++;
            if ($hp < 0) {
                $owner = intval($o->Controller ?? SWUMzOwner($mz, $player));
                $excessByOwner[$owner] = ($excessByOwner[$owner] ?? 0) + (-$hp);
            }
        }
    }

    // Overwhelm (official ruling): if Maul has Overwhelm, deal each defending player's combined excess to
    // that player's base. Read the keyword from the attacker even if it was just defeated (simultaneous
    // damage still spills).
    // Effective Overwhelm includes SEC_139 Miraj's conditional grant (attacker's controller has a Miraj in
    // play AND a defender was pre-damaged), which HasKeyword_Overwhelm doesn't cover.
    $sec139Overwhelm = false;
    if ($anyDefPreDamaged) {
        foreach (GetUnitsInPlay(intval($attacker->Controller ?? $player)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'SEC_139') { $sec139Overwhelm = true; break; }
        }
    }
    if (!empty($excessByOwner) && !LostAbilities($attacker) && (HasKeyword_Overwhelm($attacker) || $sec139Overwhelm)) {
        $ovAuid  = intval($attacker->UniqueID ?? 0);
        $ovActrl = intval($attacker->Controller ?? $player);
        foreach ($excessByOwner as $owner => $exc) {
            if ($exc <= 0) continue;
            $GLOBALS['gInCombatDamage'] = true;
            SWUDealDamageToBase($exc, $owner);
            $GLOBALS['gInCombatDamage'] = false;
            // SEC_012 — Overwhelm excess is base damage dealt by the attacker (not a SWU_DMG_SRC ability
            // dispatch, so arm here). Enemy base only. NOT a SWU_DEALT_BASEDMG "attack" (it attacked a unit).
            if ($ovAuid > 0 && intval($owner) !== $ovActrl) AddGlobalEffects($ovActrl, 'SWU_UNITDMGBASE_' . $ovAuid);
            if ($ovAuid > 0) AddGlobalEffects($ovActrl, 'SWU_DMGDBASE_' . $ovAuid . '_' . intval($owner));
        }
    }

    // "For this attack" markers end; then the batched When Defeated + after-attack pass, then cleanup.
    SWUExpireTurnEffects(SWU_DUR_ATTACK);
    $combatCtx = ['dealtToUnit' => true, 'dealtToBase' => false, 'defenderDefeated' => $anyDefDefeated || $defDefeatedCount > 0,
                  'defendersDefeated' => $defDefeatedCount,
                  'defenderIsLeader' => ((!$d1Gone && IsLeaderUnit($def1)) || (!$d2Gone && IsLeaderUnit($def2))), 'excess' => 0];
    CollectCombatStep3Triggers($player, $attackerMzID, $def1Mz, $defeatedCards, $combatCtx);
    DecisionQueueController::CleanupRemovedCards();

    $atkID = $attacker->CardID ?? '';
    if ($atkID !== '') {
        $suffix = '';
        foreach ($defeatedCards as $d) $suffix .= ' — ' . GameLogCardRef($d['cardID']) . ' defeated';
        AddGameLogEntry('ATTACK', 'P' . $player . '\'s ' . GameLogCardRef($atkID) . ' attacked 2 units' . $suffix);
    }

    $playerID = $savedPID;
    _SWUCombatFinishAction($player);
}

// Dispatch the OnAttack ability for the given unit mzID.
function OnAttackTrigger($player, $mzID): void {
    global $onAttackAbilities, $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if ($obj !== null && !empty($obj->CardID)) {
        _SWURecordDamageSource(intval($player), $mzID); // TWI_016 — the attacker is the source of its On Attack ability damage
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

// Upgrade-granted "When this unit is attacked: ..." On Defense dispatch (SEC_052 Diplomatic Immunity).
// $player = the DEFENDER's controller; $hostMzID is the attacked host in the defender's frame. Sets the
// combat-pause flag like OnDefenseTrigger so the reaction (a disclose) resolves before combat damage.
function OnDefenseFromUpgradeTrigger(int $player, string $upgradeCardID, string $hostMzID): void {
    global $onDefenseFromUpgradeAbilities, $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    if (isset($onDefenseFromUpgradeAbilities[$upgradeCardID])) {
        $onDefenseFromUpgradeAbilities[$upgradeCardID]($player, $hostMzID);
        SetSWUVar('SWU_PENDING_DEF_REACTION', '1');
    }
    $playerID = $savedPID;
}

// SEC_101 Queen Amidala — combat-damage prevention dispatch. Queues the "defeat a trait-sharing friendly
// to prevent?" offer for $player (Amidala's controller) and sets the combat-pause flag so it resolves
// before SWUCombatDamage. $mzID is Amidala in $player's frame. On accept, AMIDALA_PREVENT_COMBAT sets a
// one-shot marker that SWUCombatDamage consumes (skipping her damage this attack).
function SEC101PreventTrigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $u = GetZoneObject($mzID);
    if (SWUObjGone($u) || ($u->CardID ?? '') !== 'SEC_101') return;
    $tg = _SWUAmidalaPreventTargets($u);
    if (empty($tg)) return;
    $uid = intval($u->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), 'MZMAYCHOOSE', implode('&', $tg), 1,
        tooltip: 'Defeat_a_trait-sharing_friendly_to_prevent_combat_damage_to_Queen_Amidala?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "AMIDALA_PREVENT_COMBAT|{$uid}", 1);
    SetSWUVar('SWU_PENDING_DEF_REACTION', '1');
}

// ASH_062 The Mandalorian — combat-damage prevention dispatch. $mzID is the PROTECTED unit (the
// attacker/defender about to take damage) in $player's frame. Offer $player (its controller) the chance
// to defeat a Shield on a friendly ASH_062; on accept set the one-shot marker SWUCombatDamage consumes
// (keyed on the PROTECTED unit's UID, so its damage this attack is skipped).
function Ash062PreventTrigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $u = GetZoneObject($mzID);
    if (SWUObjGone($u)) return;
    if (_SWUAsh062Provider($u) === null) return;
    $uid = intval($u->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: 'Defeat_a_Shield_on_The_Mandalorian_to_prevent_combat_damage_to_this_unit?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "ASH062_PREVENT_COMBAT|{$uid}", 1);
    SetSWUVar('SWU_PENDING_DEF_REACTION', '1');
}

// Calculate the total attack power for a combat:
//   base unit power  +  sum of power from all attack cards in the attacker's intent.
function GetTotalAttackPower($attackerObj, $player) {
    $totalPower = ObjectCurrentPower($attackerObj);

    return $totalPower;
}
