<?php
// SWU event immediate effects.
// Called by ActivateCard when an event is played from hand, after the card
// has been moved to discard. Queue any DQ decisions needed to resolve the effect.
// Non-implemented cards fall through to the default no-op.

function OnPlayEvent(int $player, string $cardID): void {
    AddGlobalEffects($player, 'SWU_PLAYED_EVENT'); // TWI_014 Asajj "if you played an event this phase" (cleared at RGS)
    switch ($cardID) {

        // ── TS26 Events ────────────────────────────────────────────────────────
        case 'TS26_069': { // Remove the Chip — "Deal 2 damage to a unit. If it's a Clone, ready it."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Deal_2_damage_to_a_unit", "TS26_069#0");
            return;
        }

        case 'TS26_070': { // Backed by Black Sun — "Deal 1 damage to an enemy unit. You may deal damage
                           // to a unit equal to the number of damaged enemy units."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Deal_1_damage_to_an_enemy_unit", "TS26_070#0");
            return;
        }

        case 'TS26_032': { // Reckless Landing — "Play a unit from your hand. It costs 4 resources less.
                           // Deal 4 damage to it."
            global $playerID; $playerID = intval($player);
            $ready = SWUResourceCount(intval($player), readyOnly: true);
            $units = [];
            foreach (ZoneSearch('myHand') as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
                if (max(0, SWUComputePlayCost(intval($player), $o) - 4) > $ready) continue;
                $units[] = $mz;
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Play_a_unit_(costs_4_less;_deal_4_to_it)", "TS26_032#0");
            return;
        }

        case 'TS26_064': { // Urgent Mission — "Deal 2 damage to your base. Draw 2 cards."
            global $playerID; $playerID = intval($player);
            SWUDealDamageToBase(2, intval($player));
            DoDrawCard(intval($player), 2);
            return;
        }

        case 'TS26_071': { // Take Action — cost reduction via $playCostModifiers["TS26_071"].
                           // "Deal 3 damage to a unit."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Deal_3_damage_to_a_unit", "DEAL_UNIT_DAMAGE|3");
            return;
        }

        case 'TS26_072': { // Fervor — "Ready a unit. Deal 3 damage to a unit."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Ready_a_unit", "TS26_072#0");
            return;
        }

        // ── ASH Events ─────────────────────────────────────────────────────────
        case 'ASH_258': { // Grassroots Resistance — "Deal 3 damage to a unit. Heal 3 damage from your base."
            global $playerID; $playerID = intval($player);
            OnHealBase(intval($player), intval($player), 3);   // heal happens regardless of a damage target
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Deal_3_damage_to_a_unit", "ASH_258#0");
            return;
        }

        case 'ASH_115': { // The Student Guides the Master — "Give a friendly unit +1/+0 for this phase for
                          // each other friendly unit with less power than it."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Choose_a_friendly_unit_to_buff", "ASH_115#0|" . intval($player));
            return;
        }

        case 'ASH_104': { // Dathomiri Magicks — "Play up to 3 non-Vehicle units that each cost 2 or less
                          // from your discard pile for free." (The -1 cost is a $playCostModifier.)
            global $playerID; $playerID = intval($player);
            $tg = [];
            $discard = GetDiscard(intval($player));
            for ($i = 0; $i < count($discard); $i++) {
                $d = $discard[$i];
                if ($d === null || !empty($d->removed)) continue;
                $cid = $d->CardID ?? '';
                if (strpos(CardType($cid) ?? '', 'Unit') === false) continue;   // units only
                if (HasTrait($cid, 'Vehicle')) continue;                          // non-Vehicle
                if (intval(CardCost($cid)) > 2) continue;                         // cost 2 or less
                $tg[] = "myDiscard-{$i}";
            }
            if (empty($tg)) return;
            $max = min(3, count($tg));
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
                tooltip: "Play_up_to_3_non-Vehicle_units_(cost_2_or_less)_from_discard_for_free");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_104#0", 1);
            return;
        }

        case 'ASH_233': { // Keep Them Talking — "Exhaust up to 2 units that each cost 3 or less."
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $tg[] = $mz;
                }
            }
            if (empty($tg)) return;
            $max = min(2, count($tg));
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
                tooltip: "Exhaust_up_to_2_units_that_cost_3_or_less");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_233#0", 1);
            return;
        }

        case 'ASH_234': { // Masterstroke — "Attack with a unit. It gets +1/+0 for this attack for each unit
                          // the defending player controls in its arena."
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_234#0");
            return;
        }

        case 'ASH_103': { // Long Live the Empire — "Defeat a friendly Imperial unit. If you do, resource
                          // the top card of your deck."
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Imperial')) $tg[] = $mz;
            }
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Defeat_a_friendly_Imperial_unit", "ASH_103#0|" . intval($player));
            return;
        }

        case 'ASH_187': { // Reckoning — "Deal damage to a unit equal to the total amount of damage on all
                          // units you control."
            global $playerID; $playerID = intval($player);
            $total = 0;
            foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $total += intval($u->Damage ?? 0); }
            if ($total <= 0) return;   // no damage on your units → nothing to deal
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Deal_{$total}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$total}");
            return;
        }

        case 'ASH_188': { // Galvanized Leap — "Ready a unit that was damaged this phase."
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                        && in_array('SWU_DAMAGED_PHASE', $o->TurnEffects, true)) $tg[] = $mz;
                }
            }
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Ready_a_unit_damaged_this_phase", "READY_UNIT");
            return;
        }

        case 'ASH_186': { // Treacherous Minefield — "Choose an arena. For this phase, each unit in that
                          // arena gains: 'On Attack: deal 2 damage to this unit.'"
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Ground&Space", 1, tooltip: "Choose_an_arena");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_186#0", 1);
            return;
        }

        case 'ASH_139': { // Hold Them Off — "Choose a friendly unit. That unit deals damage equal to its
                          // power divided as you choose among any number of units in its arena."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_deal_its_power", "ASH_139#0|" . intval($player));
            return;
        }

        case 'ASH_151': { // Operation Cinder — "Deal 5 damage to your base. Then, deal 5 damage to each unit."
            global $playerID; $playerID = intval($player);
            SWUDealDamageToBase(5, intval($player));   // 5 to your own base
            // Snapshot every unit's UID, then deal 5 to each (defeats resolve as we go).
            $uids = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
                }
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDealDamageToUnit($mz, 5, intval($player));
            }
            return;
        }

        case 'ASH_136': { // Display of Strength — "Give a unit +3/+3 for this phase."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Give_a_unit_+3/+3_this_phase", "APPLY_PHASE_BUFF|3|3|ASH_136");
            return;
        }

        case 'ASH_137': { // Wipe Them Out — "Attack with a unit. For this attack, you may deal its excess
                          // damage to another unit in the same arena." (The marker is applied in ASH_137#0.)
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_137#0");
            return;
        }

        case 'ASH_138': { // Turning the Tide — "Choose a unit. Deal 1 damage to it for each friendly unit."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Choose_a_unit_(1_damage_per_friendly_unit)", "ASH_138#0|" . intval($player));
            return;
        }

        case 'ASH_140': { // Stronger Together — create 2 Mandalorian tokens.
            global $playerID; $playerID = intval($player);
            SWUCreateUnitTokens(intval($player), 'ASH_T01', 2);
            return;
        }
        case 'ASH_257': { // Choose Your Path — choose one: (Force) heal 5 from your base; OR (Mandalorian)
                          // create a Mandalorian token and give an Advantage token to it.
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Heal&Mandalorian", 1,
                "Choose_one:_heal_5_(Force)_or_create_a_Mandalorian_(Mandalorian)");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_257#0", 1);
            return;
        }
        case 'ASH_092': { // Foundling Rescue — you may defeat a unit with 2 or less remaining HP; create a Mandalorian token.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (_SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 2) $targets[] = $mz;
            }
            if (!empty($targets)) SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_unit_with_2_or_less_remaining_HP?", "Choose_a_unit", "DEFEAT_UNIT");
            SWUCreateUnitToken(intval($player), 'ASH_T01');   // create the Mandalorian regardless
            return;
        }
        case 'ASH_091': { // Buy Time — create a Mandalorian token and give it Sentinel for this phase.
            global $playerID; $playerID = intval($player);
            $uid = SWUCreateUnitToken(intval($player), 'ASH_T01');
            $mz  = SWUFindMzByUID($uid);
            if ($mz !== null) AddTurnEffect($mz, SWUMakeTurnEffect('SENTINEL', [], SWU_DUR_PHASE, 'ASH_091'));
            return;
        }

        case 'ASH_184': { // Follow Me — "Attack with a unit. After completing the attack, give 3 Advantage
                          // tokens to a unit." Choose a ready friendly unit to attack with; the rider is
                          // armed in ASH_184#0 and resolves post-attack (see CollectAfterAttackTriggers).
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;   // no unit can attack → fizzle (the rider is "after the attack")
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_184#0");
            return;
        }

        case 'ASH_090': { // Reforge — "Defeat an upgrade on a friendly unit. If you do, search the top 8 cards
                          // of your deck for an upgrade that can attach to that unit, reveal it, and play it on
                          // that unit. It costs 4 resources less." Friendly-scoped upgrade defeat → ASH_090#0.
            global $playerID; $playerID = intval($player);
            $hosts = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    foreach (GetUpgradesOnUnit($o) as $up) {
                        $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
                        if (!$isTok) { $hosts[] = $mz; break; }
                    }
                }
            }
            if (empty($hosts)) return;   // no friendly upgrade to defeat → fizzle
            DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
            DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_090#0");
            if (count($hosts) === 1) DecisionQueueController::AddDecision(intval($player), "PASSPARAMETER", $hosts[0], 1);
            else DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_an_upgrade_on_a_friendly_unit");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
            return;
        }

        case 'ASH_211': { // Fateful Goodbye — "If a friendly unit left play this phase, distribute 3 Advantage
                          // tokens among friendly units. If a friendly leader unit left play this phase,
                          // distribute 5 instead." Leader takes precedence (a leader leave sets both flags).
            global $playerID; $playerID = intval($player);
            $n = (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEADER_LEFT_PLAY') > 0) ? 5
               : ((GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEFT_PLAY') > 0) ? 3 : 0);
            if ($n <= 0) return;   // nothing left play → no distribute
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueDistributeAdvantage(intval($player), $n, $targets, false, "Distribute_{$n}_Advantage_among_friendly_units");
            return;
        }

        case 'ASH_235': { // Sense Through the Force — "Choose a number, then search the top 5 cards of your deck
                          // for a card, reveal it, and draw it. If its cost is the chosen number, you may give
                          // 3 Advantage tokens to a Force unit." (custom finalize ASH_235#1 carries the number.)
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|12", 1, tooltip: "Choose_a_number");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_235#0", 1);
            return;
        }

        case 'ASH_200': { // Rehabilitation — "Choose a non-leader unit. Give that unit -3/-0 for this phase,
                          // then take control of it. At the start of the regroup phase, its owner takes
                          // control of it." (TEMPORARY_STEAL reverts at RegroupPhaseStart — LOF_189 pattern.)
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $tg[] = $mz;
                }
            }
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Choose_a_non-leader_unit_to_rehabilitate", "ASH_200#0");
            return;
        }

        case 'ASH_232': { // Full of Surprises — "Return an upgrade that costs 2 or less to its owner's hand.
                          // Give a Shield token to a unit." Two independent effects (no "if you do").
            global $playerID; $playerID = intval($player);
            $hosts = [];
            foreach (_SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                foreach (GetUpgradesOnUnit($o) as $up) {
                    $ucid  = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
                    $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
                    if ($ucid !== '' && !$isTok && intval(CardCost($ucid)) <= 2) { $hosts[] = $mz; break; }
                }
            }
            if (!empty($hosts)) {
                SWUQueueChooseTarget(intval($player), $hosts, "Return_an_upgrade_(cost_2_or_less)_to_owner's_hand", "ASH_232#0");
            } else {
                _SWUAsh232GiveShield(intval($player));   // no upgrade to return → straight to the Shield
            }
            return;
        }

        case 'ASH_236': { // Far Far Away — "Return a friendly non-leader unit to its owner's hand. If you do,
                          // return an enemy non-leader unit to its owner's hand."
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $tg[] = $mz;
                }
            }
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Return_a_friendly_non-leader_unit_to_hand", "ASH_236#0");
            return;
        }

        case 'ASH_067': { // Get Lost — "Defeat an upgraded non-leader unit." Mandatory (any side); fizzles
                          // if there is no upgraded non-leader unit.
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && _SWUIsUpgraded($o)) $tg[] = $mz;
                }
            }
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Defeat_an_upgraded_non-leader_unit", "DEFEAT_UNIT");
            return;
        }

        case 'ASH_246': { // Exploit Advantage — "Defeat a friendly upgrade. If you do, draw 2 cards." Scope
                          // the defeat-upgrade host pick to FRIENDLY units (the universal flow spans both
                          // sides), then chain the draw via the DefeatUpgThen continuation.
            global $playerID; $playerID = intval($player);
            $hosts = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
                }
            }
            if (empty($hosts)) return;   // no friendly upgrade → fizzle (no draw)
            DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
            DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_246#0");
            if (count($hosts) === 1) DecisionQueueController::AddDecision(intval($player), "PASSPARAMETER", $hosts[0], 1);
            else DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", implode("&", $hosts), 1, tooltip: "Choose_a_friendly_upgrade_to_defeat");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
            return;
        }

        case 'ASH_247': { // One Must Destroy to Create — "Defeat a friendly non-leader unit. Then, you may
                          // play that unit from your discard pile for free." Choose the unit to defeat first.
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $units[] = $mz;
                }
            }
            if (empty($units)) return;   // no friendly non-leader unit → fizzle
            SWUQueueChooseTarget(intval($player), $units, "Defeat_a_friendly_non-leader_unit", "ASH_247#0");
            return;
        }

        case 'ASH_185': { // Intimidation — "If you control a unit with 4 or more power, draw 2 cards."
            global $playerID; $playerID = intval($player);
            $has4 = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && intval(ObjectCurrentPower($u)) >= 4) { $has4 = true; break; }
            }
            if ($has4) DoDrawCard(intval($player), 2);
            return;
        }

        case 'ASH_162': { // Rash Action — "Attack with a unit. For this attack, it gets +1/+0 and gains:
                          // 'When Attack Ends: if this unit dealt combat damage to an opponent's base, that
                          // opponent discards a card.'" Choose a ready friendly unit; rider armed in ASH_162#0.
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_162#0");
            return;
        }

        case 'ASH_163': { // Reckless Sacrifice — "Discard a unit from your hand. Deal 5 damage to a unit
                          // that costs more than the discarded card." Choose the hand unit to discard first.
            global $playerID; $playerID = intval($player);
            $handUnits = [];
            foreach (ZoneSearch("myHand", ["Unit", "Token Unit"]) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $handUnits[] = $mz;
            }
            if (empty($handUnits)) return;   // no unit to discard → fizzle
            SWUQueueChooseTarget(intval($player), $handUnits, "Discard_a_unit_from_your_hand", "ASH_163#0");
            return;
        }

        case 'ASH_231': { // Diplomatic Pageantry — "Exhaust a friendly unit and an enemy unit. If you do,
                          // give 2 Advantage tokens to that friendly unit." Fizzles unless both exist.
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            $enemy    = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($friendly) || empty($enemy)) return;   // can't exhaust both → fizzle
            SWUQueueChooseTarget(intval($player), $friendly, "Exhaust_a_friendly_unit", "ASH_231#0");
            return;
        }

        case 'ASH_089': { // Perseverance — "Heal 3 damage from a unit and give a Shield token to it."
            global $playerID; $playerID = intval($player);
            $tg = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($tg)) return;
            SWUQueueChooseTarget(intval($player), $tg, "Heal_3_and_Shield_a_unit", "ASH_089#0");
            return;
        }

        case 'ASH_264': { // A New Order — "Give an Advantage token to each of up to 2 units."
            global $playerID; $playerID = intval($player);
            $tg = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) $tg[] = $mz;
            }
            if (empty($tg)) return;
            $max = min(2, count($tg));
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
                tooltip: "Give_an_Advantage_token_to_up_to_2_units");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_264#0", 1);
            return;
        }

        // ── LAW Events ─────────────────────────────────────────────────────────
        case 'LAW_256': { // Fire Across the Galaxy — "Use any number of 'When Played' abilities on friendly
                          // Spectre units." Re-resolve each chosen unit's When-Played (OnWhenPlayed); they
                          // queue FIFO, so each single-decision ability resolves before the next.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    if (_SWUUnitHasTrait($o, 'Spectre') && HasWhenPlayedAbility($o->CardID ?? '')) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;   // no friendly Spectre unit has a When-Played ability → fizzle
            $max = count($targets);
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE",
                "0|{$max}|" . implode('&', $targets), 1,
                tooltip: "Use_any_number_of_friendly_Spectre_When_Played_abilities");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_256#0", 1);
            return;
        }

        case 'LAW_066': { // Tear This Ship Apart — "Look at all of an opponent's resources. You may play
                          // 1 of those cards for free. If you do, that opponent resources the top card of
                          // their deck." The look-at is the theirResources MZMAYCHOOSE (GetNextTurn reveals
                          // those resources to the chooser while it's pending). Offer only PLAYABLE cards
                          // (skip Credit tokens; an upgrade only if a valid host exists).
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $allRes = ZoneSearch("theirResources", null);
            // "Look at ALL of an opponent's resources" — log the (non-Credit) resources looked at, so it's
            // scrollable later. Credit tokens are already public, so they're excluded from the reveal log.
            SWULogResourceReveal(intval($player), array_values(array_filter($allRes,
                fn($mz) => !SWUIsCreditToken(GetZoneObject($mz)->CardID ?? ''))));
            $offer = [];
            foreach ($allRes as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                $cid = $o->CardID;
                if (SWUIsCreditToken($cid)) continue;                 // a Credit token can't be "played"
                if (SWUCardPlayBlocked(intval($player), $cid)) continue; // SOR_062 named-card lock
                if (strpos(CardType($cid), 'Upgrade') !== false
                        && empty(SWUGetUpgradeValidTargets(intval($player), $cid))) continue; // no host
                $offer[] = $mz;
            }
            if (empty($offer)) return; // looked, but nothing playable → fizzle (no refill)
            SWUQueueMayChooseTarget(intval($player), $offer,
                "Play_one_of_the_opponent's_resources_for_free?", "Choose_a_card_to_play",
                "LAW_066#0|{$opp}");
            return;
        }

        case 'LAW_041': { // Nothing Left to Fear — "Choose a friendly unit and give it +2/+2 for this
                          // phase. Then, you may defeat a non-leader unit with power equal to or less
                          // than the chosen unit." The buff is applied first (in the LAW_041#0
                          // continuation), so the power comparison uses the buffed power.
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return;   // no friendly unit to buff → fizzle
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit", "LAW_041#0");
            return;
        }

        case 'LAW_043': { // Shadow Cloaking — "Ready a unit and give a Shield token to it." Any unit is
                          // a legal target (friendly or enemy).
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Ready_a_unit_and_give_it_a_Shield", "LAW_043#0");
            return;
        }

        case 'LAW_044': { // Single Reactor Ignition — "Defeat all units. For each enemy unit defeated
                          // this way, deal 1 damage to its controller's base." Snapshot every unit's
                          // UID + controller, defeat by UID (index-shift safe), then count the enemy
                          // (opponent-controlled) units that actually left play and deal that much to
                          // the opponent's base. Re-checking by UID respects defeat immunity (LAW_149).
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $enemyUids = [];   // opponent-controlled units, to check post-defeat
            $allUids   = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $zone) {
                foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    $uid = intval($o->UniqueID);
                    $allUids[] = $uid;
                    if (intval($o->Controller ?? 0) === $opp) $enemyUids[] = $uid;
                }
            }
            foreach ($allUids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            $defeatedEnemies = 0;
            foreach ($enemyUids as $uid) {
                if (SWUFindMzByUID($uid) === null) $defeatedEnemies++;
            }
            if ($defeatedEnemies > 0) SWUDealDamageToBase($defeatedEnemies, $opp);
            return;
        }

        case 'LAW_085': { // You Hold This — "Choose a friendly non-leader unit. An opponent takes
                          // control of it. If they do, deal 4 damage to another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit",
                "LAW_085#0|" . OtherPlayer(intval($player)));
            return;
        }

        case 'LAW_096': { // Rhydonium Detonation — "Each player may return a non-leader unit to its
                          // owner's hand. Then, defeat all non-leader units." Caster's optional bounce,
                          // then opponent's, then a mass defeat of all remaining non-leader units.
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($targets)) { return; }   // nothing to bounce or defeat
            SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_to_hand?",
                "Choose_a_non-leader_unit_to_return", "LAW_096#0|" . $opp . "|" . intval($player));
            return;
        }

        case 'LAW_102': { // Choke on Aspirations — "Deal up to 5 damage to a friendly non-Vehicle unit.
                          // If it survives, heal damage from your base equal to the damage dealt this way."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_friendly_non-Vehicle_unit", "LAW_102#0");
            return;
        }

        case 'LAW_103': { // Display Piece — "Defeat an enemy non-leader unit. Its controller resources it
                          // from its owner's discard pile." The defeated card is resourced (exhausted) by
                          // its controller — plain "resources it" (contrast SEC_242's explicit "ready").
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Defeat_an_enemy_non-leader_unit",
                "LAW_103#0|" . OtherPlayer(intval($player)));
            return;
        }

        case 'LAW_130': { // Betrayed Trust — "Choose an enemy unit. For this phase, that unit can't deal
                          // combat damage." Tag it with the NO_COMBAT_DAMAGE marker (read in SWUCombatDamage).
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_unit", "LAW_130#0");
            return;
        }

        case 'LAW_131': { // Incapacitate — "Give a unit -2/-2 for this phase." Any unit.
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_-2/-2_for_this_phase", "APPLY_PHASE_DEBUFF|2|2|LAW_131");
            return;
        }

        case 'LAW_132': { // The Tree Remembers — "An enemy unit loses all abilities for this phase. If it
                          // costs 3 or less, defeat it."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_unit", "LAW_132#0");
            return;
        }

        case 'LAW_133': { // Lost and Forgotten — "Defeat a non-leader unit. If you do, heal 3 damage from
                          // your base."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Defeat_a_non-leader_unit", "LAW_133#0");
            return;
        }

        case 'LAW_165': { // Combat Exercise — "Exhaust a friendly unit. If you do, give 2 Experience
                          // tokens to it." Offer ready friendly units (exhaustable).
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_friendly_unit_(give_it_2_Experience)", "LAW_165#0");
            return;
        }

        case 'LAW_166': { // Putting a Team Together — "Search the top 8 cards of your deck for a
                          // Vigilance, Aggression, or Cunning unit, reveal it, and draw it."
            global $playerID; $playerID = intval($player);
            if (count(GetDeck(intval($player))) === 0) return;
            DoTopDeckSearch(intval($player), 8, function($c) {
                if (CardType($c) !== 'Unit') return false;
                $a = CardAspect($c) ?? '';
                return strpos($a, 'Vigilance') !== false || strpos($a, 'Aggression') !== false || strpos($a, 'Cunning') !== false;
            }, 1);
            return;
        }

        case 'LAW_167': { // Common Cause — "Give a unit +1/+1 for this phase for each different aspect
                          // among units you control." Amount computed at resolution (LAW_167#0).
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_+1/+1_per_different_aspect_you_control", "LAW_167#0|" . intval($player));
            return;
        }

        case 'LAW_168': { // Haymaker — "Give an Experience token to a friendly unit. That unit deals
                          // damage equal to its power to an enemy unit in the same arena."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_an_Experience_token", "LAW_168#0");
            return;
        }

        case 'LAW_169': { // Payroll Heist — "For this phase, each friendly unit gains: On Attack: Create
                          // a Credit token." Tag each friendly unit in play now with the LAW_169 marker
                          // (read at attack time in ExecuteSWUAttack). Units entering later aren't marked.
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'LAW_169');
            }
            return;
        }

        case 'SHD_228': { // Bounty Posting — "Search your deck for a Bounty upgrade, reveal it, and draw it.
                          // (Shuffle your deck.) You may play that upgrade (paying its cost)." Full-deck search
                          // (peek all, private) for an Upgrade whose text grants a Bounty; SHD_228#0 draws it,
                          // reshuffles the rest, then offers the may-play-at-cost.
            global $playerID; $playerID = intval($player);
            $deckSize = count(GetDeck(intval($player)));
            if ($deckSize === 0) return;
            _topDeckSearchBegin(intval($player), $deckSize,
                fn($cid) => CardType($cid) === 'Upgrade' && stripos(CardText($cid) ?? '', 'Bounty') !== false,
                "count:1", "SHD_228#0");
            return;
        }

        case 'SHD_109': { // Endless Legions — "Reveal any number of resources you control. Play each unit
                          // revealed this way for free (one at a time)." Iterative reveal-one loop: offer the
                          // player's UNIT resources (MZMAYCHOOSE; non-unit resources aren't offered), free-play
                          // the pick, re-offer; a pass (or no units left) ends the loop.
            global $playerID; $playerID = intval($player);
            _SWUShd109OfferNext(intval($player));
            return;
        }

        case 'SHD_194': { // Triple Dark Raid — "Search the top 7 cards of your deck for a Vehicle and play it.
                          // It costs 5 resources less and enters play ready. Return it to its owner's hand at
                          // the end of the phase." Search (up to 1 Vehicle); SHD_194#0 free-plays via a nested
                          // ActivateCard(discount 5) with the enters-ready + return-at-regroup grants.
            global $playerID;
            $playerID = intval($player);
            $deckSize = count(GetDeck(intval($player)));
            if ($deckSize === 0) return;
            _topDeckSearchBegin(intval($player), min(7, $deckSize),
                fn($cid) => HasTrait($cid, 'Vehicle'), "count:1", "SHD_194#0");
            return;
        }

        case 'SHD_205': { // Let the Wookiee Win — "An opponent chooses one: [You ready up to 6 resources] OR
                          // [You ready a friendly unit. If it's a Wookiee, attack with it. It gets +2/+0]."
            global $playerID;
            $opp = OtherPlayer(intval($player));
            $playerID = $opp;
            DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "Ready6Resources&ReadyUnit", 1,
                "Opponent_chooses:_they_ready_up_to_6_resources_OR_ready_a_friendly_unit");
            DecisionQueueController::AddDecision($opp, "CUSTOM", "SHD_205#0|" . intval($player), 1);
            return;
        }

        case 'SHD_132': { // Choose Sides — "Choose a friendly non-leader unit and an enemy non-leader unit.
                          // Exchange control of those units." (LAW_170 without the Credit-token half.)
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter));
            $enemy    = array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
            if (empty($friendly) || empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit", "SHD_132#0|" . OtherPlayer(intval($player)));
            return;
        }

        case 'SHD_106': { // Rule with Respect — "A friendly unit captures each enemy non-leader unit that
                          // attacked your base this phase." (SWU_DEALT_BASEDMG_{uid} marks base-attackers.)
            global $playerID; $playerID = intval($player);
            $anyAttacker = false;
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    if (GlobalEffectCount(intval($o->Controller ?? 0), 'SWU_DEALT_BASEDMG_' . intval($o->UniqueID ?? 0)) > 0) { $anyAttacker = true; break 2; }
                }
            }
            if (!$anyAttacker) return;
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_capture_the_base_attackers", "SHD_106#0");
            return;
        }

        case 'LAW_170': { // Double-Cross — "Choose a friendly non-leader unit and an enemy non-leader
                          // unit. Exchange control of those units. The player who takes control of the
                          // lower-cost unit creates Credit tokens equal to the difference between costs."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",  NonLeaderUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit", "LAW_170#0|" . OtherPlayer(intval($player)));
            return;
        }

        case 'LAW_171': { // Stockpile — "Resource this event and the top card of your deck." Both enter
                          // the resource zone exhausted (plain "resource", not "ready").
            global $playerID; $playerID = intval($player);
            // The event is already in the caster's discard (moved before OnPlayEvent) — move it to resources.
            $evMz = _SWUFindDiscardMzID(intval($player), 'LAW_171');
            if ($evMz !== null) {
                $r = MZMove(intval($player), $evMz, "myResources");
                if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); }
            }
            // Top card of the deck → resources (exhausted).
            $deck = ZoneSearch("myDeck", null);
            if (!empty($deck)) {
                $r2 = MZMove(intval($player), $deck[0], "myResources");
                if ($r2 !== null) { $r2->Status = 0; $r2->Owner = intval($player); $r2->Controller = intval($player); }
            }
            SWUKeepCreditTokensLast(intval($player));
            return;
        }

        case 'LAW_179': { // Fear and Dead Men — cost reduction handled by $playCostModifiers["LAW_179"].
                          // Effect: "Deal 4 damage to each enemy ground unit." (UID snapshot, AOE.)
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (ZoneSearch("theirGroundArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDealDamageToUnit($mz, 4, intval($player));
            }
            return;
        }

        case 'LAW_202': { // Commence the Festivities — "Attack with a unit. It gains Saboteur for this
                          // attack. If you control fewer resources than an opponent, it gets +2/+0 for
                          // this attack."
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with_(Saboteur)", "LAW_202#0|" . OtherPlayer(intval($player)));
            return;
        }

        case 'LAW_203': { // Daring Delve — "Discard 2 cards from your deck. You may return an Aggression
                          // card discarded this way to your hand."
            global $playerID; $playerID = intval($player);
            $milled = [];
            for ($i = 0; $i < 2; $i++) { $cid = SWUMillTopCard(intval($player)); if ($cid !== null) $milled[] = $cid; }
            // Find discard slots for the Aggression cards milled this way (distinct slots per copy).
            $targets = []; $usedIdx = [];
            $discard = GetDiscard(intval($player));
            foreach ($milled as $cid) {
                if (strpos((string)(CardAspect($cid) ?? ''), 'Aggression') === false) continue;
                for ($j = 0; $j < count($discard); $j++) {
                    if (in_array($j, $usedIdx, true) || !empty($discard[$j]->removed)) continue;
                    if (($discard[$j]->CardID ?? '') === $cid) { $targets[] = "myDiscard-{$j}"; $usedIdx[] = $j; break; }
                }
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets, "Return_an_Aggression_card_to_hand?",
                "Choose_an_Aggression_card_to_return", "LAW_203#0");
            return;
        }

        case 'LAW_204': { // Every Day, More Lies — "Each player discards a card from their hand." (active
                          // player first, then opponent — the SEC_147 each-player-discard pattern).
            global $playerID;
            foreach ([intval($player), OtherPlayer(intval($player))] as $p) {
                $playerID = $p;
                $hand = array_values(ZoneSearch("myHand", null));
                // The just-played event still sits in the CASTER's hand (discarded at block 10); exclude it.
                if ($p === intval($player)) {
                    $excluded = false; $filtered = [];
                    foreach ($hand as $mz) {
                        $o = GetZoneObject($mz);
                        if (!$excluded && $o !== null && ($o->CardID ?? '') === $cardID) { $excluded = true; continue; }
                        $filtered[] = $mz;
                    }
                    $hand = $filtered;
                }
                if (!empty($hand)) SWUQueueChooseTarget($p, $hand, "Discard_a_card_from_your_hand", "DISCARD_FROM_OWN_HAND|" . $p);
            }
            return;
        }

        case 'LAW_205': { // Flash the Vents — "Attack with a unit. It gets +2/+0 and gains Overwhelm for
                          // this attack. After completing this attack, if that unit damaged a base,
                          // defeat that unit."
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with_(+2/+0,_Overwhelm)", "LAW_205#0");
            return;
        }

        case 'LAW_206': { // That's a Rock — "Deal 1 damage to a unit." (The "when discarded from hand or
                          // deck" rider lives in $cardDiscardedHandlers['LAW_206:0'].)
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
            return;
        }

        case 'LAW_207': { // Attack From All Sides — "Deal 3 damage to a unit. If there are 4 or more
                          // different aspects among friendly units, you may deal 5 damage to that unit
                          // instead."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_deal_3_(or_5)", "LAW_207#0|" . intval($player));
            return;
        }

        case 'LAW_208': { // Collateral Damage — "Deal 2 damage to a unit. Then, deal 2 damage to a base
                          // or another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_2_damage_to_a_unit", "LAW_208#0");
            return;
        }

        case 'LAW_217': { // Hold For Questioning — "Exhaust an enemy unit. If you do, look at its
                          // controller's hand and discard a card from it that shares an aspect with that
                          // unit."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "LAW_217#0");
            return;
        }

        case 'LAW_226': { // Secret Battle of Pretend — "Exhaust a friendly unit. If you do, for each
                          // different aspect it has, exhaust an enemy unit in the same arena."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_friendly_unit", "LAW_226#0");
            return;
        }

        case 'LAW_242': { // Improvise — "Look at the top card of your deck. You may play it. It costs 1
                          // resource less. If you don't, you may discard it."
            global $playerID; $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;
            $topObj = GetDeck(intval($player))[$idx];
            $topID  = $topObj->CardID;
            // Only offer "Play" if the player can afford the top card at its −1 discount — otherwise picking
            // Play just fizzles at resolve. Discard / Leave are always available.
            $canPlay = max(0, SWUComputePlayCost(intval($player), $topObj) - 1)
                       <= SWUResourceCount(intval($player), readyOnly: true);
            $opts = "@{$topID}" . ($canPlay ? "&Play" : "") . "&Discard&Leave";
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", $opts, 1, "Play_the_top_card_(costs_1_less),_discard_it,_or_leave_it");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_242#0", 1);
            return;
        }

        case 'LAW_243': { // Transmission Jamming — "Name a card. Cards with that name can't be played
                          // this phase." (phase-duration, applies to BOTH players).
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card_(can't_be_played_this_phase)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_243#0", 1);
            return;
        }

        case 'LAW_245': { // Salvaged Materials — "Play an Item upgrade from your discard pile. It costs 3
                          // resources less. At the start of the next regroup phase, defeat it."
            global $playerID; $playerID = intval($player);
            $ready    = SWUResourceCount(intval($player), readyOnly: true);
            $upgrades = [];
            foreach (ZoneSearch('myDiscard') as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
                if (!HasTrait($o->CardID, 'Item')) continue;
                $hosts = SWUGetUpgradeValidTargets(intval($player), $o->CardID);
                if (empty($hosts)) continue;
                $cost = max(0, SWUComputePlayCost(intval($player), $o, GetZoneObject($hosts[0])) - 3);
                if ($cost <= $ready) $upgrades[] = $mz;
            }
            if (empty($upgrades)) return;
            SWUQueueChooseTarget(intval($player), $upgrades, "Play_an_Item_upgrade_from_your_discard_(costs_3_less)", "LAW_245#0");
            return;
        }

        case 'LAW_246': { // The Axe Forgets — "Return a non-leader unit that costs 3 or less to its
                          // owner's hand."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    if (intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_costing_3_or_less", "BOUNCE_UNIT");
            return;
        }

        case 'LAW_264': { // From a Certain Point of View — "Play a card from your hand, ignoring its
                          // aspect penalties." (Mirrors the LAW common-base play, but waives the FULL
                          // aspect penalty.) CleanupRemovedCards first so the just-played event isn't in
                          // the hand index (the LOF_150 trap), then offer affordable hand cards.
            global $playerID; $playerID = intval($player);
            DecisionQueueController::CleanupRemovedCards();
            $ready   = SWUResourceCount(intval($player), readyOnly: true);
            $targets = [];
            foreach (ZoneSearch("myHand") as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                $cid = $o->CardID;
                if (_SWUCantPlayFromHand($cid)) continue;
                $discount = SWUAspectPenalty(intval($player), $cid);
                $eff      = max(0, SWUComputePlayCost(intval($player), $o) - $discount);
                if ($ready >= $eff) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_card_(ignoring_aspect_penalties)", "LAW_264#0");
            return;
        }

        // ── IBH Events ─────────────────────────────────────────────────────────
        case 'IBH_009':
        case 'IBH_025': { // I've Found Them — Reveal top 3, draw a unit revealed this way, discard the rest.
            _topDeckSearchBegin(intval($player), 3, fn($c) => CardType($c) === 'Unit', "count:1",
                "IBH_TOPDECK_DISCARD_FINALIZE");
            return;
        }

        case 'IBH_074':
        case 'IBH_102': { // I Want Proof, Not Leads — Draw 2 cards, then discard a card from your hand.
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 2);
            // The event being resolved is still physically in hand (it's discarded later at block 10);
            // exclude it from "a card from your hand" — drop the first hand card matching this CardID.
            $hand = ZoneSearch("myHand");
            $excluded = false; $targets = [];
            foreach ($hand as $mz) {
                $o = GetZoneObject($mz);
                if (!$excluded && $o !== null && ($o->CardID ?? '') === $cardID) { $excluded = true; continue; }
                $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Discard_a_card_from_your_hand",
                "DISCARD_FROM_OWN_HAND|" . intval($player));
            return;
        }

        case 'IBH_005':
        case 'IBH_039': { // I'll Cover For You — Deal 1 to an enemy unit and 1 to another enemy unit.
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_1_to_an_enemy_unit", "IBH_005#0");
            return;
        }

        case 'IBH_061':
        case 'IBH_086': { // We're In Trouble — Deal 3 damage to a unit.
            global $playerID; $playerID = intval($player);
            $targets = _SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_3_damage_to_a_unit", "DEAL_UNIT_DAMAGE|3");
            return;
        }

        case 'IBH_013': { // Recovery — Heal 5 damage from a unit.
            global $playerID; $playerID = intval($player);
            $targets = _SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Heal_5_damage_from_a_unit", "HEAL_TARGET|5");
            return;
        }

        case 'SHD_252': { // Smuggler's Aid — heal 3 damage from your base.
            OnHealBase(intval($player), intval($player), 3);
            return;
        }

        case 'SHD_127': { // Commission — search the top 10 of your deck for a Bounty Hunter, Item,
                          // or Transport card; reveal it and draw it.
            global $playerID; $playerID = intval($player);
            if (count(GetDeck($player)) === 0) return;
            DoTopDeckSearch(intval($player), 10,
                fn($c) => HasTrait($c, 'Bounty Hunter') || HasTrait($c, 'Item') || HasTrait($c, 'Transport'), 1);
            return;
        }

        case 'SHD_129': { // Timely Intervention — play a unit from your hand (paying its cost);
                          // it gains Ambush for this phase (SEC_007 Dryden mirror, event form).
            global $playerID; $playerID = intval($player);
            DecisionQueueController::CleanupRemovedCards();   // the event is a removed hand entry
            $ready = SWUResourceCount(intval($player), readyOnly: true);
            $units = [];
            foreach (ZoneSearch('myHand') as $hmz) {
                $u = GetZoneObject($hmz);
                if ($u === null || !empty($u->removed)) continue;
                if (stripos(CardType($u->CardID) ?? '', 'Unit') === false) continue;
                if (SWUComputePlayCost(intval($player), $u) > $ready) continue;
                $units[] = $hmz;
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units,
                "Play_a_unit_from_your_hand_(it_gains_Ambush)", "SHD_129#0");
            return;
        }

        case 'SHD_075': { // Covert Strength — Heal 2 damage from a unit AND give an Experience token
                          // to it (one pick, both effects → SHD_075#0).
            global $playerID; $playerID = intval($player);
            $targets = _SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Heal_2_and_give_an_Experience_token_to_a_unit", "SHD_075#0");
            return;
        }

        case 'IBH_066':
        case 'IBH_091': { // Too Strong for Blasters — Heal 2 damage from a unit.
            global $playerID; $playerID = intval($player);
            $targets = _SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Heal_2_damage_from_a_unit", "HEAL_TARGET|2");
            return;
        }

        case 'IBH_059':
        case 'IBH_071': { // Target the Main Generator — Deal 2 damage to a base.
            global $playerID; $playerID = intval($player);
            SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
            return;
        }

        case 'IBH_018':
        case 'IBH_045': { // Go for the Legs — Exhaust an enemy ground unit.
            global $playerID; $playerID = intval($player);
            $targets = ZoneSearch("theirGroundArena", AnyUnitFilter);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_ground_unit", "EXHAUST_UNIT");
            return;
        }

        case 'IBH_104': { // The Desolation of Hoth — Defeat up to 2 enemy units that each cost 3 or less.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|2|" . implode("&", $targets), 1,
                tooltip:"Defeat_up_to_2_enemy_units_that_cost_3_or_less");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IBH_104#0", 1);
            return;
        }

        case 'IBH_095': { // You Have Failed Me — Defeat a friendly unit. If you do, ready a friendly unit
                          // with 5 or less power.
            global $playerID; $playerID = intval($player);
            $friendlies = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendlies)) return;
            SWUQueueChooseTarget(intval($player), $friendlies, "Defeat_a_friendly_unit", "IBH_095#0");
            return;
        }

        case 'IBH_021':
        case 'IBH_030': { // Improvised Detonation — Attack with a unit. It gets +2/+0 for this attack.
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                    $units[] = "{$zone}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_attack_with", "IBH_021#0");
            return;
        }

        case 'IBH_052': { // Watch This — Return a non-leader unit (cost ≤6) to its owner's hand. Exhaust
                          // each other enemy unit in the same arena.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
                    if (intval(CardCost($o->CardID ?? '')) <= 6) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_(cost_6_or_less)_to_hand", "IBH_052#0");
            return;
        }

        // ── LAW Events ─────────────────────────────────────────────────────────
        case 'LAW_244': { // Unmarked Credits — Create a Credit token.
            SWUCreateCreditToken(intval($player), 1);
            return;
        }

        case 'LAW_248': { // Windfall — Create 3 Credit tokens.
            SWUCreateCreditToken(intval($player), 3);
            return;
        }

        case 'LAW_247': { // Backed by the Hutts — Create a Credit token. You may deal damage to a unit
                          // equal to the number of friendly Credit tokens. (Create FIRST, then count.)
            global $playerID; $playerID = intval($player);
            SWUCreateCreditToken(intval($player), 1);
            $n = SWUCountFriendlyCreditTokens(intval($player));
            if ($n <= 0) return;
            $targets = _SWUAllUnits(); // "a unit" = any unit, both players, all arenas
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Deal_{$n}_to_a_unit?", "Deal_{$n}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$n}");
            return;
        }

        // ── SEC Events ─────────────────────────────────────────────────────────
        case 'SEC_053': { // One in a Million — Defeat a unit with power AND remaining HP both equal to
                          // the number of ready resources you control. (Plot event; can't be played
                          // from hand — see _SWUCantPlayFromHand.) Mandatory defeat; fizzles if no
                          // legal target. "a unit" = any unit, both players, all arenas (incl. leaders).
            global $playerID; $playerID = intval($player);
            $n = SWUResourceCount(intval($player), readyOnly: true);
            $targets = [];
            foreach (_SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval(ObjectCurrentPower($o)) === $n
                    && (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) === $n) $targets[] = $mz;
            }
            if (empty($targets)) return;   // no unit matches → mandatory defeat fizzles
            SWUQueueChooseTarget(intval($player), $targets,
                "Defeat_a_unit_(power_and_remaining_HP_=_your_ready_resources)", "DEFEAT_UNIT");
            return;
        }

        case 'SEC_145': { // Confidence in Victory — Choose an arena. At the start of the regroup phase, if
                          // you are the only player who controls units in that arena, you win the game.
                          // (The "first action only" restriction is enforced in SWUCardPlayBlocked.)
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Ground&Space", 1, tooltip:"Choose_an_arena");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_145#0", 1);
            return;
        }

        case 'SHD_230': { // Swoop Down — "Attack with a space unit. It gains Saboteur and can attack ground
                          // units for this attack. If it attacks a ground unit, it gets +2/+0 and the
                          // defender gets -2/-0 for this attack." Grant the SHD_230 marker (Saboteur +
                          // cross-arena + conditional buff/debuff) to the chosen space unit, then attack.
            global $playerID; $playerID = intval($player);
            $units = array_values(array_filter(ZoneSearch('mySpaceArena', AnyUnitFilter),
                fn($mz) => ($o = GetZoneObject($mz)) !== null && empty($o->removed) && intval($o->Status) === 1));
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, 'Attack_with_a_space_unit', 'SHD_230#0');
            return;
        }

        case 'SHD_145': { // Headhunting — "Attack with up to 3 units (one at a time). They can't attack
                          // bases for these attacks. Each Bounty Hunter that attacks this way gets +2/+0
                          // for its attack." Count-capped attack loop (SWU_SHD145_LOOP); see _SWUShd145Offer.
            global $playerID; $playerID = intval($player);
            SetSWUVar('SWU_SHD145_LOOP', '3');   // up to 3 attacks, no attackers excluded yet (comma-CSV)
            _SWUShd145Offer(intval($player));
            return;
        }

        case 'SHD_144': { // Give In to Your Anger — "Deal 1 damage to an enemy unit. Its controller's next
                          // action this phase must be an attack action with that unit, if able. It must
                          // attack a unit, if able." Choose the enemy unit → SHD_144#0 deals the damage and
                          // arms SWU_SHD144_FORCE|{uid} on that unit's controller; _SWUCheckForcedAttack
                          // (run after the turn passes to that controller) forces the attack.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_an_enemy_unit", "SHD_144#0");
            return;
        }

        case 'SHD_208': { // Final Showdown — "Ready each unit you control. At the start of the regroup
                          // phase, you lose the game." Ready every friendly unit now, then arm the
                          // SWU_SHD208_LOSE flag; _SWUCheckFinalShowdownLose (RegroupPhaseStart, before
                          // the draw step) declares the OTHER player the winner. Mirrors SEC_145's
                          // regroup-start win check, inverted to a loss.
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                OnReadyCard(intval($player), $mz);
            }
            AddGlobalEffects(intval($player), 'SWU_SHD208_LOSE');
            return;
        }

        case 'SEC_073': { // The Eye of Aldhani — At the start of the NEXT action phase, for each enemy
                          // unit, its controller must pay 1 resource or exhaust it. Arm a delayed flag
                          // (survives the regroup, like SOR_017); resolved in ActionPhaseStart.
            AddGlobalEffects(intval($player), 'SWU_EYE_ALDHANI');
            return;
        }

        case 'SEC_195': { // Arrest — Your base captures an enemy non-leader unit. At the start of the
                          // regroup phase, its owner rescues it. (Base captive store + RegroupPhaseStart rescue.)
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Your_base_captures_an_enemy_non-leader_unit", "SEC_195#0");
            return;
        }

        case 'SEC_194': { // Fully Armed and Operational — If an opponent attacked your base during their
                          // previous action this phase, play a unit from your hand. Give it Ambush this phase.
            global $playerID; $playerID = intval($player);
            $opp  = OtherPlayer(intval($player));
            $last = explode(',', GetSWUVar('SWU_LAST_ACTION', ''));        // comma-delimited (see SWUAfterAction)
            $cond = (count($last) >= 3 && intval($last[0]) === $opp
                     && $last[1] === 'BASEATK' && intval($last[2]) === intval($player));
            if (!$cond) return;                                            // opponent's previous action wasn't a base attack
            $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0);
            if (empty($targets)) return;                                   // nothing affordable to play
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_(it_gains_Ambush)", "SEC_194#0");
            return;
        }

        case 'SEC_245': { // When Has Become Now — play a card with Plot from your resources (paying its
                          // cost), then put the top card of your deck into play as a (ready) resource.
                          // Does NOT trigger the Plot keyword's deploy "replace with top of deck" (that's
                          // the leader-deploy ability); SEC_245's own ramp clause is the refill. "You may
                          // play" (Plot is optional) → MZMAYCHOOSE; the ramp happens regardless.
            global $playerID, $Plot_Cards; $playerID = intval($player);
            $ready = SWUResourceCount(intval($player), readyOnly: true);
            $resources = &GetResources(intval($player));
            $targets = [];
            $pos = 0;
            for ($i = 0; $i < count($resources); $i++) {
                if (!empty($resources[$i]->removed)) continue;
                $here = $pos; $pos++;
                $cid = $resources[$i]->CardID ?? '';
                if (!isset($Plot_Cards[$cid])) continue;
                if (SWUCardPlayBlocked(intval($player), $cid)) continue;
                if (SWUComputePlayCost(intval($player), $resources[$i]) > $ready) continue;
                $targets[] = "myResources-{$here}";
            }
            if (empty($targets)) { _SWUSec245Ramp(intval($player)); return; }  // no playable Plot → just ramp
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Play_a_Plot_card_from_your_resources?", "Choose_a_Plot_card_to_play", "SEC_245#0");
            return;
        }

        case 'SEC_232': { // Kreia's Whispers — Draw 3, then put a card from hand on TOP of your deck and
                          // another on the BOTTOM of your deck.
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 3);
            $hand = array_values(ZoneSearch("myHand", null));
            if (empty($hand)) return;
            SWUQueueChooseTarget(intval($player), $hand, "Put_a_card_on_TOP_of_your_deck", "SEC_232#0");
            return;
        }

        case 'SEC_125': { // Reconnaissance — if you control a ground unit AND a space unit, draw 2 cards.
            global $playerID; $playerID = intval($player);
            $hasG = count(ZoneSearch("myGroundArena", AnyUnitFilter)) > 0;
            $hasS = count(ZoneSearch("mySpaceArena", AnyUnitFilter)) > 0;
            if ($hasG && $hasS) DoDrawCard(intval($player), 2);
            return;
        }

        case 'SEC_158': { // Oppression Breeds Rebellion — if a friendly unit was defeated WHILE ATTACKING
                          // this phase, draw 3 cards.
            global $playerID; $playerID = intval($player);
            if (GlobalEffectCount(intval($player), 'SWU_ATTACKER_DEFEATED') > 0) DoDrawCard(intval($player), 3);
            return;
        }

        case 'SEC_130': { // Ferrix Uprising — Deal damage to a unit equal to twice the number of units
                          // you control in its arena.
            global $playerID; $playerID = intval($player);
            $targets = array_values(_SWUAllUnits());
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_damage_=_2x_your_units_in_its_arena", "SEC_130#0");
            return;
        }

        case 'SEC_257': { // Restore Freedom — Play a unit from your hand. It costs 1 resource less for
                          // each Heroism aspect icon among friendly units.
            global $playerID; $playerID = intval($player);
            $disc = 0;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (!empty($u->removed)) continue;
                foreach (SWUCardAspectIcons($u->CardID ?? '') as $a) { if ($a === 'Heroism') $disc++; }
            }
            $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], $disc);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_(1_less_per_Heroism_icon_among_friendly_units)", "DISCOUNT_PLAY_FROM_HAND|" . $disc);
            return;
        }

        case 'SEC_233': { // Beguile — Look at an opponent's hand; choose a non-leader unit that opponent
                          // controls that costs 6 or less and return it to its owner's hand.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && intval(CardCost($o->CardID ?? '')) <= 6) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_an_enemy_non-leader_unit_(cost_6_or_less)_to_hand", "BOUNCE_UNIT");
            return;
        }

        case 'SEC_235': { // The Wrong Ride — Exhaust 2 enemy resources.
            global $playerID; $playerID = intval($player);
            SWUExhaustResources(OtherPlayer(intval($player)), 2);
            return;
        }

        case 'SEC_196': { // No One Ever Knew — For each friendly Official unit, exhaust an enemy unit.
            global $playerID; $playerID = intval($player);
            $n = 0;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && HasTrait($u->CardID ?? '', 'Official')) $n++;
            }
            for ($i = 0; $i < $n; $i++) {
                $enemies = array_values(array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)));
                if (empty($enemies)) break;
                SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
            }
            return;
        }

        case 'SEC_072': { // Scour the Archives — search the top 8 of your deck for an upgrade, reveal+draw.
            DoTopDeckSearch(intval($player), 8, fn($c) => stripos(CardType($c) ?? '', 'Upgrade') !== false, 1);
            return;
        }

        case 'SEC_075': { // Knowledge and Defense — Give a unit -2/-2 for this phase. Draw a card.
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 1);                 // "Draw a card" is unconditional
            $targets = array_values(_SWUAllUnits());
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-2/-2_for_this_phase", "APPLY_PHASE_DEBUFF|2|2|");
            return;
        }

        case 'SEC_231': { // Implicate — Choose a unit. For this phase it gains Sentinel and "When this
                          // unit is attacked: create a Spy token." (Any unit, friendly or enemy.)
            global $playerID; $playerID = intval($player);
            $units = array_values(_SWUAllUnits());
            if (empty($units)) return;
            SWUQueueChooseTarget($player, $units, "Choose_a_unit_to_gain_Sentinel_and_Spy-on-defense", "SEC_231#0");
            return;
        }

        case 'SEC_157': { // One Way Out — Attack with a unit. It gets +1/+0 and gains Overwhelm for this
                          // attack. If it attacks a unit, the defender loses all abilities for this attack.
            global $playerID; $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget($player, $ready, "Choose_a_unit_to_attack_with", "SEC_157#0");
            return;
        }

        case 'SEC_229': { // Catch Unawares — Attack with a unit. The defender gets -4/-0 for this attack.
            global $playerID; $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget($player, $ready, "Choose_a_unit_to_attack_with_(defender_-4/-0)", "SEC_229#0");
            return;
        }

        case 'SEC_228': { // Clever Gambit — Exhaust a friendly unit. If you do, attack with another unit.
                          // It gets +3/+0 for this attack. Need ≥2 ready units (one to exhaust + attacker).
            global $playerID; $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (count($ready) < 2) return;
            SWUQueueChooseTarget($player, $ready, "Exhaust_a_friendly_unit_(then_attack_with_another)", "SEC_228#0");
            return;
        }

        case 'SEC_179': { // Aggressive Negotiations — Attack with a unit. For this attack, it gets +1/+0
                          // for each card in your hand.
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SEC_179#0");
            return;
        }

        case 'SEC_126': { // Trade Route Taxation — choose an opponent; if you control more units than
                          // that opponent, they can't play events for this phase. (2-player: lone opp.)
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $mine = 0; foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $mine++; }
            $theirs = 0; foreach (GetUnitsInPlay($opp) as $u) { if (empty($u->removed)) $theirs++; }
            if ($mine > $theirs) AddGlobalEffects($opp, 'SWU_EVENT_LOCK');
            return;
        }

        case 'SEC_091': { // Corporate Warmongering — "Give a friendly unit +3/+3 for this phase. Give
                          // each other friendly unit +1/+1 for this phase." Pick the +3/+3 recipient.
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_+3/+3_(others_+1/+1)", "SEC_091#0");
            return;
        }

        case 'SEC_074': { // Relief Request — "Heal 3 damage from a unit. You may disclose Vigilance →
                          // heal 3 damage from another unit." First heal (mandatory) over damaged units,
                          // then the optional disclose → a second heal on a DIFFERENT damaged unit.
            global $playerID; $playerID = intval($player);
            $damaged = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $damaged[] = $mz;
            }
            if (empty($damaged)) return;
            SWUQueueChooseTarget(intval($player), $damaged, "Heal_3_damage_from_a_unit", "SEC_074#0");
            return;
        }

        case 'SEC_129': { // With Thunderous Applause — "Give a unit +2/+2 for this phase. You may
                          // disclose Command → give ANOTHER unit +2/+2 for this phase."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_+2/+2_for_this_phase", "SEC_129#0");
            return;
        }

        case 'SEC_105': { // Renewed Friendship — "Return a unit from your discard pile to your hand.
                          // Create 2 Spy tokens."
            global $playerID; $playerID = intval($player);
            $units = ZoneSearch("myDiscard", AnyUnitFilter);
            if (empty($units)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueChooseTarget(intval($player), $units, "Return_a_unit_from_your_discard_to_hand", "SEC_105#0");
            return;
        }

        case 'SEC_092': { // I Am the Senate — "Create 5 Spy tokens."
            SWUCreateUnitTokens(intval($player), 'SEC_T01', 5);
            return;
        }

        case 'SEC_128': { // Convene the Senate — "Search the top 8 for up to 2 Official units, draw them.
                          // Create a Spy token."
            SWUCreateUnitToken(intval($player), 'SEC_T01');
            DoTopDeckSearch(intval($player), 8, fn($c) => stripos(CardType($c) ?? '', 'unit') !== false && HasTrait($c, 'Official'), 2);
            return;
        }

        case 'SEC_177': { // It's Not Over Yet — "You may ready a unit that didn't attack or enter play
                          // this phase. Create a Spy token." Offer the ready BEFORE creating the Spy so the
                          // new token (which entered this phase) is never an eligible ready target; the Spy
                          // is then created in SEC_177#0 (always).
            global $playerID; $playerID = intval($player);
            $eligible = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                $uid = intval($o->UniqueID ?? 0);
                if (GlobalEffectCount(intval($player), 'SWU_PLAYED_UNIT_' . $uid) > 0) continue;   // entered this phase
                if (GlobalEffectCount(intval($player), 'SWU_UNIT_ATTACKED_' . $uid) > 0) continue;  // attacked this phase
                $eligible[] = $mz;
            }
            if (empty($eligible)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueMayChooseTarget(intval($player), $eligible, "Ready_a_unit?", "Choose_a_unit_to_ready", "SEC_177#0");
            return;
        }

        case 'SEC_230': { // Charged with Espionage — "You may disclose CunningCunning → look at an
                          // opponent's hand and discard a UNIT from it."
            SWUQueueDisclose(intval($player), ['Cunning', 'Cunning'], "SEC_230#0",
                "Disclose_CunningCunning_to_discard_a_unit_from_an_opponent's_hand");
            return;
        }

        case 'SEC_234': { // Bog Down in Procedure — "Exhaust a unit. You may disclose Cunning →
                          // exhaust another unit."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Exhaust_a_unit", "SEC_234#0");
            return;
        }

        case 'SEC_124': { // Budget Scheming — "Give an Experience token to each of up to 3 Official units."
            global $playerID; $playerID = intval($player);
            $officials = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Official')) $officials[] = $mz;
            }
            if (empty($officials)) return;
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|3|" . implode('&', $officials), 1, tooltip: "Give_Experience_to_up_to_3_Official_units");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_124#0", 1);
            return;
        }

        case 'SEC_040': { // Emergency Powers — "Choose a non-leader unit and pay any number of resources.
                          // For each resource paid, give an Experience token to the chosen unit."
            global $playerID; $playerID = intval($player);
            $units = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                                 ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_non-leader_unit", "SEC_040#0");
            return;
        }

        case 'SEC_247': { // Evil is Everywhere — "Defeat a unit with cost <= the number of Villainy aspect
                          // icons among friendly units."
            global $playerID; $playerID = intval($player);
            $vill = 0;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (!empty($u->removed)) continue;
                foreach (SWUCardAspectIcons($u->CardID ?? '') as $ic) if ($ic === 'Villainy') $vill++;
            }
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= $vill) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_(cost<={$vill})", "DEFEAT_UNIT");
            return;
        }

        case 'SEC_258': { // Grassroots Resistance — "Deal 3 to a unit. Heal 3 from your base."
            global $playerID; $playerID = intval($player);
            OnHealBase(intval($player), intval($player), 3);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_3_to_a_unit", "DEAL_UNIT_DAMAGE|3");
            return;
        }

        case 'SEC_180': { // Let's Call It War — "Deal 3 to a unit. Then, if you have the initiative, you
                          // may deal 2 to another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_3_to_a_unit", "SEC_180#0");
            return;
        }

        case 'SEC_183': { // Topple the Summit — "Deal 3 to each damaged unit." (Plot auto.)
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $uids[] = intval($o->UniqueID);
                }
            }
            foreach ($uids as $uid) { $playerID = intval($player); $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 3, intval($player)); }
            return;
        }

        case 'SEC_144': { // Tempest Assault — "If you've dealt damage to an enemy base this phase, deal 2
                          // to each enemy space unit."
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            if (GlobalEffectCount(intval($player), 'SWU_DMGBASE_' . $opp) <= 0) return;
            $uids = [];
            foreach (ZoneSearch("theirSpaceArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
            }
            return;
        }

        case 'SEC_078': { // Hyperspace Disaster — "Defeat all space units." (snapshot UIDs, then defeat by UID)
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (["mySpaceArena", "theirSpaceArena"] as $zone) {
                foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
                }
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            return;
        }

        case 'SEC_077': { // Retaliation — "Defeat a unit that dealt damage to a base this phase."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                $ctrl = intval($o->Controller ?? 0);
                if (GlobalEffectCount($ctrl, 'SWU_DEALT_BASEDMG_' . intval($o->UniqueID ?? 0)) > 0) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_that_damaged_a_base_this_phase", "DEFEAT_UNIT");
            return;
        }

        case 'SEC_127': { // Charged with Corruption — disclose CommandCommand → a friendly unit captures
                          // an enemy non-leader unit.
            SWUQueueDisclose(intval($player), ['Command', 'Command'], "SEC_127#0",
                "Disclose_CommandCommand_to_capture_an_enemy_unit");
            return;
        }

        case 'SEC_106': { // Dismantle the Conspiracy — "A friendly unit captures any number of enemy
                          // non-leader units with a total of 7 or less remaining HP."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_capturing_unit", "SEC_106#0");
            return;
        }

        case 'SEC_131': { // Let's Talk — "Each friendly unit captures an enemy non-leader unit in the same arena."
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
            }
            if (empty($uids)) return;
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_131#0|" . implode(',', $uids), 1);
            return;
        }

        case 'SEC_236': { // Undercover Operation — "Ready a unit that was played this phase. If it costs
                          // 3 or less, create a Spy token."
            global $playerID; $playerID = intval($player);
            $eligible = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (GlobalEffectCount(intval($player), 'SWU_PLAYED_UNIT_' . intval($o->UniqueID ?? 0)) > 0) $eligible[] = $mz;
            }
            if (empty($eligible)) return;
            SWUQueueChooseTarget(intval($player), $eligible, "Ready_a_unit_played_this_phase", "SEC_236#0");
            return;
        }

        case 'SEC_246': { // Contempt for Culture — "Deal 2 damage to a non-Vehicle unit. Create a Spy token."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_non-Vehicle_unit", "SEC_246#0");
            return;
        }

        case 'SEC_178': { // Pursue the Lead — "Choose a player. That player discards a card from their
                          // hand. If it costs 3 or less, create a Spy token."
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "You&Opponent", 1,
                tooltip: "Choose_a_player_to_discard_a_card");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_178#0|" . intval($player), 1);
            return;
        }

        case 'SEC_181': { // Unauthorized Investigation — "Create a Spy token. You may disclose
                          // Aggression → create another Spy token."
            SWUCreateUnitToken(intval($player), 'SEC_T01');
            SWUQueueDisclose(intval($player), ['Aggression'], "SEC_181#0",
                "Disclose_Aggression_to_create_another_Spy_token");
            return;
        }

        case 'SEC_182': { // Charged with Treason — "You may disclose AggressionAggression → deal 5 to a unit."
            SWUQueueDisclose(intval($player), ['Aggression', 'Aggression'], "SEC_182#0",
                "Disclose_AggressionAggression_to_deal_5_to_a_unit");
            return;
        }

        case 'SEC_211': { // Faith in Your Friends — "Search the top 3 of your deck for a card and draw it.
                          // Then, you may disclose CunningCunningCunningHeroismHeroism → create 2 Spy tokens."
                          // The disclose is queued at block 2 so it offers the hand AFTER the search's draw.
            DoTopDeckSearch(intval($player), 3, fn($c) => true, 1);
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_211#0", 2);
            return;
        }

        case 'SEC_076': { // Charged with Murder — "You may disclose VigilanceVigilance ... If you do,
                          // defeat a damaged non-leader unit." (CR §38)
            SWUQueueDisclose(intval($player), ['Vigilance', 'Vigilance'], "SEC_076_DEFEAT",
                "Disclose_VigilanceVigilance_to_defeat_a_damaged_non-leader_unit");
            return;
        }

        // ── LOF Events (Phase 13) ──────────────────────────────────────────────
        case 'LOF_042': { // Always Two — "Choose 2 friendly Sith units. If you do, give 2 Shield tokens
                          // and 2 Experience tokens to each chosen unit. Defeat all other friendly units."
            global $playerID; $playerID = intval($player);
            $sith = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Sith')) $sith[] = $mz;
            }
            if (count($sith) < 2) return; // can't choose 2 → whole effect fizzles, no defeats
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "2|2|" . implode('&', $sith), 1,
                tooltip: "Choose_2_friendly_Sith_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_042#0", 1);
            return;
        }

        case 'LOF_054': { // Calm in the Storm — "Exhaust a friendly unit. If you do, give a Shield token
                          // and 2 Experience tokens to it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_friendly_unit_(Shield_+_2_Experience)", "LOF_054#0");
            return;
        }

        case 'LOF_076': { // Soresu Stance — "Play a Force unit from your hand (paying its cost) and give a
                          // Shield token to it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_Force_unit_from_your_hand_(gives_it_a_Shield)", "LOF_076#0");
            return;
        }

        case 'LOF_077': { // Crushing Blow — "Defeat a non-leader unit that costs 2 or less."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
                if (intval(CardCost($o->CardID ?? '')) <= 2) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_costing_2_or_less", "LOF_077#0");
            return;
        }

        case 'LOF_078': { // Whirlwind of Power — "Give a unit -2/-2 for this phase. If you control a Force
                          // unit, give it -3/-3 instead."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            $n = PlayerHasUnitWithTraitInPlay(intval($player), 'Force', -1) ? 3 : 2;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-{$n}/-{$n}_for_this_phase", "APPLY_PHASE_DEBUFF|{$n}|{$n}|LOF_078");
            return;
        }

        case 'LOF_103': // Following the Path — "Search the top 8 cards for up to 2 Force units, reveal them,
                        // and put them on top of your deck in any order. (Put the others on the bottom.)"
            _topDeckSearchBegin(intval($player), 8,
                fn($c) => HasTrait($c, 'Force') && CardType($c) === 'Unit', "count:2", "LOF_103#0");
            return;

        case 'LOF_104': { // Luminous Beings — "Put up to 3 Force units from your discard pile on the bottom
                          // of your deck in a random order. Give that many units +4/+4 for this phase."
            global $playerID; $playerID = intval($player);
            $force = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $force[] = $mz;
            }
            if (empty($force)) return;
            $max = min(3, count($force));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $force), 1,
                tooltip: "Choose_up_to_3_Force_units_from_your_discard");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_104#0", 1);
            return;
        }

        case 'LOF_124': { // Niman Strike — "Attack with a Force unit, even if it's exhausted. It gets +1/+0
                          // and can't attack bases for this attack."
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (_SWUUnitHasTrait($u, 'Force')) $units[] = "{$zone}-{$i}"; // ready OR exhausted
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Attack_with_a_Force_unit_(+1/+0,_can't_attack_bases)", "LOF_124#0");
            return;
        }

        case 'LOF_125': { // The Burden of Masters — "Put a Force unit from your discard pile on the bottom
                          // of your deck. If you do, play a unit from your hand and give 2 Experience to it."
            global $playerID; $playerID = intval($player);
            $force = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $force[] = $mz;
            }
            if (empty($force)) return;
            SWUQueueChooseTarget(intval($player), $force, "Put_a_Force_unit_from_discard_on_the_bottom_of_your_deck", "LOF_125#0");
            return;
        }

        case 'LOF_126': { // Overpower — "Give a unit +3/+3 and Overwhelm for this phase."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+3/+3_and_Overwhelm_this_phase", "LOF_126#0");
            return;
        }

        case 'LOF_127': // Rampage — "Each friendly Creature unit gets +2/+2 for this phase."
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (HasTrait($o->CardID ?? '', 'Creature')) SWUApplyPhaseBuff($mz, 2, 2, 'LOF_127');
            }
            return;

        case 'LOF_128': { // Protect the Pod — "A friendly non-Vehicle unit deals damage equal to its
                          // remaining HP to an enemy unit."
            global $playerID; $playerID = intval($player);
            $friendly = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (!HasTrait($o->CardID ?? '', 'Vehicle')) $friendly[] = $mz;
            }
            if (empty($friendly)) return;
            if (empty(array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)))) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-Vehicle_unit_(deals_its_remaining_HP)", "LOF_128#0");
            return;
        }

        case 'LOF_141': { // Death Field — "Deal 2 damage to each non-Vehicle enemy unit. If you control a
                          // Force unit, draw a card."
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (!HasTrait($o->CardID ?? '', 'Vehicle')) $uids[] = intval($o->UniqueID ?? -1);
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 2, intval($player));
            }
            if (PlayerHasUnitWithTraitInPlay(intval($player), 'Force', -1)) DoDrawCard(intval($player), 1);
            return;
        }

        case 'LOF_152': // Focus Determines Reality — "Each friendly Force unit gains Raid 1 and Saboteur
                        // for this phase."
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (_SWUUnitHasTrait($o, 'Force')) {
                    AddTurnEffect($mz, 'LOF_152');          // Raid 1 (registry token), this phase
                    AddTurnEffect($mz, 'SABOTEUR^LOF_152'); // Saboteur, this phase (source = LOF_152)
                }
            }
            return;

        case 'LOF_174': { // Ataru Onslaught — "Ready a Force unit with 4 or less power."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (_SWUUnitHasTrait($o, 'Force') && intval(ObjectCurrentPower($o)) <= 4) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Ready_a_Force_unit_with_4_or_less_power", "READY_UNIT");
            return;
        }

        case 'LOF_176': { // Lightsaber Throw — "Discard a Lightsaber card from your hand. If you do, deal 4
                          // damage to a ground unit and draw a card."
            global $playerID; $playerID = intval($player);
            $sabers = [];
            $hand = GetHand($player);
            for ($i = 0; $i < count($hand); $i++) {
                $c = $hand[$i];
                if ($c === null || !empty($c->removed)) continue;
                if (HasTrait($c->CardID ?? '', 'Lightsaber')) $sabers[] = "myHand-{$i}";
            }
            if (empty($sabers)) return;
            SWUQueueChooseTarget(intval($player), $sabers, "Discard_a_Lightsaber_(deal_4_to_a_ground_unit_+_draw)", "LOF_176#0");
            return;
        }

        case 'LOF_203': // Premonition of Doom — "The next time you take the initiative this phase, exhaust
                        // all units." Lingering per-phase flag consumed in SWUTakeInitiative.
            AddGlobalEffects(intval($player), 'SWU_LOF203');
            return;

        case 'LOF_217': { // Force Slow — "Give an exhausted unit -8/-0 for this phase."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval($o->Status ?? 0) !== 1) $targets[] = $mz;  // exhausted only
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_an_exhausted_unit_-8/-0_this_phase", "APPLY_PHASE_DEBUFF|8|0|LOF_217");
            return;
        }

        case 'LOF_219': { // Psychometry — "Choose another card in your discard pile. Search the top 5 cards
                          // of your deck for a card that shares a trait with it, reveal it, and draw it."
            global $playerID; $playerID = intval($player);
            $cards = []; $skipped = false;
            $myD = GetDiscard($player);
            for ($i = 0; $i < count($myD); $i++) {
                $c = $myD[$i];
                if ($c === null || !empty($c->removed)) continue;
                if (!$skipped && ($c->CardID ?? '') === 'LOF_219') { $skipped = true; continue; } // "another card"
                $cards[] = "myDiscard-{$i}";
            }
            if (empty($cards)) return;
            SWUQueueChooseTarget(intval($player), $cards, "Choose_a_discard_card_(search_top_5_for_a_shared-trait_card)", "LOF_219#0");
            return;
        }

        case 'LOF_223': { // Force Illusion — "Exhaust an enemy unit. A friendly unit gains Sentinel for
                          // this phase."
            global $playerID; $playerID = intval($player);
            $enemy = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "LOF_223#0");
            return;
        }

        case 'LOF_224': { // Pounce — "Attack with a Creature unit. It gets +4/+0 for this attack."
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (HasTrait($u->CardID ?? '', 'Creature') && intval($u->Status ?? 0) === 1) $units[] = "{$zone}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Attack_with_a_Creature_unit_(+4/+0)", "LOF_224#0");
            return;
        }

        case 'LOF_225': { // Three Lessons — "Play a unit from your hand (paying its cost). It gains Hidden
                          // for this phase. Give an Experience token and a Shield token to it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_(Hidden_+_Experience_+_Shield)", "LOF_225#0");
            return;
        }

        case 'LOF_239': { // Consumed by the Dark Side — "Give 2 Experience tokens to a unit, then deal 2
                          // damage to it."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_then_deal_2_to_a_unit", "LOF_239#0");
            return;
        }

        case 'LOF_240': // Flight of the Inquisitor — "You may return a Force unit and a Lightsaber upgrade
                        // from your discard pile to your hand." (Two independent optional returns.)
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_240#0", 1);
            return;

        case 'LOF_241': { // In the Shadows — "Give an Experience token to each of up to 3 friendly units
                          // with Hidden."
            global $playerID; $playerID = intval($player);
            $hidden = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (HasKeyword_Hidden($o)) $hidden[] = $mz;
            }
            if (empty($hidden)) return;
            $max = min(3, count($hidden));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $hidden), 1,
                tooltip: "Give_an_Experience_token_to_each_of_up_to_3_Hidden_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_241#0", 1);
            return;
        }

        case 'LOF_262': { // Go Into Hiding — "Choose a unit. It can't be attacked this phase (unless it has
                          // Sentinel)." Grants the CANT_BE_ATTACKED phase marker.
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Make_a_unit_unattackable_this_phase", "GRANT_PHASE_KEYWORD|CANT_BE_ATTACKED^LOF_262");
            return;
        }

        case 'LOF_263': { // Last Words — "If a friendly unit was defeated this phase, give 2 Experience
                          // tokens to a unit."
            global $playerID; $playerID = intval($player);
            if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') <= 0) return;
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_tokens_to_a_unit", "GIVE_EXPERIENCE|2");
            return;
        }

        case 'LOF_264': { // It's Worse — "Defeat a non-leader unit."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit", "DEFEAT_UNIT");
            return;
        }

        case 'LOF_222': { // A Precarious Predicament — "Return an enemy non-leader unit to its owner's hand
                          // unless its controller says 'It could be worse.' If they do, you may play a card
                          // named It's Worse from your hand … for free."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_an_enemy_non-leader_unit_(unless_its_controller_objects)", "LOF_222#0");
            return;
        }

        case 'LOF_226': // Tip the Scale — "Look at an opponent's hand and discard a non-unit card from it."
            global $playerID; $playerID = intval($player);
            $tipTargets = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'unit') === false);
            SWUQueueChooseTarget(intval($player), $tipTargets, "Discard_a_non-unit_card_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
            if (count($tipTargets) <= 1) SWUQueueShowOpponentHand(intval($player));
            return;

        case 'LOF_205': { // Force Speed — "Attack with a unit. For this attack, it gains: 'On Attack: Return
                          // any number of non-unique upgrades attached to the defender to their owners' hands.'"
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status ?? 0) === 1) $units[] = "{$zone}-{$i}"; // ready units
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Attack_with_a_unit_(returns_defender's_non-unique_upgrades)", "LOF_205#0");
            return;
        }

        case 'LOF_177': { // Echoes of the Force — "Each player chooses a unit they control. Deal 3 damage to
                          // each unit not chosen this way." Caster picks first, then the opponent.
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($mine)) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_177_OPP|" . intval($player) . "|-1", 1);
                return;
            }
            SWUQueueChooseTarget(intval($player), $mine, "Choose_a_unit_you_control_(spared_from_the_3_damage)", "LOF_177_MINE|" . intval($player));
            return;
        }

        case 'LOF_189': { // Liberated by Darkness — "Use the Force (lose your Force token). If you do, take
                          // control of a non-leader unit. At the start of the regroup phase, its owner takes
                          // control of it." (Reuses the TEMPORARY_STEAL marker → returned at RegroupPhaseStart.)
            global $playerID; $playerID = intval($player);
            if (!PlayerHasTheForce(intval($player))) return; // can't pay the Force → whole effect fizzles
            UseTheForce(intval($player));
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed) || IsLeaderUnit($o)) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_a_non-leader_unit_(until_regroup)", "LOF_189#0");
            return;
        }

        case 'LOF_202': // Mind Trick — "Exhaust any number of units with a combined power of 4 or less. If
                        // you control a Force unit, those units lose all abilities … for this phase."
            global $playerID; $playerID = intval($player);
            _SWUCombinedBudgetOffer(intval($player), 4, 'power', 1);
            return;

        case 'LOF_043': { // The Tragedy of Plagueis — "Choose a friendly unit. For this phase, it can't be
                          // defeated by having no remaining HP. An opponent chooses a unit they control.
                          // Defeat that unit."
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (!empty($mine)) {
                SWUQueueChooseTarget(intval($player), $mine, "Choose_a_friendly_unit_(can't_be_defeated_by_no_HP_this_phase)", "LOF_043#0");
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "OPP_DEFEAT_OWN_UNIT|0", 1);
            return;
        }

        case 'LOF_220': { // Shien Flurry — "Play a Force unit from your hand (paying its cost). It gains
                          // Ambush for this phase. The next time it would be dealt damage this phase,
                          // prevent 2 of that damage."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_Force_unit_(Ambush_+_prevent_2_of_next_damage)", "LOF_220#0");
            return;
        }

        case 'LOF_172': { // Sorcerous Blast — "Use the Force (lose your Force token). If you do, deal 3 damage to a unit."
            // CR 37.4: a player may only Use the Force if they control their Force token. If they don't,
            // they did not Use the Force, so the "If you do" rider fails and the event fizzles.
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player)); // defeat the Force token
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $targets, "Deal_3_damage_to_a_unit", "DEAL_UNIT_DAMAGE|3");
            return;
        }

        case 'LOF_041': { // Drain Essence — "Deal 2 damage to a unit. The Force is with you."
            // The Force creation is unconditional (separate sentence); the deal-2 fizzles cleanly with
            // no units in play.
            TheForceIsWithYou(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit", "DEAL_UNIT_DAMAGE|2");
            return;
        }

        case 'LOF_123': { // Directed by the Force — "The Force is with you. You may play a unit from your
                          // hand (paying its cost)." Nested play inside the event resolution.
            TheForceIsWithYou(intval($player));
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Play_a_unit_from_your_hand?", "Choose_a_unit_to_play", "LOF_123#0");
            return;
        }

        case 'LOF_216': { // Disturbance in the Force — "If a friendly unit left play this phase, the Force
                          // is with you and you may give a Shield token to a unit."
            if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEFT_PLAY') <= 0) return;
            TheForceIsWithYou(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Give_a_Shield_token_to_a_unit?", "Choose_a_unit_to_Shield", "GIVE_SHIELD");
            return;
        }

        case 'LOF_075': { // Cure Wounds — "Use the Force. If you do, heal 6 damage from a unit." Mandatory
                          // use (auto if you control the Force; fizzles if you don't).
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Heal_6_damage_from_a_unit", "HEAL_TARGET|6");
            return;
        }

        case 'LOF_173': { // Unleash Rage — "Use the Force. If you do, give a friendly unit +3/+0 this phase."
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player));
            $targets = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_friendly_unit_+3/+0", "APPLY_PHASE_BUFF|3|0|LOF_173");
            return;
        }

        case 'LOF_079': { // Shatterpoint — "Choose one: Defeat a non-leader unit with 3 or less remaining
                          // HP. / Use the Force → defeat a non-leader unit."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "DefeatWeak&ForceDefeat", 1,
                tooltip: "Choose:_defeat_a_3-or-less-HP_unit,_or_use_the_Force_to_defeat_any_non-leader");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_079#0", 1);
            return;
        }

        case 'LOF_175': { // Do or Do Not — "You may use the Force. If you do, draw 2. If you do not, draw 1."
            if (!PlayerHasTheForce(intval($player))) { DoDrawCard(intval($player), 1); return; }
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip: "Use_the_Force_to_draw_2?_(otherwise_draw_1)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_175#0", 1);
            return;
        }

        case 'LOF_188': { // As I Have Foreseen — "Look at the top card. You may use the Force. If you do,
                          // play that card. It costs 4 resources less." (No Force → you just looked.)
            if (!PlayerHasTheForce(intval($player))) return;
            // Only offer to use the Force if the top card is affordable at its −4 discount — otherwise the
            // Force would be spent for a play that can't happen. If unaffordable, the player just looked.
            global $playerID; $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;
            $topObj = GetDeck(intval($player))[$idx];
            if (max(0, SWUComputePlayCost(intval($player), $topObj) - 4)
                > SWUResourceCount(intval($player), readyOnly: true)) return;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip: "Use_the_Force_to_play_the_top_card_(4_less)?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_188#0", 1);
            return;
        }

        case 'LOF_218': { // Impossible Escape — "You may either exhaust a friendly unit OR use the Force.
                          // If you do either, exhaust an enemy unit and draw a card."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "ExhaustFriendly&UseForce&Neither", 1,
                tooltip: "Pay_a_cost_to_exhaust_an_enemy_unit_and_draw");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_218#0", 1);
            return;
        }

        case 'LOF_221': { // Trust Your Instincts — "Use the Force. If you do, attack with a unit. It gets
                          // +2/+0 for this attack and deals its combat damage before the defender."
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player));
            global $playerID, $gShootFirstPending;
            $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            $gShootFirstPending = true;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "LOF_221_ATTACK", 1);
            return;
        }

        case 'LOF_227': { // The Will of the Force — "Return a non-leader unit to its owner's hand. You may
                          // use the Force. If you do, that player discards a random card from their hand."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_to_its_owner's_hand", "LOF_227#0");
            return;
        }

        case 'JTL_234': // Torpedo Barrage — Deal 5 indirect damage to a player (you choose; CR §35).
            SWUDealIndirectToChosenPlayer(intval($player), 5);
            return;

        case 'JTL_128': { // Prepare for Takeoff — search the top 8 cards for up to 2 Vehicle units, reveal
                          // and draw them.
            global $playerID;
            $playerID = intval($player);
            DoTopDeckSearch(intval($player), 8,
                fn($c) => stripos(CardType($c) ?? '', 'Unit') !== false && HasTrait($c, 'Vehicle'), 2);
            return;
        }

        case 'JTL_228': { // Barrel Roll — "Attack with a space unit. After completing this attack, you
                          // may exhaust a space unit."
            global $playerID;
            $playerID = intval($player);
            $units = [];
            $arr = GetZone('mySpaceArena');
            for ($i = 0; $i < count($arr); $i++) {
                $u = $arr[$i];
                if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                $units[] = "mySpaceArena-{$i}";
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_space_unit_to_attack_with", "JTL_228#0");
            return;
        }

        case 'JTL_231': { // Punch It — "Attack with a Vehicle unit. It gets +2/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                    if (HasTrait($u->CardID, 'Vehicle')) $units[] = "{$zone}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_Vehicle_unit_to_attack_with", "JTL_231#0");
            return;
        }

        case 'JTL_261': { // Attack Run — "Attack with 2 space units (one at a time)."
            global $playerID;
            $playerID = intval($player);
            $units = [];
            $arr = GetZone('mySpaceArena');
            for ($i = 0; $i < count($arr); $i++) {
                $u = $arr[$i];
                if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                $units[] = "mySpaceArena-{$i}";
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_the_first_space_unit_to_attack_with", "JTL_261#0");
            return;
        }

        case 'JTL_156':   // Trench Run — attack w/ a Fighter (+4/+0 + granted On-Attack discard/self-damage)
        case 'JTL_177':   // Stay on Target — attack w/ a Vehicle (+2/+0 + granted base-damage→draw)
        case 'JTL_193': { // I Have You Now — attack w/ a Vehicle; prevent all damage to it this attack
            global $playerID;
            $playerID = intval($player);
            $trait = ($cardID === 'JTL_156') ? 'Fighter' : 'Vehicle';
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                    if (HasTrait($u->CardID, $trait)) $units[] = "{$zone}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_attack_with", $cardID . '#0');
            return;
        }

        case 'JTL_123': { // Dogfight — "Attack with a unit. It can attack even if it's exhausted. It
                          // can't attack bases for this attack." (Candidates include exhausted units.)
            global $playerID;
            $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    $units[] = "{$zone}-{$i}";   // ready OR exhausted
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_attack_with_(can't_attack_bases)", "JTL_123#0");
            return;
        }

        case 'JTL_124': { // Tandem Assault — "Attack with a space unit. Then, attack with a ground unit.
                          // It gets +2/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $spaceUnits = [];
            $arr = GetZone('mySpaceArena');
            for ($i = 0; $i < count($arr); $i++) {
                $u = $arr[$i];
                if ($u === null || !empty($u->removed)) continue;
                if (intval($u->Status) === 1) $spaceUnits[] = "mySpaceArena-{$i}";
            }
            if (empty($spaceUnits)) return;
            SWUQueueChooseTarget(intval($player), $spaceUnits, "Choose_a_space_unit_to_attack_with", "JTL_124#0");
            return;
        }

        case 'JTL_235': { // Commandeer — "Take control of a non-leader Vehicle unit that costs 6 or less
                          // without a Pilot. Ready it. At the start of the next regroup phase, return it."
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch('myGroundArena',    NonLeaderUnitFilter), ZoneSearch('mySpaceArena',    NonLeaderUnitFilter),
                ZoneSearch('theirGroundArena', NonLeaderUnitFilter), ZoneSearch('theirSpaceArena', NonLeaderUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (HasTrait($o->CardID, 'Vehicle') && intval(CardCost($o->CardID)) <= 6 && !_SWUHasPilotOnIt($o)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_a_Vehicle_(returns_next_regroup)", "JTL_235#0");
            return;
        }

        case 'JTL_181': { // Planetary Bombardment — "Deal 8 indirect damage to a player. If you control a
                          // Capital Ship unit, deal 12 indirect damage instead."
            global $playerID;
            $playerID = intval($player);
            $cap = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) { if (HasTrait($u->CardID ?? '', 'Capital Ship')) { $cap = true; break; } }
            SWUDealIndirectToChosenPlayer(intval($player), $cap ? 12 : 8);
            return;
        }

        case 'JTL_077': { // In the Heat of Battle — "Each unit gains Sentinel and loses Saboteur for this
                          // phase."
            global $playerID;
            $playerID = intval($player);
            for ($p = 1; $p <= 2; $p++) {
                foreach (array_merge(GetGroundArena($p), GetSpaceArena($p)) as $u) {
                    if ($u === null || !empty($u->removed)) continue;
                    $mz = $u->GetMzID();
                    AddTurnEffect($mz, 'JTL_077_SENTINEL');   // gain Sentinel this phase
                    AddTurnEffect($mz, 'JTL_077');            // lose Saboteur this phase (suppressor)
                }
            }
            return;
        }

        case 'JTL_244': { // There Is No Escape — "Choose up to 3 units. Those units lose all abilities and
                          // can't gain abilities for this round."
            global $playerID;
            $playerID = intval($player);
            $units = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            ));
            if (empty($units)) return;
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . implode("&", $units), 1, "Choose_up_to_3_units_to_lose_abilities");
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_244#0", 1);
            return;
        }

        case 'JTL_125': { // Air Superiority — "If you control more space units than an opponent, deal 4
                          // damage to a ground unit that opponent controls."
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $mine = 0; foreach (GetSpaceArena(intval($player)) as $u) { if (empty($u->removed)) $mine++; }
            $thrs = 0; foreach (GetSpaceArena($opp)             as $u) { if (empty($u->removed)) $thrs++; }
            if ($mine <= $thrs) return;
            $targets = array_values(ZoneSearch('theirGroundArena', AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_4_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|4");
            return;
        }

        case 'JTL_129': { // Focus Fire — "Choose a unit. Each friendly Vehicle unit in the same arena
                          // deals damage equal to its power to that unit."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            ));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Each_friendly_Vehicle_in_that_arena_deals_its_power", "JTL_129#0");
            return;
        }

        case 'JTL_174': { // Hotshot Maneuver — "Choose a friendly unit. For each of its 'On Attack'
                          // abilities, deal 2 damage to a different enemy unit. Then, attack with the
                          // chosen unit."
            global $playerID;
            $playerID = intval($player);
            $friendly = array_values(array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)));
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit", "JTL_174#0");
            return;
        }

        case 'JTL_127': { // Lightspeed Assault — "Defeat a friendly space unit and deal damage equal to
                          // its power to an enemy space unit. If you do, deal indirect damage equal to the
                          // enemy unit's power to its controller." Needs both a friendly and an enemy
                          // space unit to do anything.
            global $playerID;
            $playerID = intval($player);
            $friendly = ZoneSearch('mySpaceArena', AnyUnitFilter);
            $enemy    = ZoneSearch('theirSpaceArena', AnyUnitFilter);
            if (empty($friendly) || empty($enemy)) return; // can't complete the combined effect → fizzle
            SWUQueueChooseTarget(intval($player), $friendly, "Defeat_a_friendly_space_unit", "JTL_127#0");
            return;
        }

        case 'JTL_131': { // Turbolaser Salvo — "Choose an arena. A friendly space unit deals damage equal
                          // to its power to each enemy unit in that arena." Choose arena → choose the
                          // (space) dealer → AOE its power to each enemy in that arena.
            global $playerID;
            $playerID = intval($player);
            if (empty(ZoneSearch('mySpaceArena', AnyUnitFilter))) return; // no friendly space unit → fizzle
            DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', 'Ground&Space', 1, tooltip: "Choose_an_arena");
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_131#0', 1);
            return;
        }

        case 'JTL_180': { // Piercing Shot — "Defeat all Shield tokens on a unit. Deal 3 damage to it."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            ));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_all_Shields_then_deal_3", "JTL_180#0");
            return;
        }

        case 'JTL_043': { // No Glory, Only Results — "Take control of a non-leader unit, then defeat it."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    NonLeaderUnitFilter), ZoneSearch('mySpaceArena',    NonLeaderUnitFilter),
                ZoneSearch('theirGroundArena', NonLeaderUnitFilter), ZoneSearch('theirSpaceArena', NonLeaderUnitFilter)
            ));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_and_defeat_a_non-leader_unit", "JTL_043#0");
            return;
        }

        case 'JTL_205': { // Daring Raid — "Put another card in a discard pile on the bottom of its owner's
                          // deck. If you do, create an X-Wing token." (may; token only on a put.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            $myD = GetDiscard(intval($player));
            for ($i = 0; $i < count($myD); $i++) { if ($myD[$i] !== null && empty($myD[$i]->removed)) $targets[] = "myDiscard-{$i}"; }
            $thD = GetDiscard(GetOpponent(intval($player)));
            for ($i = 0; $i < count($thD); $i++) { if ($thD[$i] !== null && empty($thD[$i]->removed)) $targets[] = "theirDiscard-{$i}"; }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Put_a_discarded_card_on_the_bottom_of_its_owner's_deck",
                "Put_a_discarded_card_on_the_bottom_of_its_owner's_deck", "JTL_205#0");
            return;
        }

        case 'JTL_207': { // Spy Net — "Look at an opponent's hand and discard an event from it."
            global $playerID;
            $playerID = intval($player);
            $targets = SWULookAtOpponentHand(intval($player), fn($cid) => stripos(CardType($cid) ?? '', 'event') !== false);
            SWUQueueChooseTarget(intval($player), $targets, "Discard_an_event_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
            if (count($targets) <= 1) SWUQueueShowOpponentHand(intval($player));
            return;
        }

        case 'JTL_208': { // Cunning — "Discard 3 cards from an opponent's deck and 3 cards from your deck.
                          // Deal damage to a unit equal to the number of cards with an odd cost discarded
                          // this way."
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $odd = 0;
            for ($i = 0; $i < 3; $i++) { $c = SWUMillTopCard($opp);            if ($c !== null && (intval(CardCost($c)) % 2) === 1) $odd++; }
            for ($i = 0; $i < 3; $i++) { $c = SWUMillTopCard(intval($player)); if ($c !== null && (intval(CardCost($c)) % 2) === 1) $odd++; }
            if ($odd <= 0) return;
            $units = array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $units, "Deal_{$odd}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$odd}");
            return;
        }

        case 'JTL_121': { // Salvage — "Play a Vehicle unit from your discard pile (paying its cost). Then,
                          // deal 1 damage to it." Offer the AFFORDABLE Vehicle units in the discard; the
                          // continuation plays the pick at cost and deals 1 to it.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            $myD = GetDiscard(intval($player));
            for ($i = 0; $i < count($myD); $i++) {
                $e = $myD[$i];
                if ($e === null || !empty($e->removed)) continue;
                $cid = $e->CardID ?? '';
                if ($cid === '' || strpos(CardType($cid) ?? '', 'Unit') === false) continue; // a unit
                if (!HasTrait($cid, 'Vehicle')) continue;                                      // ... a Vehicle
                $cost = max(0, intval(CardCost($cid)) + SWUAspectPenalty(intval($player), $cid));
                if (SWUResourceCount(intval($player), true) < $cost) continue;                 // affordable only
                $targets[] = "myDiscard-$i";
            }
            if (empty($targets)) return; // no affordable Vehicle in the discard → fizzle
            SWUQueueChooseTarget(intval($player), $targets,
                "Play_a_Vehicle_unit_from_your_discard_(paying_its_cost)", "JTL_121#0");
            return;
        }

        case 'JTL_074': { // Close the Shield Gate — "Choose a base. The next time damage would be dealt to
                          // it this phase, prevent that damage." Arm the SWU_SHIELD_GATE flag on the
                          // chosen base's owner (consumed in SWUDealDamageToBase, cleared at regroup).
            global $playerID;
            $playerID = intval($player);
            SWUQueueChooseTarget(intval($player), ["myBase-0", "theirBase-0"],
                "Choose_a_base_to_protect_from_the_next_damage", "JTL_074#0");
            return;
        }

        case 'JTL_155': { // They Hate That Ship — "An opponent creates 2 TIE Fighter tokens and readies
                          // them. Then, play a Vehicle unit from your hand. It costs 3 resources less."
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            SWUCreateUnitTokens($opp, 'JTL_T01', 2, true); // 2 TIE Fighters (Space, 1/1), readied
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 3) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return; // no affordable Vehicle in hand → only the TIEs were created
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_Vehicle_unit_(costs_3_less)", "JTL_155#0");
            return;
        }

        case 'JTL_232': { // Jump to Lightspeed — return a friendly space unit (and its non-leader upgrades)
                          // to owners' hands (continuation JTL_232; free-replay rider deferred).
            global $playerID;
            $playerID = intval($player);
            $space = ZoneSearch("mySpaceArena", AnyUnitFilter);
            $space = array_values(array_filter($space, function($mz) { $o = GetZoneObject($mz); return $o !== null && !IsLeaderUnit($o); }));
            if (empty($space)) return;
            SWUQueueChooseTarget(intval($player), $space, "Return_a_friendly_space_unit_to_hand", "JTL_232#0");
            return;
        }

        case 'JTL_233': { // Sweep the Area — return up to 2 non-leader units in the same arena with combined
                          // cost <= 3 to their owners' hands (continuation JTL_233 validates).
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
                "0|2|" . implode("&", $targets), 1, tooltip: "Return_up_to_2_same-arena_units_(combined_cost_3_or_less)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_233#0", 1);
            return;
        }

        case 'JTL_209': { // It's a Trap — if an opponent controls more space units than you, ready each
                          // space unit you control.
            global $playerID;
            $playerID = intval($player);
            $mine = ZoneSearch("mySpaceArena", AnyUnitFilter);
            if (count(ZoneSearch("theirSpaceArena", AnyUnitFilter)) <= count($mine)) return;
            foreach ($mine as $mz) OnReadyCard(intval($player), $mz);
            return;
        }

        case 'JTL_262': { // Evasive Maneuver — exhaust a unit.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit", "EXHAUST_UNIT");
            return;
        }

        case 'JTL_179': { // Koiogran Turn — ready a Fighter or Transport unit with 6 or less power.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if ((HasTrait($o->CardID, 'Fighter') || HasTrait($o->CardID, 'Transport')) && ObjectCurrentPower($o) <= 6) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Ready_a_Fighter/Transport_unit_with_6_or_less_power", "READY_UNIT");
            return;
        }

        case 'JTL_195': { // Cat and Mouse — exhaust an enemy unit; if you do, ready a friendly unit in the
                          // same arena with power <= that enemy unit's power (continuation JTL_195).
            global $playerID;
            $playerID = intval($player);
            $enemies = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemies)) return;
            SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "JTL_195#0");
            return;
        }

        case 'JTL_206': { // Fly Casual — ready a Vehicle unit; it can't attack bases for this phase.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Ready_a_Vehicle_unit_(can't_attack_bases_this_phase)", "JTL_206#0");
            return;
        }

        case 'JTL_178': { // Face Off — if no player has taken the initiative this phase, you may ready an
                          // enemy unit; if you do, ready a friendly unit in the same arena (cont JTL_178).
            global $playerID;
            $playerID = intval($player);
            if (strpos((string)GetInitiativeCounter(), 'UNCLAIMED') === false) return; // initiative taken
            $enemies = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($enemies)) return;
            SWUQueueMayChooseTarget(intval($player), $enemies,
                "You_may_ready_an_enemy_unit", "Ready_an_enemy_unit", "JTL_178#0");
            return;
        }

        case 'JTL_254': { // Dedicated Wingmen — create 2 X-Wing tokens.
            SWUCreateUnitTokens(intval($player), 'JTL_T02', 2);
            return;
        }

        case 'JTL_122': { // All Wings Report In — exhaust up to 2 friendly space units; for each unit
                          // exhausted this way, create an X-Wing token. (Continuation JTL_122.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch("mySpaceArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $targets[] = $mz; // ready only
            }
            if (empty($targets)) return;
            $max = min(2, count($targets));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
                "0|" . $max . "|" . implode("&", $targets), 1, tooltip: "Exhaust_up_to_2_friendly_space_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_122#0", 1);
            return;
        }

        case 'JTL_130': { // Timely Reinforcements — choose an opponent; for every 2 resources they
                          // control, create an X-Wing token and give it Sentinel for this phase. (2-player:
                          // the single opponent.)
            global $playerID;
            $playerID = intval($player);
            $opp = GetOpponent(intval($player));
            $n = intdiv(count(GetResources($opp)), 2);
            // X-Wing (Space, 2/2) with JTL_130 (Sentinel this phase); the marker rides the batch funnel so
            // any Moff-Jerjerrod-doubled X-Wings get it too.
            SWUCreateUnitTokens(intval($player), 'JTL_T02', $n, false, 'JTL_130');
            return;
        }

        case 'JTL_092': { // Scramble Fighters — create 8 TIE Fighter tokens, readied; they can't attack
                          // bases for this phase (per-token CANT_ATTACK_BASES marker, expires at regroup).
            global $playerID;
            $playerID = intval($player);
            // 8 TIE Fighters (Space, 1/1), readied, CANT_ATTACK_BASES this phase; the marker rides the batch
            // funnel so any Moff-Jerjerrod-doubled TIEs also can't attack bases.
            SWUCreateUnitTokens(intval($player), 'JTL_T01', 8, true, 'CANT_ATTACK_BASES');
            return;
        }

        case 'JTL_253': { // Coordinated Front — you may give a ground unit +2/+2, and you may give a
                          // space unit +2/+2 (two independent optional grants). Ground first, then the
                          // space half via the JTL_253 continuation.
            global $playerID;
            $playerID = intval($player);
            $ground = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("theirGroundArena", AnyUnitFilter));
            if (!empty($ground)) {
                SWUQueueMayChooseTarget(intval($player), $ground,
                    "You_may_give_a_ground_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_253");
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_253#0", 1);
            return;
        }

        case 'JTL_229': { // Diversion — give a unit Sentinel for this phase.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_Sentinel_this_phase", "GRANT_PHASE_KEYWORD|JTL_229");
            return;
        }

        case 'JTL_194': { // Heartless Tactics — exhaust a unit and give it -2/-0 this phase; then if it
                          // has 0 power and isn't a leader, you may return it to its owner's hand.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_and_-2/-0_a_unit", "JTL_194#0");
            return;
        }

        case 'JTL_106': { // Unity of Purpose — for each friendly unit with a DIFFERENT name, give each
                          // unit you control +1/+1 this phase. N = number of distinct names among your
                          // units; every friendly unit gets +N/+N.
            global $playerID;
            $playerID = intval($player);
            $myUnits = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($myUnits)) return;
            $names = [];
            foreach ($myUnits as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $names[CardTitle($o->CardID)] = true;
            }
            $n = count($names);
            if ($n <= 0) return;
            foreach ($myUnits as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($mz, $n, $n, 'JTL_106');
            }
            return;
        }

        case 'JTL_042': { // Power from Pain — give a unit +1/+0 this phase for each damage on it.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+1/+0_per_damage_on_it", "JTL_042#0");
            return;
        }

        case 'JTL_079': { // Out the Airlock — give a unit -5/-5 for this phase.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-5/-5_this_phase", "APPLY_PHASE_DEBUFF|5|5|JTL_079");
            return;
        }

        case 'JTL_075': { // Repair — heal 3 damage from a unit or base.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter),
                ['myBase-0', 'theirBase-0']
            );
            SWUQueueChooseTarget(intval($player), $targets, "Heal_3_from_a_unit_or_base", "HEAL_TARGET|3");
            return;
        }

        case 'JTL_076': { // Covering the Wing — create an X-Wing token; you may give a Shield to ANOTHER
                          // unit (not the just-created X-Wing).
            global $playerID;
            $playerID = intval($player);
            $before = [];
            foreach (GetField(intval($player)) as $u) {
                if ($u !== null && empty($u->removed)) $before[] = intval($u->UniqueID ?? 0);
            }
            SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
            $xwUid = 0;
            foreach (GetField(intval($player)) as $u) {
                if ($u === null || !empty($u->removed)) continue;
                $uid = intval($u->UniqueID ?? 0);
                if (!in_array($uid, $before, true)) { $xwUid = $uid; break; }
            }
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $xwUid) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "You_may_give_a_Shield_to_another_unit", "Give_a_Shield_token", "GIVE_SHIELD");
            return;
        }

        case 'JTL_175': { // System Shock — defeat a non-leader upgrade attached to a unit; if you do,
                          // deal 1 to that unit (thenHandler JTL_175 reads DefeatUpgHost).
            SWUQueueDefeatUpgrade(intval($player), "Defeat_a_non-leader_upgrade",
                may: false, max: 1, filter: "leader=0", min: 1, thenHandler: "JTL_175#0");
            return;
        }

        case 'JTL_230': { // Electromagnetic Pulse — deal 2 to a Droid or Vehicle unit and exhaust it.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (HasTrait($o->CardID, 'Droid') || HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Deal_2_and_exhaust_a_Droid_or_Vehicle_unit", "JTL_230#0");
            return;
        }

        case 'JTL_078': { // Direct Hit — defeat a non-leader Vehicle unit.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_Vehicle_unit", "DEFEAT_UNIT");
            return;
        }

        case 'JTL_080': { // Nebula Ignition — defeat each unit that isn't upgraded (no attached upgrades,
                          // including token upgrades). Snapshot UIDs first (mass defeat is index-unstable).
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (empty(GetUpgradesOnUnit($o))) $uids[] = intval($o->UniqueID ?? 0);
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null && $mz !== '') SWUDefeatUnit(intval($player), $mz);
            }
            return;
        }

        case 'JTL_126': { // Eject — Detach a Pilot upgrade, move it to the ground arena as a unit, and
                          // exhaust it. Draw a card. (Continuation JTL_126.) The choice is the host
                          // vehicle (a Vehicle holds at most one Pilot), across both players' arenas.
            global $playerID;
            $playerID = intval($player);
            $hosts = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && _SWUFindPilotSubcard($o) !== null) $hosts[] = $mz;
                }
            }
            if (empty($hosts)) return;
            SWUQueueChooseTarget(intval($player), $hosts, "Detach_a_Pilot_upgrade", "JTL_126#0");
            return;
        }

        case 'JTL_091': { // Apology Accepted — defeat a friendly unit; you may give 2 Experience tokens
                          // to a unit (continuation JTL_091).
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_friendly_unit", "JTL_091#0");
            return;
        }

        case 'JTL_055': { // You're All Clear, Kid — defeat an enemy space unit with 3 or less remaining
                          // HP. If you do and an opponent controls no space units, you may give an
                          // Experience token to a unit (continuation JTL_055).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch("theirSpaceArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && (ObjectCurrentHP($o) - intval($o->Damage)) <= 3) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Defeat_an_enemy_space_unit_with_3_or_less_remaining_HP", "JTL_055#0");
            return;
        }

        case 'JTL_173': { // Fight Fire With Fire — choose a friendly unit and an enemy unit in the SAME
                          // arena; deal 3 to each. Offer only friendly units in an arena that has an
                          // enemy unit, then pick the same-arena enemy (continuation JTL_173 → #1).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (['Ground', 'Space'] as $a) {
                if (empty(ZoneSearch("their{$a}Arena", AnyUnitFilter))) continue;
                foreach (ZoneSearch("my{$a}Arena", AnyUnitFilter) as $mz) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Choose_a_friendly_unit_(deal_3_to_it_and_a_same-arena_enemy)", "JTL_173#0");
            return;
        }

        case 'JTL_176': { // Shoot Down — deal 3 to a space unit; if it is defeated this way, you may
                          // deal 2 to a base (continuation JTL_176).
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch("mySpaceArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_space_unit", "JTL_176#0");
            return;
        }

        case 'JTL_144': { // No Disintegrations — deal damage to a non-leader unit equal to 1 less than
                          // its remaining HP (so it can never defeat the unit). Amount is computed in
                          // the JTL_144 continuation at resolution time.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Deal_damage_to_a_non-leader_unit_(1_less_than_its_remaining_HP)", "JTL_144#0");
            return;
        }

        case 'SOR_219': { // Sneak Attack — "Play a unit from your hand. It costs 3 less and enters
                          // play ready. At the start of the regroup phase, defeat it." Offer the hand
                          // units the player can afford at the -3 discount; SOR_219 plays the pick.
            global $playerID;
            $playerID = intval($player);
            $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 3);
            if (empty($targets)) return; // no affordable unit → fizzle (event already in discard)
            SWUQueueChooseTarget(intval($player), $targets,
                "Play_a_unit_(costs_3_less,_enters_ready,_defeated_at_regroup)", "SOR_219#0");
            return;
        }

        case 'SOR_245': { // Medal Ceremony — "Give an Experience token to each of up to 3 Rebel units
                          // that attacked this phase." Read the caster's SWU_ATTACKED_{uid} flags and
                          // keep only in-play Rebel units (the flag is per attacking player).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (!HasTrait($o->CardID, 'Rebel')) continue;
                $uid = intval($o->UniqueID ?? 0);
                if (GlobalEffectCount(intval($player), 'SWU_ATTACKED_' . $uid) <= 0) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;  // no eligible Rebel attacker → fizzle
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . implode("&", $targets), 1, tooltip:"Give_Experience_to_up_to_3_Rebel_units_that_attacked_this_phase");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_245#0", 1);
            return;
        }

        case 'SOR_138': { // Force Lightning — "Choose a unit. It loses all abilities for this phase.
                          // Then, if you control a FORCE unit, pay any number of resources and deal 2
                          // damage to the chosen unit for each resource paid this way."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_to_lose_all_abilities_this_phase", "SOR_138#0");
            return;
        }

        case 'SOR_041': { // Power of the Dark Side — "An opponent chooses a unit they control. Defeat
                          // that unit." Any unit (incl. leaders). Cross-player choose via the
                          // intermediate CUSTOM (nonLeader=0). The event flow's FINISH_PLAY_CARD
                          // owns the after-action, so just queue the choose.
            DecisionQueueController::AddDecision($player, "CUSTOM", "OPP_DEFEAT_OWN_UNIT|0", 1);
            return;
        }

        case 'SOR_233': { // I Am Your Father — "Deal 7 damage to an enemy unit unless its controller
                          // says 'no.' If they do, draw 3 cards." Caster picks the enemy unit; the
                          // unit's controller then gets a YESNO (refuse the damage → caster draws 3).
            global $playerID;
            $playerID = intval($player);
            $enemies = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemies)) return;   // no enemy unit → fizzle
            SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "SOR_233#0");
            return;
        }

        case 'SOR_187': { // I Had No Choice — "Choose up to 2 non-leader units. An opponent chooses 1
                          // of those units. Return that unit to its owner's hand and put the other on
                          // the bottom of its owner's deck."
            global $playerID;
            $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($units)) return;   // no non-leader unit → fizzle
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|2|" . implode("&", $units), 1,
                tooltip:"Choose_up_to_2_non-leader_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_187#0", 1);
            return;
        }

        case 'SOR_174': { // Smoke and Cinders — "Each player discards all but 2 cards (of their choice)
                          // from their hand." Queue the opponent's keep-2 first, then the caster's, so
                          // $playerID is left = caster (whose MZMULTICHOOSE is validated first, in their
                          // own queue). Each player's decision is answered under their own $playerID.
            $opp = OtherPlayer(intval($player));
            SWUKeepNDiscardRest($opp, 2, "Keep_2_cards_-_discard_the_rest");
            SWUKeepNDiscardRest(intval($player), 2, "Keep_2_cards_-_discard_the_rest");
            return;
        }

        case 'SOR_043': { // Superlaser Blast — "Defeat all units." Snapshot every unit's UID across all
                          // four arenas (incl. deployed leaders + tokens), then defeat by UID so the
                          // index shift from each defeat can't stale the others (simultaneous mass-defeat
                          // through the SWUDefeatUnit collector, which fires each WhenDefeated).
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $zone) {
                foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
                }
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            return;
        }

        case 'SOR_042': { // Search Your Feelings — "Search your deck for a card and draw it. (Then,
                          // shuffle your deck.)" Reuse the top-N search with n = full deck size + an
                          // any-card filter + pick 1: TOPDECKSEARCH_FINALIZE draws the pick and shuffles
                          // the rest back (a full reshuffle, since the WHOLE deck was peeked). The
                          // searcher may draw nothing (AnswerDecision:''). The peeked cards are private
                          // to the searcher (it's their own decision).
            global $playerID;
            $playerID = intval($player);
            $deckSize = count(GetDeck(intval($player)));
            if ($deckSize === 0) return;   // empty deck → nothing to search
            DoTopDeckSearch(intval($player), $deckSize, fn($cid) => true, 1);
            return;
        }

        case 'SOR_223': { // Don't Get Cocky — "Choose a unit. One at a time, reveal cards from your deck
                          // until you choose to stop or have revealed 7 cards. If the combined cost of the
                          // revealed cards is 7 or less, deal that much to the chosen unit. Put the
                          // revealed cards on the bottom of your deck in a random order." The reveal loop
                          // + running cost is carried across requests in the SOR_223#1 handler param.
            global $playerID;
            $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;   // no unit to choose → fizzle
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit", "SOR_223#0");
            return;
        }

        case 'SOR_058': { // Vigilance — "Choose two, in any order: Discard 6 from an opponent's deck /
                          // Heal 5 from a base / Defeat a unit with ≤3 remaining HP / Give a Shield to a
                          // unit." Sequential OPTIONCHOOSE driver (see SWUQueueModalChoose).
            SWUQueueModalChoose(intval($player), 'SOR_058', ['Discard6', 'Heal5', 'Defeat', 'Shield'], 2);
            return;
        }
        case 'SOR_107': // Command — 2 Exp / friendly deals its power to a non-unique enemy / this→resource / return a unit from discard
            SWUQueueModalChoose(intval($player), 'SOR_107', ['Experience', 'PowerStrike', 'Resource', 'Return'], 2);
            return;
        case 'SOR_155': // Aggression — draw / defeat up to 2 upgrades / ready a ≤3-power unit / deal 4 to a unit
            SWUQueueModalChoose(intval($player), 'SOR_155', ['Draw', 'DefeatUpgrades', 'Ready', 'Deal4'], 2);
            return;
        case 'SOR_203': // Cunning — return a ≤4-power non-leader / +4/+0 this phase / exhaust up to 2 / opponent discards random
            SWUQueueModalChoose(intval($player), 'SOR_203', ['ReturnUnit', 'BuffUnit', 'Exhaust', 'Discard'], 2);
            return;

        case 'SOR_167': { // Force Throw — "Choose a player. That player discards a card from their hand.
                          // Then, if you control a FORCE unit, you may deal damage to a unit equal to the
                          // cost of the discarded card."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "You&Opponent", 1, "Which_player_discards_a_card?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_167#0", 1);
            return;
        }

        case 'SOR_235': { // Galactic Ambition — "Play a non-[Heroism] unit from your hand for free.
                          // Deal damage to your base equal to its cost."
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch("myHand", NonLeaderUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (stripos(CardAspect($o->CardID) ?? '', 'Heroism') !== false) continue; // non-Heroism only
                $targets[] = $mz;
            }
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_non-Heroism_unit_from_your_hand_for_free", "SOR_235#0");
            return;
        }

        case 'SOR_139': { // Force Choke — "Deal 5 damage to a non-Vehicle unit. That unit's controller
                          // draws a card." (The cost reduction lives in $playCostModifiers.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (HasTrait($o->CardID, 'Vehicle')) continue;
                $targets[] = $mz;
            }
            SWUQueueChooseTarget(intval($player), $targets, "Deal_5_damage_to_a_non-Vehicle_unit", "SOR_139#0");
            return;
        }

        case 'SOR_123': { // Recruit — "Search the top 5 of your deck for a unit, reveal it, and draw it."
            DoTopDeckSearch(intval($player), 5, fn($c) => CardType($c) === 'Unit', 1);
            return;
        }

        case 'SOR_104': { // U-Wing Reinforcement — "Search top 10 for up to 3 units, combined cost ≤7, play each free."
            DoTopDeckPlay(intval($player), 10, fn($c) => CardType($c) === 'Unit', 7, 3);
            return;
        }

        case 'SOR_126': { // Resupply — "Put this event into play as a resource."
            global $playerID;
            $playerID = intval($player);
            $mz = _SWUFindDiscardMzID(intval($player), 'SOR_126'); // event is in discard at this point
            if ($mz !== null) SWURampResourceExhausted(intval($player), $mz); // enters exhausted (no "ready" wording)
            return;
        }

        case 'SOR_091': { // The Emperor's Legion — "Return each unit in your discard pile that was
                          // defeated this phase to your hand." The defeated-this-phase multiset is
                          // counted per CardID on the owner (SWU_DEFEATED_CARD_{id}); return up to
                          // that many copies of each from the player's discard (CardID-keyed because
                          // discard UniqueIDs don't survive the serialization boundary; counts do).
            global $playerID;
            $playerID = intval($player);
            $discard  = GetDiscard(intval($player));
            $remaining = [];  // cardID → how many still to return this resolution
            $toReturn  = [];
            for ($i = 0; $i < count($discard); $i++) {
                $o = $discard[$i];
                if (isset($o->removed) && $o->removed) continue;
                $cid = $o->CardID ?? '';
                if (!isset($remaining[$cid])) {
                    $remaining[$cid] = GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . $cid);
                }
                if ($remaining[$cid] > 0) { $toReturn[] = $o; $remaining[$cid]--; }
            }
            foreach ($toReturn as $o) {
                $o->removed = true;
                AddHand(intval($player), CardID:$o->CardID);
            }
            DecisionQueueController::CleanupRemovedCards();
            return;
        }

        case 'SOR_186': { // No Good to Me Dead — "Exhaust a unit. That unit can't ready this round
                          // (including during the regroup phase)." Any unit (already-exhausted is a
                          // legal target — the exhaust no-ops but the can't-ready flag still applies).
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit_(it_can't_ready_this_round)", "SOR_186#0");
            return;
        }

        case 'SOR_175': { // — "Draw 2 cards. Each opponent whose base you've damaged this phase
                          // discards 2 cards from their hand." (2-player: the one opponent.)
            global $playerID;
            $playerID = intval($player);
            DoDrawCard(intval($player), 2);
            $opp = OtherPlayer(intval($player));
            if (GlobalEffectCount(intval($player), 'SWU_DMGBASE_' . $opp) > 0) {
                SWUDiscardCards(intval($player), 2); // makes OtherPlayer($player) discard 2
            }
            return;
        }

        case 'SOR_171': { // Mission Briefing — "Choose a player. They draw 2 cards."
            global $playerID;
            $playerID = intval($player);
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "You&Opponent", 1, "Which_player_draws_2_cards?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_171#0", 1);
            return;
        }

        case 'SOR_152': { // For a Cause I Believe In — "Reveal the top 4 cards of your deck. For each
            // [Heroism] card revealed this way, deal 1 damage to an enemy base. You may discard any of
            // the revealed cards and put the rest back on top of your deck in any order."
            global $playerID;
            $playerID = intval($player);
            $deck = GetDeck(intval($player));
            $ids = [];
            foreach ($deck as $c) {
                if (!empty($c->removed)) continue;
                $ids[] = $c->CardID;
                if (count($ids) >= 4) break;
            }
            if (empty($ids)) return;                       // empty deck → nothing to reveal
            // Reveal publicly, then deal 1 to the enemy base per [Heroism] card revealed.
            $heroism = 0;
            foreach ($ids as $cid) {
                if (strpos(CardAspect($cid) ?? '', 'Heroism') !== false) $heroism++;
            }
            AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . implode(', ', array_map('GameLogCardRef', $ids)));
            if ($heroism > 0) SWUDealDamageToBase($heroism, GetOpponent(intval($player)));
            // Then: discard any of the revealed cards and reorder the rest back on top.
            DecisionQueueController::AddDecision($player, "REVEALARRANGE", implode(',', $ids), 1, "Discard_any_revealed_cards_then_reorder_the_rest_on_top");
            DecisionQueueController::AddDecision($player, "CUSTOM", "REVEALARRANGE_FINALIZE|" . count($ids), 1);
            return;
        }

        case 'SOR_200': { // Spark of Rebellion — "Look at an opponent's hand and discard a card from it."
            global $playerID;
            $playerID = intval($player);
            $targets = SWULookAtOpponentHand(intval($player));   // any card is a valid target
            SWUQueueChooseTarget(intval($player), $targets, "Discard_a_card_from_the_opponent's_hand", "DISCARD_FROM_OPP_HAND");
            return;
        }

        case 'SOR_246': { // You're My Only Hope — "Look at the top card of your deck. You may play it.
            // It costs [5 resources] less. If your base has 5 or less remaining HP, you may play it
            // for free instead." The cost mode (−5 vs free) is decided by base HP in the handler.
            global $playerID;
            $playerID = intval($player);
            $idx = _SWUTopDeckFrontIdx(intval($player));
            if ($idx === -1) return;                       // empty deck → nothing to look at
            $topObj = GetDeck(intval($player))[$idx];
            $topID  = $topObj->CardID;
            // Free if the base has ≤5 remaining HP (always playable); otherwise the −5 discount must be
            // affordable. If neither holds, "Play" is impossible and "Leave" is the only outcome, so skip
            // the prompt entirely (the top card just stays put) rather than offer an unplayable "Play".
            $bases = GetBase(intval($player));
            $free  = !empty($bases)
                     && (intval(CardHp($bases[0]->CardID)) - intval($bases[0]->Damage)) <= 5;
            $canPlay = $free
                       || max(0, SWUComputePlayCost(intval($player), $topObj) - 5)
                          <= SWUResourceCount(intval($player), readyOnly: true);
            if (!$canPlay) return;
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$topID}&Play&Leave", 1, "Play_the_top_card_(costs_5_less,_or_free_if_your_base_has_5_or_less_HP)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_246#0", 1);
            return;
        }

        case 'SOR_218': { // Asteroid Sanctuary — "Exhaust an enemy unit. Give a Shield to a friendly unit that costs 3 or less."
            global $playerID;
            $playerID = intval($player);
            $enemies = array_merge(
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $enemies, 'Exhaust_an_enemy_unit', 'EXHAUST_UNIT');
            $friendlies = [];
            foreach (array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter),
                ZoneSearch('mySpaceArena',  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval(CardCost($o->CardID) ?? 99) <= 3) $friendlies[] = $mz;
            }
            SWUQueueChooseTarget(intval($player), $friendlies, 'Give_a_Shield_to_a_friendly_unit_(cost_3_or_less)', 'GIVE_SHIELD');
            return;
        }

        case 'SOR_221': { // Outmaneuver — "Choose an arena (ground or space). Exhaust each unit in that arena."
            global $playerID;
            $playerID = intval($player);
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1, "Choose_an_arena_to_exhaust");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_221#0", 1);
            return;
        }

        case 'SOR_217': { // Shoot First — "Attack with a unit. It gets +1/+0 for this attack..."
            global $playerID, $gShootFirstPending;
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (!empty($readyUnits)) {
                $gShootFirstPending = true;
                // Mandatory "attack with a unit" → auto-PASSPARAMETER when only 1 ready unit, MZCHOOSE for 2+.
                SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SHOOT_FIRST_ATTACK", 1);
            }
            return;
        }

        case 'SHD_181': // Pillage
            SWUDiscardCards($player, 2);
            return;

        case 'SOR_168': { // Precision Fire — "Attack with a unit. It gains Saboteur for this attack.
            // If it's a TROOPER, it also gets +2/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SOR_168#0");
            return;
        }

        case 'SOR_150': { // Heroic Sacrifice — "Draw a card, then attack with a unit. For this attack,
            // it gets +2/+0 and gains: 'When this unit deals combat damage: Defeat it.'"
            global $playerID;
            $playerID = intval($player);
            DoDrawCard(intval($player), 1);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;   // drew the card, but no unit able to attack
            SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SOR_150#0");
            return;
        }

        case 'SOR_092': { // Overwhelming Barrage — give a friendly unit +2/+2 this phase, then it
            // deals damage equal to its (buffed) power divided among any number of OTHER units.
            global $playerID;
            $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return; // no friendly unit → fizzle
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_buff", "SOR_092#0");
            return;
        }

        case 'SOR_222': // Waylay (reprinted as TWI_226) — "Return a non-leader unit to its owner's hand."
        case 'TWI_226': {
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch('myGroundArena',    NonLeaderUnitFilter),
                ZoneSearch('mySpaceArena',     NonLeaderUnitFilter),
                ZoneSearch('theirGroundArena', NonLeaderUnitFilter),
                ZoneSearch('theirSpaceArena',  NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            if (count($targets) === 1) {
                // Single valid target — auto-bounce (Waylay is mandatory, no "you may").
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'BOUNCE_UNIT', 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_unit_to_return_to_hand');
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'BOUNCE_UNIT', 1);
            }
            return;
        }

        case 'SOR_251':
        case 'SHD_262': { // Confiscate — "Defeat an upgrade." (mandatory, single)
            SWUQueueDefeatUpgrade(intval($player), 'Choose_a_unit_to_defeat_an_upgrade_on', may: false, max: 1);
            return;
        }

        case 'SOR_170': { // Power Failure — "Defeat any number of upgrades on a unit." (min 0)
            SWUQueueDefeatUpgrade(intval($player), 'Choose_a_unit_to_defeat_upgrades_on', may: false, max: 99, min: 0);
            return;
        }

        case 'SOR_199': { // Bamboozle — "Exhaust a unit and return each upgrade on it to its owner's hand."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter),
                ZoneSearch('mySpaceArena',     AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            );
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_199#1', 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_unit_to_exhaust');
                DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_199#1', 1);
            }
            return;
        }

        case 'SOR_169': { // Keep Fighting — "Ready a unit with 3 or less power."
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena",    ["Unit", "Leader Unit"]),
                ZoneSearch("mySpaceArena",     ["Unit", "Leader Unit"]),
                ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]),
                ZoneSearch("theirSpaceArena",  ["Unit", "Leader Unit"])
            ) as $mz) {
                $obj = GetZoneObject($mz);
                if ($obj === null || !empty($obj->removed)) continue;
                if (ObjectCurrentPower($obj) <= 3) $targets[] = $mz;
            }
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, "PASSPARAMETER", $targets[0], 0);
            } else {
                $targetStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_a_unit_to_ready");
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "READY_UNIT", 0);
            return;
        }

        case 'SOR_216': { // Disarm — "Give an enemy unit –4/–0 for this phase."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_an_enemy_unit_to_give_-4/-0');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'APPLY_PHASE_DEBUFF|4|0|SOR_216', 1);
            return;
        }

        case 'SOR_076': { // Make an Opening — "Give a unit –2/–2 for this phase. Heal 2 damage from your base."
            global $playerID;
            $playerID = intval($player);
            OnHealBase(intval($player), intval($player), 2);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter),
                ZoneSearch('mySpaceArena',     AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_unit_to_give_-2/-2');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'APPLY_PHASE_DEBUFF|2|2|SOR_076', 1);
            return;
        }

        case 'TWI_076': { // Death by Droids — "Defeat a unit that costs 3 or less. Create 2 Battle Droid tokens."
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) { SWUCreateUnitTokens(intval($player), 'TWI_T01', 2); return; }
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_that_costs_3_or_less", "TWI_076#0");
            return;
        }

        case 'TWI_088': { // Reprocess — "Choose up to 4 units in your discard pile. Put them on the
                          // bottom of your deck in a random order and create that many Battle Droid tokens."
            global $playerID;
            $playerID = intval($player);
            $specs = [];
            foreach (ZoneSearch('myDiscard', ['Unit', 'Token Unit']) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $specs[] = $mz;
            }
            if (empty($specs)) return; // no units → "that many" = 0, fizzle cleanly
            $max = min(4, count($specs));
            DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
                "0|{$max}|" . implode('&', $specs), 1,
                tooltip: 'Choose_up_to_4_units_to_bottom_of_deck');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_088#0', 1);
            return;
        }

        case 'TWI_222': { // Political Pressure — "Choose an opponent. They may discard a random card
                          // from their hand. If they don't, create 2 Battle Droid tokens."
            $opp = OtherPlayer(intval($player));
            global $playerID;
            $playerID = $opp;
            $oppHand = ZoneSearch('myHand', null); // relative to $playerID = opponent
            if (empty($oppHand)) { SWUCreateUnitTokens(intval($player), 'TWI_T01', 2); return; }
            DecisionQueueController::AddDecision($opp, 'YESNO', '-', 1,
                tooltip: 'Discard_a_random_card_or_the_opponent_creates_2_Battle_Droids?');
            DecisionQueueController::AddDecision($opp, 'CUSTOM', 'TWI_222#0|' . intval($player), 1);
            return;
        }

        case 'TWI_227': { // Prisoner of War — "A friendly unit captures an enemy non-leader, non-Vehicle
                          // unit. If the enemy unit costs less than the friendly unit, create 2 Battle Droid tokens."
            global $playerID;
            $playerID = intval($player);
            $capturers = [];
            foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
                $hasTarget = false;
                foreach (ZoneSearch($theirZone, NonLeaderUnitFilter) as $emz) {
                    $eo = GetZoneObject($emz);
                    if ($eo !== null && empty($eo->removed) && !HasTrait($eo->CardID, 'Vehicle')) { $hasTarget = true; break; }
                }
                if (!$hasTarget) continue;
                foreach (ZoneSearch($myZone, AnyUnitFilter) as $fmz) {
                    $fo = GetZoneObject($fmz);
                    if ($fo !== null && empty($fo->removed)) $capturers[] = $fmz;
                }
            }
            if (empty($capturers)) return; // fizzle — no friendly unit with a valid target
            SWUQueueChooseTarget(intval($player), array_values(array_unique($capturers)),
                'Choose_a_friendly_unit_to_capture_with', 'TWI_227#0');
            return;
        }

        case 'TWI_102': { // Manufactured Soldiers — "Choose one: Create 2 Clone Trooper tokens. /
                          // Create 3 Battle Droid tokens."
            DecisionQueueController::AddDecision(intval($player), 'OPTIONCHOOSE', 'Clones&Droids', 1,
                tooltip: 'Choose_one:_2_Clone_Troopers_or_3_Battle_Droids');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_102#0', 1);
            return;
        }

        case 'TWI_125': { // The Clone Wars — "Pay any number of resources. Create that many Clone Trooper
                          // tokens. Each opponent creates that many Battle Droid tokens."
            $maxX = SWUResourceCount(intval($player), readyOnly: true);
            if ($maxX <= 0) return; // no resources to pay → 0 tokens
            DecisionQueueController::AddDecision(intval($player), 'NUMBERCHOOSE', "0|{$maxX}", 1,
                tooltip: 'Pay_any_number_of_resources_(that_many_Clone_Troopers;_opponent_gets_Battle_Droids)');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_125#0', 1);
            return;
        }

        case 'TWI_190': { // On the Doorstep — "Create 3 Battle Droid tokens and ready them."
            SWUCreateUnitTokens(intval($player), 'TWI_T01', 3, ready: true);
            return;
        }

        case 'TWI_257': { // Private Manufacturing — "Draw 2 cards. If you control no token units, put 2
                          // cards from your hand on the bottom of your deck in any order."
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 2);
            $hasToken = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && strpos(CardType($u->CardID ?? '') ?? '', 'Token') !== false) { $hasToken = true; break; }
            }
            if ($hasToken) return;
            DecisionQueueController::CleanupRemovedCards();
            $hand = array_values(ZoneSearch("myHand"));
            $n = min(2, count($hand));
            if ($n <= 0) return;
            DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "{$n}|{$n}|" . implode('&', $hand), 1,
                tooltip: "Put_2_cards_from_your_hand_on_the_bottom_of_your_deck");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_257#0", 1);
            return;
        }

        case 'TWI_100': { // Petition the Senate — "If you control 3 or more Official units, draw 3 cards."
            global $playerID; $playerID = intval($player);
            $n = 0;
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Official')) $n++;
                }
            }
            if ($n >= 3) DoDrawCard(intval($player), 3);
            return;
        }

        case 'TWI_175': { // Strategic Analysis — "Draw 3 cards."
            global $playerID; $playerID = intval($player);
            DoDrawCard(intval($player), 3);
            return;
        }

        case 'TWI_188': { // Wartime Profiteering — "Look at cards from the top of your deck equal to the
                          // number of units defeated this phase. Draw 1 and put the others on the bottom."
                          // Total defeated this phase = both players' SWU_FRIENDLY_DEFEATED (set per
                          // controller at every unit-defeat site).
            global $playerID; $playerID = intval($player);
            $n = GlobalEffectCount(1, 'SWU_FRIENDLY_DEFEATED') + GlobalEffectCount(2, 'SWU_FRIENDLY_DEFEATED');
            if ($n <= 0) return;
            DoTopDeckSearch(intval($player), $n, fn($c) => true, 1);
            return;
        }

        case 'TWI_189': { // Unnatural Life — "Play a unit that was defeated this phase from your discard
                          // pile. It costs 2 resources less and enters play ready. At the start of the
                          // regroup phase, defeat it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)
                    && GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . ($o->CardID ?? '')) > 0) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_defeated_this_phase_(-2,_ready,_defeated_at_regroup)", "TWI_189#0");
            return;
        }

        case 'TWI_225': { // Now There Are Two of Them — "If you control exactly one unit, play a non-Vehicle
                          // unit from your hand that shares a Trait with the unit you control. It costs 5
                          // resources less."
            global $playerID; $playerID = intval($player);
            $mine = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $mine[] = $o; }
            }
            if (count($mine) !== 1) return; // exactly one unit
            $refTraits = array_filter(array_map('trim', explode(',', CardTrait($mine[0]->CardID ?? '') ?? '')));
            $handUnits = [];
            DecisionQueueController::CleanupRemovedCards();
            foreach (array_values(ZoneSearch('myHand')) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                $cid = $o->CardID ?? '';
                if (strpos(CardType($cid) ?? '', 'Unit') === false || HasTrait($cid, 'Vehicle')) continue; // non-Vehicle unit
                $shares = false;
                foreach ($refTraits as $t) { if ($t !== '' && HasTrait($cid, $t)) { $shares = true; break; } }
                if ($shares) $handUnits[] = $mz;
            }
            if (empty($handUnits)) return;
            SWUQueueChooseTarget(intval($player), $handUnits, "Play_a_trait-sharing_non-Vehicle_unit_(-5)", "TWI_225#0");
            return;
        }

        case 'TWI_123': { // Outflank — "Attack with 2 units (one at a time)." (Reprint of SHD_128.)
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                $arr = GetZone($z);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                    $units[] = "{$z}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_the_first_unit_to_attack_with", "SHD_128#0");
            return;
        }

        case 'TWI_127': { // Resupply — "Put this event into play as a resource."
            global $playerID; $playerID = intval($player);
            $mz = _SWUFindDiscardMzID(intval($player), 'TWI_127'); // the event is in discard now
            if ($mz !== null) SWURampResourceExhausted(intval($player), $mz);
            return;
        }

        case 'TWI_176': { // Caught in the Crossfire — "Choose 2 enemy units in the same arena. Each of
                          // those units deals damage equal to its power to the other."
            global $playerID; $playerID = intval($player);
            $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            if (count($enemies) < 2) return;
            SWUQueueChooseTarget(intval($player), $enemies, "Choose_the_first_enemy_unit", "TWI_176#0");
            return;
        }

        case 'TWI_204': { // Impropriety Among Thieves — "Choose a ready non-leader unit controlled by each
                          // player. If you do, each player takes control of the chosen unit controlled by
                          // the player to their right. At the start of the regroup phase, each player
                          // takes control of each unit they own that was chosen." In 2P, "the player to
                          // their right" is the opponent, so this is a control SWAP (temporary until
                          // regroup). The caster chooses one ready non-leader unit for EACH player.
            global $playerID; $playerID = intval($player);
            $mine = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !IsLeaderUnit($o)) $mine[] = $mz;
                }
            }
            $theirs = [];
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !IsLeaderUnit($o)) $theirs[] = $mz;
                }
            }
            // "If you do" — both players must have a valid ready non-leader unit, else the swap fizzles.
            if (empty($mine) || empty($theirs)) return;
            SWUQueueChooseTarget(intval($player), $mine, "Choose_your_ready_non-leader_unit", "TWI_204#0");
            return;
        }

        case 'TWI_040': { // A Fine Addition — "If an enemy unit was defeated this phase, play an upgrade
                          // from your hand or from any player's discard pile, ignoring its aspect penalty."
            global $playerID; $playerID = intval($player);
            // Condition: you defeated an enemy unit this phase (SWU_ENEMY_DEFEATED, cleared at RGS).
            if (GlobalEffectCount(intval($player), 'SWU_ENEMY_DEFEATED') <= 0) return; // condition unmet → fizzle
            // A Fine Addition is still a removed-but-uncompacted entry in its caster's hand right now, so
            // compact first — else a hand candidate's myHand-N index is offset by the stale slot (LOF_150/
            // SOR_167 gotcha) and the chosen mzID resolves to the wrong card.
            DecisionQueueController::CleanupRemovedCards();
            // Collect playable upgrade/pilot candidates from hand + both discard piles (each with a valid
            // host and affordable with the aspect penalty waived). Pilots qualify — A Fine Addition plays
            // from a KNOWN zone (no "search for an upgrade" clause that pilots can't be found by), so a
            // Piloting card can be played as an upgrade here (user-confirmed ruling; unlike Reforge).
            $cands = _SWUTwi040Candidates(intval($player));
            if (empty($cands)) return; // nothing playable → fizzle
            // "may" pick which upgrade (or decline). Attach happens via _SWUFinalizeUpgradeAttach (a DIRECT
            // attach path — it does NOT route through SWUBeginPlayCard/ActivateCard, so the old nested-play
            // no-op doesn't apply). The event's FINISH_PLAY_CARD owns the After Action (suppressed below).
            SWUQueueMayChooseTarget(intval($player), $cands,
                "Play_an_upgrade_(A_Fine_Addition)?", "Choose_an_upgrade_to_play", "TWI_040#0");
            return;
        }

        case 'TWI_089': { // Consolidation of Power — "Choose any number of friendly units. You may play a
                          // unit from your hand if its cost is less than or equal to the combined power of
                          // the chosen units for free. Then, defeat the chosen units."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($friendly)) return;
            $max = count($friendly);
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $friendly), 1,
                tooltip:"Choose_any_number_of_friendly_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "TWI_089#0", 1);
            return;
        }

        case 'TWI_201': { // Aid from the Innocent — "Search the top 10 cards of your deck for 2 Heroism
                          // non-unit cards and discard them. (Put the other cards on the bottom of your
                          // deck in a random order.)"
            global $playerID; $playerID = intval($player);
            _topDeckSearchBegin(intval($player), 10,
                fn($c) => strpos(CardType($c) ?? '', 'Unit') === false
                          && strpos(CardAspect($c) ?? '', 'Heroism') !== false,
                "count:2", "TWI_201_FINALIZE");
            return;
        }

        case 'TWI_199': { // Clear the Field — "Choose a non-leader unit that costs 3 or less. Return it and
                          // each enemy non-leader unit with the same name as it to their owners' hands."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_non-leader_unit_costing_3_or_less", "TWI_199#0");
            return;
        }

        case 'TWI_223': { // Unmasking the Conspiracy — "Discard a card from your hand. If you do, look at
                          // an opponent's hand and discard a card from it."
            global $playerID; $playerID = intval($player);
            DecisionQueueController::CleanupRemovedCards();
            $hand = [];
            $excluded = false;
            foreach (array_values(ZoneSearch("myHand")) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (!$excluded && ($o->CardID ?? '') === $cardID) { $excluded = true; continue; } // exclude this event
                $hand[] = $mz;
            }
            if (empty($hand)) return;
            SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand", "TWI_223#0");
            return;
        }

        case 'TWI_249': { // Heroes on Both Sides — "Choose up to 1 Republic unit and up to 1 Separatist
                          // unit. Give each chosen unit +2/+2 and Saboteur for this phase."
            global $playerID; $playerID = intval($player);
            $rep = []; $sep = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    if (HasTrait($o->CardID ?? '', 'Republic')) $rep[] = $mz;
                    if (HasTrait($o->CardID ?? '', 'Separatist')) $sep[] = $mz;
                }
            }
            if (!empty($rep)) SWUQueueMayChooseTarget(intval($player), $rep, "Give_a_Republic_unit_+2/+2_and_Saboteur?", "Choose_a_Republic_unit", "TWI_249B|" . implode('&', $sep));
            elseif (!empty($sep)) SWUQueueMayChooseTarget(intval($player), $sep, "Give_a_Separatist_unit_+2/+2_and_Saboteur?", "Choose_a_Separatist_unit", "TWI_249C");
            return;
        }

        case 'TWI_250': { // Sword and Shield Maneuver — "Give each friendly Trooper unit Raid 1 for this
                          // phase. Give each friendly Jedi unit Sentinel for this phase."
            global $playerID; $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    if (HasTrait($o->CardID ?? '', 'Trooper')) AddTurnEffect($mz, SWUMakeTurnEffect('RAID', [1], SWU_DUR_PHASE, 'TWI_250'));
                    if (HasTrait($o->CardID ?? '', 'Jedi')) AddTurnEffect($mz, 'SENTINEL');
                }
            }
            return;
        }

        case 'TWI_200': { // Creative Thinking — "Exhaust a non-unique unit. Create a Clone Trooper token."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !CardUnique($o->CardID ?? '')) $targets[] = $mz;
                }
            }
            if (!empty($targets)) SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_non-unique_unit", "EXHAUST_UNIT");
            SWUCreateUnitToken(intval($player), 'TWI_T02'); // create a Clone Trooper (unconditional)
            return;
        }

        case 'TWI_221': { // In Pursuit — "Exhaust a friendly unit. If you do, exhaust an enemy unit."
            global $playerID; $playerID = intval($player);
            $friendly = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $friendly[] = $mz;
                }
            }
            if (empty($friendly)) return; // no ready friendly unit → nothing happens
            SWUQueueChooseTarget(intval($player), $friendly, "Exhaust_a_friendly_unit", "TWI_221#0");
            return;
        }

        case 'TWI_077': { // Vanquish — "Defeat a non-leader unit."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit", "DEFEAT_UNIT");
            return;
        }

        case 'TWI_041': { // Lethal Crackdown — "Defeat a non-leader unit. Deal damage to your base equal
                          // to that unit's power." (Snapshot power before defeat, in the continuation.)
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_(deal_its_power_to_your_base)", "TWI_041#0");
            return;
        }

        case 'TWI_140': { // Self-Destruct — "Defeat a friendly unit. If you do, deal 4 damage to a unit."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter));
            if (empty($friendly)) return; // no friendly unit → no defeat, no damage
            SWUQueueChooseTarget(intval($player), $friendly, "Defeat_a_friendly_unit", "TWI_140#0");
            return;
        }

        case 'TWI_238': { // Merciless Contest — "Each player chooses a non-leader unit they control.
                          // Defeat those units." Caster picks + defeats one; opponent picks + defeats one.
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter));
            if (!empty($mine)) SWUQueueChooseTarget(intval($player), $mine, "Choose_your_non-leader_unit_to_defeat", "DEFEAT_UNIT");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "OPP_DEFEAT_OWN_UNIT|1", 1);
            return;
        }

        case 'TWI_073': { // Grievous Reassembly — "Heal 3 damage from a unit. Create a Battle Droid token."
            global $playerID;
            $playerID = intval($player);
            // Collect heal targets BEFORE creating the token (so the new token isn't offered).
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (!empty($targets)) SWUQueueChooseTarget(intval($player), $targets, "Heal_3_damage_from_a_unit", "HEAL_TARGET|3");
            SWUCreateUnitToken(intval($player), 'TWI_T01'); // Battle Droid (unconditional)
            return;
        }

        case 'TWI_129': { // In Defense of Kamino — "For this phase, each friendly Republic unit gains
                          // Restore 2 and: 'When Defeated: Create a Clone Trooper token.'" One marker per
                          // unit: registry row grants Restore 2; CollectWhenDefeatedTriggers reads it too.
            global $playerID;
            $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Republic')) AddTurnEffect($mz, 'TWI_129');
                }
            }
            return;
        }

        case 'TWI_239': { // Execute Order 66 — "Deal 6 damage to each Jedi unit. For each unit defeated
                          // this way, its controller creates a Clone Trooper token."
            global $playerID;
            $playerID = intval($player);
            // Snapshot each Jedi unit's UID + controller before dealing damage.
            $jedi = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Jedi')) {
                        $jedi[] = ['uid' => intval($o->UniqueID ?? 0), 'ctrl' => intval($o->Controller ?? 0)];
                    }
                }
            }
            foreach ($jedi as $j) { $mz = SWUFindMzByUID($j['uid']); if ($mz !== null) SWUDealDamageToUnit($mz, 6, intval($player)); }
            // For each snapshotted Jedi now gone (defeated by the 6), its controller creates a Clone Trooper.
            foreach ($jedi as $j) {
                if (SWUFindMzByUID($j['uid']) === null && $j['ctrl'] > 0) SWUCreateUnitToken($j['ctrl'], 'TWI_T02');
            }
            return;
        }

        case 'TWI_173': { // Blood Sport — "Deal 2 damage to each ground unit." (AoE, UID-snapshot.)
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            foreach (['myGroundArena', 'theirGroundArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
                }
            }
            foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player)); }
            return;
        }

        case 'TWI_174': { // Open Fire — "Deal 4 damage to a unit."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_4_damage_to_a_unit", "DEAL_UNIT_DAMAGE|4");
            return;
        }

        case 'TWI_177': { // Guerilla Insurgency — "Each player defeats a resource they control and discards
                          // 2 cards from their hand. Deal 4 damage to each ground unit." (AoE is independent
                          // of the discards, so order among them is immaterial.)
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            // 1. Each player defeats a resource they control (fungible → auto-pick the first).
            foreach ([intval($player), $opp] as $p) {
                $playerID = $p;
                $res = ZoneSearch("myResources", null);
                if (!empty($res)) SWUDefeatResource($p, $res[0]);
            }
            // 2. Each player discards 2 (opponent via the standard helper; caster inline, excluding the
            //    just-played event that still lingers in the caster's hand).
            SWUDiscardCards(intval($player), 2); // makes the opponent discard 2
            $playerID = intval($player);
            $casterCards = [];
            $excluded = false;
            foreach (array_values(ZoneSearch("myHand")) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (!$excluded && ($o->CardID ?? '') === $cardID) { $excluded = true; continue; } // skip the event itself
                $casterCards[] = $o;
            }
            if (count($casterCards) <= 2) {
                foreach ($casterCards as $o) { $o->Remove(); SWUAddToDiscard(intval($player), $o->CardID, 'HAND'); }
            } else {
                for ($n = 0; $n < 2; $n++) {
                    DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", "myHand", 1, tooltip: "Choose_card_to_discard");
                    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DISCARD_FROM_OWN_HAND|" . intval($player), 1);
                }
            }
            // 3. Deal 4 to each ground unit (both players; UID-snapshot).
            $playerID = intval($player);
            $uids = [];
            foreach (['myGroundArena', 'theirGroundArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
                }
            }
            foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 4, intval($player)); }
            return;
        }

        case 'TWI_170': { // Daring Raid — "Deal 2 damage to a unit or base."
            global $playerID;
            $playerID = intval($player);
            $targets = _SWUAllUnitsAndBases(intval($player));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit_or_base", "DEAL_TARGET|2");
            return;
        }

        case 'TWI_171': { // Grenade Strike — "Deal 2 damage to a unit. You may deal 1 damage to another
                          // unit in the same arena."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit", "TWI_171#0");
            return;
        }

        case 'TWI_156': { // Unlimited Power — "Deal 4 damage to a unit, 3 to a second, 2 to a third, and 1
                          // to a fourth. (All simultaneously.)" Chain 4 ordered picks (each excluding the
                          // already-chosen), accumulate uid:amount, then apply all at once via SWUDealSplitDamage.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_4_damage_to_a_unit", "TWI_156#0|4|3,2,1|");
            return;
        }

        case 'TWI_099': { // Synchronized Strike — "Deal damage to an enemy unit equal to the number of
                          // units you control in its arena." (Amount computed at resolution.)
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_damage_equal_to_your_units_in_its_arena", "TWI_099#0");
            return;
        }

        case 'TWI_103': { // Pyrrhic Assault — "For this phase, each friendly unit gains: 'When Defeated:
                          // Deal 2 damage to an enemy unit.'" Snapshot friendly units in play now and mark
                          // each with the phase-duration TWI_103 grant (read in CollectWhenDefeatedTriggers).
            global $playerID;
            $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'TWI_103');
                }
            }
            return;
        }

        case 'TWI_075': { // Disruptive Burst — "Give each enemy unit -1/-1 for this phase."
            global $playerID;
            $playerID = intval($player);
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($mz, 1, 1, 'TWI_075');
                }
            }
            return;
        }

        case 'TWI_072': { // I Have the High Ground — "Choose a friendly unit. Each enemy unit gets -4/-0
                          // while attacking that unit this phase." (Marker read in SWUCombatDamage.)
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_friendly_unit_(enemies_attacking_it_get_-4/-0)", "TWI_072#0");
            return;
        }

        case 'TWI_052': { // Hello There — "Choose a unit that entered play this phase. It gets -4/-4 for
                          // this phase." (SWU_PLAYED_UNIT_{uid} marks units that entered this phase.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    $ctrl = intval($o->Controller ?? 0);
                    if ($ctrl > 0 && GlobalEffectCount($ctrl, 'SWU_PLAYED_UNIT_' . intval($o->UniqueID ?? -1)) > 0) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_that_entered_this_phase_-4/-4", "APPLY_PHASE_DEBUFF|4|4|TWI_052");
            return;
        }

        case 'TWI_055': { // Equalize — "Give a unit -2/-2 for this phase. Then, if you control fewer units
                          // than that unit's controller, give another unit -2/-2 for this phase."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-2/-2_for_this_phase", "TWI_055#0");
            return;
        }

        case 'TWI_224': { // Breaking In — "Attack with a unit. It gets +2/+0 and gains Saboteur for
                          // this attack."
            global $playerID;
            $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u !== null && empty($u->removed) && intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+2/+0_and_Saboteur_this_attack)", "TWI_224#0");
            return;
        }

        case 'TWI_153': { // Bold Resistance — "Choose up to 3 units that share the same Trait. Each of
                          // those units gets +2/+0 for this phase."
            global $playerID;
            $playerID = intval($player);
            $all = array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            );
            if (empty($all)) return;
            DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
                "0|3|" . implode('&', $all), 1, tooltip: 'Choose_up_to_3_units_sharing_a_Trait_(+2/+0_this_phase)');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_153#0', 1);
            return;
        }

        case 'TWI_172': { // Grim Resolve — "Attack with a non-leader unit. It gains Grit for this attack."
            global $playerID;
            $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u !== null && empty($u->removed) && intval($u->Status) === 1 && !IsLeaderUnit($u)) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_non-leader_unit_(it_gains_Grit_for_this_attack)", "TWI_172#0");
            return;
        }

        case 'TWI_126': { // Encouraging Leadership — "Give each friendly unit +1/+1 for this phase."
            global $playerID;
            $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($mz, 1, 1, 'TWI_126');
                }
            }
            return;
        }

        case 'TWI_139': { // Corner the Prey — "Attack with a unit. It gets +1/+0 for this attack for each
                          // damage on the defender at the start of this attack."
            global $playerID;
            $playerID = intval($player);
            $ready = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u !== null && empty($u->removed) && intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";
                }
            }
            if (empty($ready)) return;
            SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+1/+0_per_damage_on_the_defender)", "TWI_139#0");
            return;
        }

        case 'TWI_074': { // Guarding the Way — "Give a unit Sentinel for this phase. If you have the
                          // initiative, also give that unit +2/+2 for this phase."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_Sentinel_this_phase", "TWI_074#0");
            return;
        }

        case 'TWI_178': { // Planetary Invasion — "Exploit 3. Ready up to 3 units. Each of those units
                          // gets +1/+0 and gains Overwhelm for this phase."
            global $playerID;
            $playerID = intval($player);
            $specs = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $specs[] = $mz;
                }
            }
            if (empty($specs)) return;
            $max = min(3, count($specs));
            DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
                "0|{$max}|" . implode('&', $specs), 1, tooltip: 'Ready_up_to_3_units_(+1/+0_and_Overwhelm_this_phase)');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_178#0', 1);
            return;
        }

        case 'TWI_078': { // The Invasion of Christophsis — "Exploit 4. Choose an opponent. Defeat each
                          // unit that player controls." (2-player: the single opponent.)
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $uids = [];
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? -1);
                }
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            return;
        }

        case 'TWI_237': { // Droid Deployment — "Create 2 Battle Droid tokens."
            SWUCreateUnitTokens(intval($player), 'TWI_T01', 2);
            return;
        }

        case 'TWI_251': { // Drop In — "Create 2 Clone Trooper tokens."
            SWUCreateUnitTokens(intval($player), 'TWI_T02', 2);
            return;
        }

        case 'SOR_124': // Tactical Advantage — "Give a unit +2/+2 for this phase."
        case 'TWI_124': { // reprint of SOR_124 (identical text).
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter),
                ZoneSearch('mySpaceArena',     AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_unit_to_give_+2/+2');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', "APPLY_PHASE_BUFF|2|2|{$cardID}", 1);
            return;
        }

        case 'SOR_106': { // Attack Pattern Delta — "Give a friendly unit +3/+3 for this
            // phase. Give another friendly unit +2/+2 for this phase. Give a third friendly
            // unit +1/+1 for this phase." Each buff goes to a DISTINCT friendly unit; the
            // descending buffs are assigned one at a time via the chained SOR_106 handler.
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter),
                ZoneSearch('mySpaceArena',  AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_friendly_unit_to_give_+3/+3');
            }
            // SOR_106|curPower|curHp|remainingBuffsCSV|excludedMzIDsCSV
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_106#0|3|3|2_2,1_1|', 1);
            return;
        }

        case 'SOR_125': // Prepare for Takeoff — "Search the top 8 cards for up to 2 Vehicle units, draw them."
            DoTopDeckSearch($player, 8,
                fn($c) => HasTrait($c, 'Vehicle') && CardType($c) === 'Unit',
                2
            );
            return;

        case 'SOR_224': { // Change of Heart — "Take control of a non-leader unit. At the start of the regroup phase, its owner takes control of it."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    ['Unit']),
                ZoneSearch('mySpaceArena',     ['Unit']),
                ZoneSearch('theirGroundArena', ['Unit']),
                ZoneSearch('theirSpaceArena',  ['Unit'])
            ));
            file_put_contents('/tmp/swu_debug.txt', "SOR_224 OnPlayEvent: player=$player playerID=$playerID targets=" . implode(',', $targets) . "\n", FILE_APPEND);
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_non-leader_unit_to_take_control_of');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_224#0', 1);
            return;
        }

        case 'SHD_182': { // Bravado — "Ready a ground unit."
            global $playerID;
            $playerID = intval($player);
            $targets = array_values(array_merge(
                ZoneSearch('myGroundArena',    AnyUnitFilter),
                ZoneSearch('theirGroundArena', AnyUnitFilter)
            ));
            if (empty($targets)) return;
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, 'PASSPARAMETER', $targets[0], 1);
            } else {
                DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1,
                    'Choose_a_ground_unit_to_ready');
            }
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'READY_UNIT', 1);
            return;
        }

        case 'SOR_103': { // Rebel Assault — "Attack with a Rebel unit. It gets +1/+0 for this attack.
                          //   Then, attack with another Rebel unit. It gets +1/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $rebels = array_values(array_filter(array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter),
                ZoneSearch('mySpaceArena',  AnyUnitFilter)
            ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1 && HasTrait($o->CardID, 'Rebel'); }));
            if (empty($rebels)) return;
            // First attacker → SOR_103 handler (+1/+0, arms the mandatory chained second attack).
            SWUQueueChooseTarget($player, $rebels, 'Attack_with_a_Rebel_unit', 'SOR_103#0');
            return;
        }

        case 'SOR_075': { // It Binds All Things — "Heal up to 3 damage from a unit. If you control a
            // FORCE unit, you may deal that much damage to another unit." The actual healed amount is
            // captured in the follow-up and carried to the optional deal.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget($player, $targets, "Heal_up_to_3_damage_from_a_unit", "SOR_075#0");
            return;
        }

        case 'SOR_055': { // The Force Is With Me — "Choose a friendly unit and give 2 Experience tokens
            // to it. If you control a FORCE unit, also give a Shield token to it. You may attack with
            // the chosen unit."
            global $playerID;
            $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return;
            SWUQueueChooseTarget($player, $friendly, "Choose_a_friendly_unit", "SOR_055#0");
            return;
        }

        case 'SOR_172': // Open Fire — "Deal 4 damage to a unit."
            $targets = implode("&", array_filter(array_merge(
                ZoneSearch("myGroundArena",    ["Unit", "Leader Unit"]),
                ZoneSearch("mySpaceArena",     ["Unit", "Leader Unit"]),
                ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"]),
                ZoneSearch("theirSpaceArena",  ["Unit", "Leader Unit"])
            )));
            if ($targets === '') return;
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targets, 1, "Choose_a_unit_to_deal_4_damage");
            DecisionQueueController::AddDecision($player, "CUSTOM", "DEAL_UNIT_DAMAGE|4", 1);
            return;

        case 'SOR_077': { // Takedown — "Defeat a unit with 5 or less remaining HP."
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 5) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_with_5_or_less_remaining_HP", "DEFEAT_UNIT");
            return;
        }

        case 'SOR_078': { // Vanquish — "Defeat a non-leader unit." (["Unit","Token Unit"] excludes leader units.)
            $targets = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit", "DEFEAT_UNIT");
            return;
        }

        case 'SHD_078': { // Fell the Dragon — "Defeat a non-leader unit with 5 or more power."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) >= 5) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_with_5+_power", "DEFEAT_UNIT");
            return;
        }

        case 'SHD_054': { // Midnight Repairs — "Heal up to 8 total damage from any number of units."
            $specs = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o === null || !empty($o->removed)) continue;
                    $dmg = intval($o->Damage ?? 0);
                    if ($dmg > 0) $specs[] = "{$mz}:{$dmg}";
                }
            }
            if (empty($specs)) return;
            DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "8|" . implode("&", $specs) . "|UPTO", 1, tooltip:"Heal_up_to_8_damage_among_units");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SHD_054#0", 1);
            return;
        }

        case 'SHD_079': { // Rival's Fall — "Defeat a unit." (any unit, incl. deployed leaders)
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit", "DEFEAT_UNIT");
            return;
        }

        case 'SHD_108': { // Enforced Loyalty — "Defeat a friendly unit. If you do, draw 2 cards."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return;   // no friendly unit → no defeat, no draw
            SWUQueueChooseTarget(intval($player), $friendly, "Defeat_a_friendly_unit", "SHD_108#0");
            return;
        }

        case 'SHD_231': { // Surprise Strike — "Attack with a unit. It gets +3/+0 for this attack."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Attack_with_a_unit_(+3/+0)", "SHD_231#0");
            return;
        }

        case 'SHD_244': { // No Bargain — "Each opponent discards a card from their hand. Draw a card."
            SWUDiscardCards(intval($player), 1);   // makes OtherPlayer discard 1
            DoDrawCard(intval($player), 1);
            return;
        }

        case 'SHD_093': { // Remnant Reserves — "Search the top 5 cards of your deck for up to 3 units,
                          // reveal them, and draw them."
            DoTopDeckSearch(intval($player), 5, function($cid) { return strpos(CardType($cid) ?? '', 'Unit') !== false; }, 3);
            return;
        }

        case 'SHD_105': { // Spark of Hope — "Choose a unit in your discard pile. If it was defeated this
                          // phase, put it into play as a resource."
            $targets = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)
                    && GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . ($o->CardID ?? '')) > 0) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Put_a_unit_defeated_this_phase_into_play_as_a_resource", "SHD_105#0");
            return;
        }

        case 'SHD_130': { // Moment of Glory — "Give a unit +4/+4 for this phase."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+4/+4_this_phase", "APPLY_PHASE_BUFF|4|4|SHD_130");
            return;
        }

        case 'SHD_051': { // Mystic Reflection — "Give an enemy unit -2/-0 for this phase. If you control a
                          // Force unit, give the enemy unit -2/-2 for this phase instead."
            global $playerID; $playerID = intval($player);
            $enemies = [];
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $enemies[] = $mz;
                }
            }
            if (empty($enemies)) return;
            $hasForce = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && HasTrait($u->CardID ?? '', 'Force')) { $hasForce = true; break; }
            }
            $hp = $hasForce ? 2 : 0;
            SWUQueueChooseTarget(intval($player), $enemies, "Give_an_enemy_unit_-2/-{$hp}_this_phase", "APPLY_PHASE_DEBUFF|2|{$hp}|SHD_051");
            return;
        }

        case 'SHD_128': { // Outflank — "Attack with 2 units (one at a time)."
            global $playerID; $playerID = intval($player);
            $units = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                $arr = GetZone($z);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed) || intval($u->Status) !== 1) continue;
                    $units[] = "{$z}-{$i}";
                }
            }
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_the_first_unit_to_attack_with", "SHD_128#0");
            return;
        }

        case 'SHD_253': { // Wren's Handiwork — "Search the top 8 cards of your deck for up to 2 Mandalorian
                          // and/or upgrade cards, reveal them, and draw them."
            global $playerID; $playerID = intval($player);
            if (count(GetDeck($player)) === 0) return;
            DoTopDeckSearch(intval($player), 8,
                fn($c) => HasTrait($c, 'Mandalorian') || strpos(CardType($c) ?? '', 'Upgrade') !== false, 2);
            return;
        }

        case 'SHD_156': { // Shadowed Undercover — "Draw a card. Each opponent who controls more resources
                          // than you discards a card from their hand."
            DoDrawCard(intval($player), 1);
            $opp = OtherPlayer(intval($player));
            if (SWUResourceCount($opp) > SWUResourceCount(intval($player))) {
                SWUDiscardCards(intval($player), 1);   // makes the opponent discard 1
            }
            return;
        }

        case 'SHD_179': { // Desperate Attack — "Attack with a damaged unit. It gets +2/+0 for this attack."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Attack_with_a_damaged_unit_(+2/+0)", "SHD_179#0");
            return;
        }

        case 'SHD_207': { // A New Adventure — "Return a non-leader unit that costs 6 or less to its owner's
                          // hand. Then, its owner may play it for free."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 6) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_(cost_6_or_less)", "SHD_207#0");
            return;
        }

        case 'SHD_077': { // Take Control — "Take control of an upgrade that costs 3 or less and attach it to
                          // an eligible unit of your choice." (generic move-upgrade seam, cost≤3 filter)
            SWUQueueMoveUpgrade(intval($player), 'cost:3', "Take_control_of_an_upgrade_(cost_3_or_less)");
            return;
        }

        case 'SHD_094': { // Palpatine's Return — "Play a unit from your discard pile. It costs 6 less. If it's
                          // a Force unit, it costs 8 less instead."
            $targets = ZoneSearch("myDiscard", ["Unit"]);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_from_your_discard_(6_less,_8_if_Force)", "SHD_094#0");
            return;
        }

        case 'SHD_206': { // Spare the Target — "Return an enemy non-leader unit to its owner's hand. Collect
                          // that unit's Bounties."
            $targets = array_merge(
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_an_enemy_unit_and_collect_its_Bounties", "SHD_206#0");
            return;
        }

        case 'SHD_233': { // Evacuate — "Return each non-leader unit to its owner's hand." (mass bounce, UID-safe)
            $uids = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
                }
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUBounceUnit(intval($player), $mz);
            }
            return;
        }

        case 'SHD_243': { // Altering the Deal — "Discard a captured card guarded by a friendly unit."
            [$tempMZs, $entries] = _SWUStageFriendlyCaptives(intval($player));
            if (empty($entries)) return;
            SWUQueueChooseTarget(intval($player), $tempMZs, "Discard_a_captured_card", "SHD_243#0|" . implode(",", $entries));
            return;
        }

        case 'SHD_180': { // Detention Block Rescue — "Deal 3 damage to a unit. If that unit is guarding any
                          // captured cards, deal 6 damage instead."
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_3_(6_if_guarding_captives)_to_a_unit", "SHD_180#0");
            return;
        }

        case 'SHD_076': { // Unexpected Escape — "Exhaust a unit. You may rescue a captured card guarded by
                          // that unit."
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit", "SHD_076#0");
            return;
        }

        case 'SHD_227': { // Look the Other Way — "Exhaust a unit unless its controller pays 2 resources."
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_(exhaust_unless_controller_pays_2)", "SHD_227#0|{$player}");
            return;
        }

        case 'SHD_232': { // Relentless Pursuit — "Choose a friendly unit. It captures an enemy non-leader
                          // unit that costs the same as or less than it. If the friendly unit is a Bounty
                          // Hunter, give a Shield token to it."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_capture_with", "SHD_232#0");
            return;
        }

        case 'SHD_039': { // Calculated Lethality — "Defeat a non-leader unit that costs 3 or less. For each
                          // upgrade that was on that unit, give an Experience token to a friendly unit."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_costing_3_or_less", "SHD_039#0");
            return;
        }

        case 'SHD_178': { // Daring Raid — "Deal 2 damage to a unit or base."
            $targets = _SWUAllUnitsAndBases(intval($player));
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_a_unit_or_base", "DEAL_TARGET|2");
            return;
        }

        case 'SHD_229': { // Ma Klounkee — "Return a friendly non-leader Underworld unit to its owner's hand.
                          // If you do, deal 3 damage to a unit."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Underworld')) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;   // no friendly Underworld unit → no return, no damage
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_friendly_Underworld_unit_to_hand", "SHD_229#0");
            return;
        }

        case 'SHD_159': { // The Chaos of War — deal each player's hand-count to that player's own base.
            DecisionQueueController::CleanupRemovedCards();  // compact the just-played event out of the caster's hand
            $opp     = OtherPlayer(intval($player));
            $myHand  = count(GetHand(intval($player)));   // now excludes this event
            $oppHand = count(GetHand($opp));
            if ($myHand  > 0) SWUDealDamageToBase($myHand,  intval($player));
            if ($oppHand > 0) SWUDealDamageToBase($oppHand, $opp);
            return;
        }

        case 'SOR_173': // Bombing Run — "Choose an arena. Deal 3 to each unit in that arena."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1, "Choose_an_arena_to_bomb");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_173#0", 1);
            return;

        case 'SOR_127': { // Strike True — "A friendly unit deals damage equal to its power to an enemy unit."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return; // needs both a dealer and a target
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_your_unit_to_deal_damage", "SOR_127#0");
            return;
        }

        case 'SOR_073': { // Moment of Peace — "Give a Shield token to a unit."
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_unit", "GIVE_SHIELD");
            return;
        }

        case 'SOR_074': { // Repair — "Heal 3 damage from a unit or base." Bases ARE valid
            // MZCHOOSE targets via myBase-0 / theirBase-0 (GetZone recognizes those zones).
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter),
                ["myBase-0", "theirBase-0"]
            );
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Heal_3_from_a_unit_or_base");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HEAL_TARGET|3", 1);
            return;
        }

        case 'SOR_154': // Rallying Cry — "Each friendly unit gains Raid 2 this phase."
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            ) as $mz) {
                AddTurnEffect($mz, "SOR_154");   // CardID token; Raid value 2 comes from the registry, this phase
            }
            return;

        case 'SOR_220': { // Surprise Strike — "Attack with a unit. It gets +3/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if ($u === null || !empty($u->removed)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with_(+3/+0)", "SOR_220#0");
            return;
        }

        case 'SOR_234': { // Maximum Firepower — two friendly Imperial units each deal their power to the same unit.
            global $playerID;
            $playerID = intval($player);
            $imperials = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (HasTrait($o->CardID, 'Imperial')) $imperials[] = $mz;
            }
            if (empty($imperials)) return;
            SWUQueueChooseTarget(intval($player), $imperials, "Choose_first_Imperial_unit_to_deal_damage", "SOR_234#0");
            return;
        }

        case 'SOR_151': { // Karabast — a friendly unit deals (its damage + 1) to an enemy unit.
            global $playerID;
            $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_your_unit", "SOR_151#0");
            return;
        }

        case 'SHD_131': // Take Captive — "A friendly unit captures an enemy non-leader unit in the same arena."
        case 'TWI_128': { // (identical reprint)
            global $playerID;
            $playerID = intval($player);
            // Build the list of friendly units that have at least one valid capture target:
            // an enemy NON-LEADER unit in the SAME arena (ground↔ground, space↔space).
            // Friendly Leader Units are allowed as capturers; captured unit must be non-leader.
            $capturers = [];
            foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
                $enemyNonLeaders = array_values(array_filter(
                    ZoneSearch($theirZone, NonLeaderUnitFilter),
                    function($emz) { $eo = GetZoneObject($emz); return $eo !== null && empty($eo->removed); }
                ));
                if (empty($enemyNonLeaders)) continue;
                foreach (ZoneSearch($myZone, AnyUnitFilter) as $fmz) {
                    $fo = GetZoneObject($fmz);
                    if ($fo === null || !empty($fo->removed)) continue;
                    $capturers[] = $fmz;
                }
            }
            if (empty($capturers)) return;
            SWUQueueChooseTarget(intval($player), array_values(array_unique($capturers)),
                'Choose_a_friendly_unit_to_capture_with', 'SHD_131#0');
            return;
        }

        case 'SOR_252': { // Restock — choose up to 4 cards in a discard pile; bottom of owner's deck (random).
            global $playerID;
            $playerID = intval($player);
            $cards = [];
            $myD = GetDiscard($player);
            for ($i = 0; $i < count($myD); $i++) {
                if ($myD[$i] !== null && empty($myD[$i]->removed)) $cards[] = "myDiscard-{$i}";
            }
            $thD = GetDiscard(GetOpponent($player));
            for ($i = 0; $i < count($thD); $i++) {
                if ($thD[$i] !== null && empty($thD[$i]->removed)) $cards[] = "theirDiscard-{$i}";
            }
            if (empty($cards)) return;
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|4|" . implode("&", $cards), 1, "Choose_up_to_4_cards_for_deck_bottom");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_252#0", 1);
            return;
        }

        default:
            return;
    }
}
