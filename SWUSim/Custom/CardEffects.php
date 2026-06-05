<?php
// SWU event immediate effects.
// Called by ActivateCard when an event is played from hand, after the card
// has been moved to discard. Queue any DQ decisions needed to resolve the effect.
// Non-implemented cards fall through to the default no-op.

function OnPlayEvent(int $player, string $cardID): void {
    switch ($cardID) {

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
            SWUCreateUnitToken($opp, 'JTL_T01', true); // TIE Fighter (Space, 1/1), readied
            SWUCreateUnitToken($opp, 'JTL_T01', true);
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
            SWUCreateUnitToken(intval($player), 'JTL_T02');
            SWUCreateUnitToken(intval($player), 'JTL_T02');
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
            for ($i = 0; $i < $n; $i++) {
                $uid  = NextUniqueID();
                $card = AddSpaceArena(intval($player), CardID: 'JTL_T02', Status: 0, Owner: intval($player),
                    Damage: 0, Controller: intval($player), UniqueID: $uid);
                if ($card !== null) AddTurnEffect($card->GetMzID(), 'JTL_130'); // Sentinel this phase
            }
            return;
        }

        case 'JTL_092': { // Scramble Fighters — create 8 TIE Fighter tokens, readied; they can't attack
                          // bases for this phase (per-token CANT_ATTACK_BASES marker, expires at regroup).
            global $playerID;
            $playerID = intval($player);
            for ($i = 0; $i < 8; $i++) {
                $uid  = NextUniqueID();
                $card = AddSpaceArena(intval($player), CardID: 'JTL_T01', Status: 1, Owner: intval($player),
                    Damage: 0, Controller: intval($player), UniqueID: $uid);
                if ($card !== null) AddTurnEffect($card->GetMzID(), 'CANT_ATTACK_BASES');
            }
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
            if ($mz !== null) SWURampResourceReady(intval($player), $mz);
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
            $topID = GetDeck(intval($player))[$idx]->CardID;
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
