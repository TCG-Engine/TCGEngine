<?php
require_once __DIR__ . "/BotKnowledge.php";
// Decision policies are deliberately explicit: an optional sacrifice and a free draw share
// the same wire format. Never infer benefit merely from MZMAYCHOOSE or from a word in a tooltip.
function GABotDecisionContext($player, $front) {
    $next = false;
    $handler = '';
    foreach (GetDecisionQueue($player) as $entry) {
        if ($entry === null || !empty($entry->removed)) continue;
        if ($entry === $front) { $next = true; continue; }
        if ($next) {
            if (($entry->Type ?? '') === 'CUSTOM') $handler = strval($entry->Param ?? '');
            break;
        }
    }
    return ['tooltip' => strval($front->Tooltip ?? ''), 'param' => strval($front->Param ?? ''), 'handler' => $handler];
}

function GABotChoiceCandidates($player, $param) {
    global $playerID;
    $saved = $playerID;
    $playerID = intval($player);
    try {
        $choices = [];
        foreach (array_filter(explode('&', $param)) as $token) {
            $token = explode(':', $token)[0];
            if (preg_match('/^(my|their)[A-Za-z]+$/', $token)) {
                foreach ((array)GetZone($token) as $i => $obj) {
                    if ($obj !== null && empty($obj->removed)) $choices[] = "$token-$i";
                }
            } else {
                $ability = DecodeOpportunityAbilityChoice($token);
                $resolved = GABotResolveChoiceToken($player, $ability['mzID'] ?? $token);
                if ($resolved !== null && $ability !== null) $resolved = $token;
                if ($resolved !== null) $choices[] = $resolved;
            }
        }
        return array_values(array_unique($choices));
    } finally { $playerID = $saved; }
}

function GABotLiveCount($zone) {
    return count(array_filter((array)$zone, fn($obj) => $obj !== null && empty($obj->removed)));
}

function GABotOwnChampion($player) {
    foreach (GetField($player) as $obj) {
        if ($obj !== null && empty($obj->removed) && PropertyContains(EffectiveCardType($obj), 'CHAMPION')) return $obj;
    }
    return null;
}

function GABotRemainingHP($obj) {
    return $obj === null ? 0 : max(0, intval(ObjectCurrentHP($obj)) - intval($obj->Damage ?? 0));
}

function GABotMaterializeScore($player, $mz, $handler) {
    $obj = GetZoneObject($mz);
    if ($obj === null || !GAMaterialCanLeave($obj)) return -1000;
    if (!CanPlayerUseCardElement($player, $obj->CardID, false, false)) return -1000;
    $champion = PropertyContains(EffectiveCardType($obj), 'CHAMPION');
    if ($champion && !CanMaterializeChampion($player, $obj->CardID)) return -1000;
    $free = $handler === 'MATERIALIZE|NOCOST';
    // These cards have additional costs / special payment paths. Leave them to a dedicated
    // policy instead of committing a sacrifice or repeatedly undoing an unpayable choice.
    if (!$free && in_array($obj->CardID, ['2s08hssegf', '9f92917r84', 'u25fuv184p', 'dW5uyngvJW',
        'uvgflagxbb', 'YTO70fFsBY', 'kINobk9KQA', 'Y34Imzlr0n', 'mDN1CI9IEe'], true)) return -1000;
    if ($obj->CardID === '4nmxqsm4o9' && GABotLiveCount(GetMaterial($player)) > 1) return -1000;
    if (!$free && CardCost_memory($obj->CardID) < 0) return -1000;
    $cost = $free ? 0 : intval(CardMemoryCost($obj));
    if ($cost < 0) return -1000; // X needs its own value policy.
    $available = GABotLiveCount(GetMemory($player));
    foreach (GABotChoiceCandidates($player, GetMaterializeFloatingChoices($player)) as $choice) {
        // Spend graveyard floating memory, not field permanents whose value we haven't priced.
        if (strpos($choice, 'Graveyard-') !== false) ++$available;
    }
    if ($cost > $available) return -1000;
    $score = ($champion ? 100 : 25) - $cost * 5;
    // Establish a weapon before spending a materialization on standalone ammunition.
    if (PropertyContains(EffectiveCardSubtypes($obj), 'GUN')) $score += 15;
    if (PropertyContains(EffectiveCardSubtypes($obj), 'BULLET')) $score -= 10;
    return $score;
}

function GABotCombatContext($player) {
    $attackerMZ = GetCombatAttackerMZ();
    $targetMZ = GetCombatTargetMZ();
    $attacker = $attackerMZ ? GetZoneObject($attackerMZ) : null;
    $target = $targetMZ ? GetZoneObject($targetMZ) : null;
    if ($attacker === null || $target === null || !empty($attacker->removed) || !empty($target->removed)) return null;
    $owner = intval($attacker->Controller);
    return ['attacker' => $attackerMZ, 'target' => $targetMZ, 'owner' => $owner,
        'threat' => GetAttackThreatAmount($attacker, $owner), 'hp' => GABotRemainingHP($target),
        'targetOwner' => intval($target->Controller)];
}

// Only public field state and the bot's own deck size are used by this plan.
function GABotHasSapphirePlan($player) {
    if (!IsGuoJiaBonus($player)) return false;
    foreach (GetField($player) as $obj) {
        if ($obj !== null && empty($obj->removed) && $obj->CardID === 'vzmnt0orxj' && !HasNoAbilities($obj)) return true;
    }
    return false;
}

function GABotGenbuQuestScore($player, $recover = false) {
    if (!GABotHasSapphirePlan($player) || GetQuestCounterCount($player) >= 9) return -1;
    // Prefer recovery when already within a small hit of losing; otherwise accelerate Genbu.
    if ($recover && GABotRemainingHP(GABotOwnChampion($player)) <= 4) return -1;
    return 40;
}

// Read-only recovery estimate; mirror RecoverChampion's public prevention/replacement rules.
function GABotRecoveryAmount($player, $amount) {
    if (GlobalEffectCount($player, 'CANT_RECOVER') > 0) return 0;
    foreach (GetField(3 - $player) as $obj) {
        if ($obj === null || !empty($obj->removed) || HasNoAbilities($obj)) continue;
        if ($obj->CardID === 'koyegh1snb'
            || ($obj->CardID === 'ka5av43ehj' && PlayerLevel($obj->Controller) >= 2)) return 0;
    }
    foreach (array_merge(GetField(1), GetField(2)) as $obj) {
        if ($obj !== null && empty($obj->removed) && $obj->CardID === 'vgWgu1DUYv' && !HasNoAbilities($obj)) {
            $amount = max(0, $amount - 3);
            break;
        }
    }
    if ($amount <= 0) return 0;
    $champion = GABotOwnChampion($player);
    if ($champion !== null && intval($champion->Damage) >= 20) {
        foreach (GetField($player) as $obj) {
            if ($obj !== null && empty($obj->removed) && $obj->CardID === '7qWYuRNoYI' && !HasNoAbilities($obj)) $amount += 2;
        }
    }
    return $amount;
}

// Public draw restrictions used by DoDrawCard. Never consume a draw item for no benefit.
function GABotCanBenefitFromDraw($player) {
    if (GABotLiveCount(GetDeck($player)) <= 1 || GlobalEffectCount($player, 'ri955ygd5v_NO_DRAW') > 0) return false;
    foreach ([1, 2] as $seat) {
        $mandate = false; $uniqueAlly = false;
        foreach (GetField($seat) as $obj) {
            if ($obj === null || !empty($obj->removed)) continue;
            if ($obj->CardID === 'q8sdbzr5zs' && GetTurnNumber() > 1 && DrawTurnCount($player) >= 3) return false;
            if ($obj->CardID === '5ckzgqa186' && !HasNoAbilities($obj)) $mandate = true;
            if (PropertyContains(EffectiveCardType($obj), 'ALLY') && PropertyContains(EffectiveCardType($obj), 'UNIQUE')) $uniqueAlly = true;
        }
        if ($mandate && $uniqueAlly && GABotLiveCount(GetHand($player)) + GABotLiveCount(GetMemory($player)) >= 8) return false;
    }
    return true;
}

function GABotGenbuDefenseScore($player, $source) {
    $combat = GABotCombatContext($player);
    if ($combat === null || $combat['targetOwner'] !== $player
        || GetZoneObject($combat['target']) !== $source
        || DecisionQueueController::GetVariable('CombatDamageAmount') !== null) return -1;
    $bonus = count(ZoneSearch('myGraveyard', cardElements: ['WATER']));
    // Pay three reserve only when the temporary life changes lethal damage to survival.
    return $combat['threat'] >= $combat['hp'] && $combat['threat'] < $combat['hp'] + $bonus ? 120 : -1;
}

function GABotSynchronScore($player, $source) {
    $champion = GABotOwnChampion($player);
    if ($champion === null) return -1;
    $amount = GABotRecoveryAmount($player, 2 + GetCounterCount($source, 'refinement'));
    $healing = min($amount, max(0, intval($champion->Damage)));
    if ($healing <= 0) return -1;
    $combat = GABotCombatContext($player);
    if ($combat !== null && $combat['targetOwner'] === $player
        && GetZoneObject($combat['target']) === $champion
        && DecisionQueueController::GetVariable('CombatDamageAmount') === null
        && $combat['threat'] >= $combat['hp']) {
        return $combat['threat'] < $combat['hp'] + $healing ? 180 : -1;
    }
    // Preserve the item for further refinement rather than waste part of its recovery.
    return $healing === $amount ? 35 : -1;
}

// Return the preferred mill recipient, or null when preserving the Fractal is better.
// Before payment, include this WATER item entering our graveyard in the mill amount.
function GABotPolarDepthsTarget($player, $beforeSacrifice = false) {
    $amount = count(ZoneSearch('myGraveyard', cardElements: ['WATER'])) + ($beforeSacrifice ? 1 : 0);
    if ($amount <= 0) return null;
    $enemySize = GABotLiveCount(GetDeck(3 - $player));
    if ($enemySize > 0 && $enemySize <= $amount) return 'NO';
    if (GABotHasSapphirePlan($player) && GetQuestCounterCount($player) < 9
        && GABotLiveCount(GetDeck($player)) > $amount + 8
        && GlobalEffectCount($player, 'yuo7dbge3b') === 0) {
        foreach (GetField($player) as $obj) {
            if ($obj !== null && empty($obj->removed) && $obj->CardID === 'GRlUlcYRmV'
                && !HasNoAbilities($obj) && IsFostered($obj)) return null;
        }
        return 'YES';
    }
    return null;
}

// Pay only to clear a small graveyard containing reusable resources, or to earn
// the three-reserve draw. The opponent chooses its cards, so do not assume sniping.
function GABotThuriblePayment($player) {
    $grave = array_values(array_filter(GetGraveyard(3 - $player), fn($o) => $o !== null && empty($o->removed)));
    $count = count($grave);
    $spare = max(0, min(GABotLiveCount(GetHand($player)) - 2, CountAvailableReservePayments($player)));
    if ($count === 0 || $spare === 0) return 0;
    if ($count >= 3 && $spare >= 3 && GABotCanBenefitFromDraw($player)) return 3;
    if ($count <= 3 && $count <= $spare) {
        foreach ($grave as $obj) {
            if (HasFloatingMemory($obj) || (EffectiveCardElement($obj) === 'WATER' && IsGuoJiaBonus(3 - $player))) {
                if ($count < 3 || GABotCanBenefitFromDraw($player)) return $count;
            }
        }
    }
    return 0;
}

// Base damage only: use public board state, never unresolved private choices.
function GABotDeclaredDamageEstimate($entry, $target) {
    $controller = intval($entry->Controller);
    switch ($entry->CardID) {
        case 'RIVahUIQVD': return 1 + PlayerLevel($controller); // Fireball
        case '50m1nBduUZ':
            return IsClassBonusActive($controller, ['MAGE'])
                ? (PlayerLevel($controller) >= 6 ? 6 : (PlayerLevel($controller) >= 3 ? 4 : 2)) : 2;
        case 'vjonmi9jjq': return HasStealth($target) ? 4 : 1; // Smoke Out
        case 'ZIU4bH6D9q': return intval($target->Damage ?? 0) > 0 ? 6 : 2;
        case '0op3nq0ymv': return PropertyContains(EffectiveCardType($target), 'ALLY') ? 5 : 3;
    }
    return ['0G32Pp5atK'=>2, '0xm513tj3j'=>4, '145y6KBhxe'=>4][$entry->CardID] ?? null;
}

// Spend the consumable only against understood, publicly declared spell damage.
// Infer likely memory synergies from public champion lineage only.
// Champion families are a matchup hint, not knowledge of the opponent's deck.
function GABotOpponentLikelyWindOrLuxem($player) {
    $champion = GABotOwnChampion(3 - $player);
    if ($champion === null) return false;
    $lineage = array_merge([$champion->CardID], (array)($champion->Subcards ?? []));
    foreach ($lineage as $cardID) {
        if (!PropertyContains(CardType($cardID), 'CHAMPION')) continue;
        $element = CardElement($cardID);
        if (PropertyContains($element, 'WIND') || PropertyContains($element, 'LUXEM')) return true;
        $name = CardName($cardID);
        foreach (['Zander,', 'Guo Jia,', 'Mordred,'] as $family) {
            if (str_starts_with($name, $family)) return true;
        }
    }
    return false;
}

function GABotNullifyingMirrorScore($player) {
    // Memory contents are private: use only its public count, never card identities.
    if (intval(GetTurnPlayer()) === $player
        || !GABotOpponentLikelyWindOrLuxem($player)
        || GABotLiveCount(GetMemory(3 - $player)) === 0
        || GlobalEffectCount($player, 'pol1nz0j1n') > 0
        || GlobalEffectCount($player, '0sVdvpQKXq_MEMORY') > 0) return -1;
    foreach (GetLiveEffectStackEntries() as $entry) {
        if (intval($entry->Controller) === $player && $entry->CardID === 'pol1nz0j1n') return -1;
    }
    return 20;
}

// Spend the one-shot amulet only on understood, publicly targeted champion damage.
function GABotSafeguardScore($player) {
    foreach (GetLiveEffectStackEntries() as $entry) {
        if ($entry->CardID === 'yj2rJBREH8' && intval($entry->Controller) === $player) return -1;
    }
    $champion = GABotOwnChampion($player);
    if ($champion === null) return -1;
    foreach ($champion->TurnEffects ?? [] as $effect) {
        if ($effect === 'yj2rJBREH8' || str_starts_with($effect, 'PREVENT_NONCOMBAT_')) return -1;
    }
    if (IsDistant($champion) && GlobalEffectCount($player, 'isxy5lh23q') > 0) return -1;
    $score = -1;
    foreach (GetLiveEffectStackEntries() as $entry) {
        if (intval($entry->Controller) === $player) continue;
        foreach (GADeclarationData($entry)['primary'] ?? [] as $identity) {
            $ref = GATargetReference($identity);
            if ($ref === null || GetZoneObject($ref) !== $champion) continue;
            $amount = GABotDeclaredDamageEstimate($entry, $champion);
            if ($amount === null || $amount <= 0) continue;
            $hp = GABotRemainingHP($champion);
            if ($hp > 0 && $amount >= $hp && $amount - 4 < $hp) return 110;
            if ($amount >= 2 && $amount < $hp) $score = 35;
        }
    }
    return $score;
}

function GABotFlashGrenadeScore($player) {
    if (GlobalEffectCount($player, 'isxy5lh23q') > 0) return -1;
    foreach (GetLiveEffectStackEntries() as $entry) {
        if ($entry->CardID === 'isxy5lh23q' && intval($entry->Controller) === $player) return -1;
    }
    // Wait for the stack to settle before spending protection on an attack.
    $combat = GABotCombatContext($player);
    if (empty(GetLiveEffectStackEntries()) && $combat !== null
        && $combat['owner'] !== $player && $combat['targetOwner'] === $player
        && DecisionQueueController::GetVariable('CombatDamageAmount') === null) {
        $target = GetZoneObject($combat['target']);
        $protected = false;
        foreach ($target->TurnEffects ?? [] as $effect) {
            if (str_starts_with($effect, 'PREVENT_COMBAT_')) $protected = true;
        }
        if ($target->CardID === 'Zu53izIFTX'
            && IsClassBonusActive($player, CardClasses($target->CardID))) $protected = true;
        if (!$protected && IsDistant($target)
            && (PropertyContains(EffectiveCardType($target), 'ALLY')
                || PropertyContains(EffectiveCardType($target), 'CHAMPION'))) {
            $amount = $combat['threat'];
            $hp = $combat['hp'];
            if ($hp > 0 && $amount >= $hp && $amount - 3 < $hp) return 110;
            if ($amount >= 2 && $amount < $hp) return 35;
        }
    }
    $score = -1;
    foreach (GetLiveEffectStackEntries() as $entry) {
        if (intval($entry->Controller) === $player) continue;
        foreach (GADeclarationData($entry)['primary'] ?? [] as $identity) {
            $ref = GATargetReference($identity);
            $target = $ref === null ? null : GetZoneObject($ref);
            if ($target === null || !empty($target->removed) || intval($target->Controller) !== $player
                || !IsDistant($target)
                || !(PropertyContains(EffectiveCardType($target), 'ALLY')
                    || PropertyContains(EffectiveCardType($target), 'CHAMPION'))) continue;
            foreach ($target->TurnEffects ?? [] as $effect) {
                if (str_starts_with($effect, 'PREVENT_NONCOMBAT_') || $effect === 'yj2rJBREH8') continue 2;
            }
            $amount = GABotDeclaredDamageEstimate($entry, $target);
            if ($amount === null || $amount <= 0) continue;
            $hp = GABotRemainingHP($target);
            if ($hp > 0 && $amount >= $hp && $amount - 3 < $hp) return 110;
            if ($amount >= 2) $score = 35;
        }
    }
    return $score;
}

// Only understood damage spells with publicly declared targets.
// Preserve the last durability unless one prevention would save a unit.
function GABotClarentScore($player, $source) {
    foreach (GetLiveEffectStackEntries() as $entry) {
        if ($entry->CardID === 'm31WVJ9F04' && intval($entry->Controller) === $player) return -1;
    }
    foreach (GetLiveEffectStackEntries() as $entry) {
        if (intval($entry->Controller) === $player) continue;
        foreach (GADeclarationData($entry)['primary'] ?? [] as $identity) {
            $ref = GATargetReference($identity);
            $target = $ref === null ? null : GetZoneObject($ref);
            if ($target === null || intval($target->Controller) !== $player) continue;
            // Existing noncombat protection already covers this response. Do not stack it.
            foreach ($target->TurnEffects ?? [] as $effect) {
                if (str_starts_with($effect, 'PREVENT_NONCOMBAT_') || $effect === 'yj2rJBREH8') continue 2;
            }
            $amount = GABotDeclaredDamageEstimate($entry, $target);
            if ($amount === null || $amount <= 0) continue;
            if (GABotRemainingHP($target) === $amount) return 110;
            if (GetCounterCount($source, 'durability') > 1) return 35;
        }
    }
    return -1;
}

// Spend ready non-unit reserve sources before cards in hand; retain units for combat.
function GABotSpareReserveSources($player, $excluded = null) {
    return array_values(array_filter(GetReservablePaymentSources($player, $excluded), function($mz) {
        $obj = GetZoneObject($mz);
        $type = EffectiveCardType($obj);
        return !PropertyContains($type, 'ALLY') && !PropertyContains($type, 'CHAMPION');
    }));
}

// null means unsupported, negative means understood but undesirable. This distinction makes
// coverage reports useful without labeling a deliberate pass as a missing implementation.
// A narrow Armoire exchange: lower the hand's reserve burden without changing
// omen type/element or losing cost diversity, duplicate-cost bonuses or total cost.
function GABotArmoirePair($player, $omens = null, $hands = null) {
    $omens ??= GetOmenMZIDs($player);
    $hands ??= ZoneSearch('myHand');
    $costs = [];
    foreach (GetOmens($player) as $obj) {
        $cost = intval(EffectiveCardReserveCost($obj));
        $costs[$cost] = ($costs[$cost] ?? 0) + 1;
    }
    // Armoire is banished to activate; it cannot fund the card acquired afterward.
    $reserveSources = array_filter(GABotSpareReserveSources($player),
        fn($mz) => GetZoneObject($mz)->CardID !== 'fm894uc4ij');
    $best = null;
    foreach ($omens as $omen) {
        $take = GetZoneObject($omen);
        if ($take === null || !CanPlayerUseCardElement($player, $take->CardID, false, false)) continue;
        $takeCost = intval(EffectiveCardReserveCost($take));
        if ($takeCost > max(0, GABotLiveCount(GetHand($player)) - 1 + count($reserveSources))) continue;
        foreach ($hands as $hand) {
            $give = GetZoneObject($hand);
            if ($give === null || EffectiveCardType($take) !== EffectiveCardType($give)
                || EffectiveCardElement($take) !== EffectiveCardElement($give)) continue;
            $giveCost = intval(EffectiveCardReserveCost($give));
            $gain = $giveCost - $takeCost;
            if ($gain < 2) continue;
            $after = $costs;
            $after[$takeCost] = ($after[$takeCost] ?? 1) - 1;
            if ($after[$takeCost] <= 0) unset($after[$takeCost]);
            $after[$giveCost] = ($after[$giveCost] ?? 0) + 1;
            if (array_diff(array_keys($costs), array_keys($after))
                || (max($costs ?: [0]) >= 2 && max($after) < 2)) continue;
            if ($best === null || $gain > $best['gain']) $best = compact('omen', 'hand', 'gain');
        }
    }
    return $best;
}

function GABotOptionalScore($player, $answer, $legal) {
    $handler = $legal['handler'] ?? '';
    $tooltip = $legal['tooltip'] ?? '';
    if ($handler === 'MATERIALIZE' || $handler === 'MATERIALIZE|NOCOST') {
        return GABotMaterializeScore($player, $answer, $handler);
    }
    if ($handler === '5fnmnpavo4:0:ActivateAbility-1') return GABotPolarDepthsTarget($player) === 'YES' ? 25 : -1;
    if ($handler === '59ipqa91r2:0:Enter-1') return GABotGenbuQuestScore($player);
    if ($handler === 'enxi6tshtu:0:Enter-1') return GABotGenbuQuestScore($player, true);
    if (in_array($handler, ['thxvlynvy5:0:OnAttack-1', '4mcnqsm3n9:0:CardActivated-1'], true)) {
        // Build deluge in our graveyard, but stop before milling risks running out of cards.
        return GABotHasSapphirePlan($player) && GABotLiveCount(GetDeck($player)) > 8
            && DelugeAmount($player) < 12 ? 20 : -1;
    }
    if ($handler === 'rpOaAjgtue:0:Enter-1') {
        $obj = GetZoneObject($answer);
        if (strpos($answer, 'myGraveyard-') !== 0 || $obj === null || !HasFloatingMemory($obj)
            || GABotLiveCount(GetDeck($player)) <= 1) return -1;
        // Preserve water cards for deluge when a non-water floating card is available.
        return EffectiveCardElement($obj) === 'WATER' ? 25 : 30;
    }
    if ($handler === 'WeaponSelected') {
        $weapon = GetZoneObject($answer);
        if ($weapon === null || !in_array($answer, GetAvailableWeapons($player), true)) return -1;
        // Optional reserve taxes need a separate tradeoff policy.
        if (in_array($weapon->CardID, ['5iqigcom2r', 'chnppup4iz', 'f5ooozsikp', 'pyx8bd7ozu'], true)) return -1;
        return 10 * max(0, intval(ObjectCurrentPower($weapon))) - 1;
    }
    if ($tooltip === 'Take_a_fast_action?') {
        $ability = DecodeOpportunityAbilityChoice($answer);
        if ($ability !== null) {
            $source = GetZoneObject($ability['mzID']);
            // Dynamic slots move when other abilities are granted; identify Enlighten by
            // the engine's current descriptor, never by the champion ID or a fixed slot.
            if ($source !== null) {
                foreach (json_decode(GetDynamicAbilities($source) ?: '[]', true) as $dynamic) {
                    if ($dynamic['name'] === 'LR: Materialize Gun' && intval($dynamic['index']) === $ability['abilityIndex']) {
                        if (!CanActivateAbility($player, $ability['mzID'], $ability['abilityIndex'])) return -1;
                        foreach (GetField($player) as $obj) {
                            if ($obj !== null && empty($obj->removed) && PropertyContains(EffectiveCardSubtypes($obj), 'GUN')) return -1;
                        }
                        foreach (GetMaterial($player) as $i => $obj) {
                            if ($obj !== null && empty($obj->removed) && PropertyContains(EffectiveCardSubtypes($obj), 'GUN')
                                && GABotMaterializeScore($player, 'myMaterial-' . $i, 'MATERIALIZE') > 0) return 40;
                        }
                        return -1;
                    }
                    if ($dynamic['name'] === 'Enlighten' && intval($dynamic['index']) === $ability['abilityIndex']) {
                        return CanActivateAbility($player, $ability['mzID'], $ability['abilityIndex'])
                            && GABotCanBenefitFromDraw($player) ? GABotDrawPriority($player) : -1;
                    }
                }
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'pol1nz0j1n') {
                return CanActivateAbility($player, $ability['mzID'], 0) ? GABotNullifyingMirrorScore($player) : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'yj2rJBREH8') {
                return CanActivateAbility($player, $ability['mzID'], 0) ? GABotSafeguardScore($player) : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'isxy5lh23q') {
                return CanActivateAbility($player, $ability['mzID'], 0) ? GABotFlashGrenadeScore($player) : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'fm894uc4ij') {
                return CanActivateAbility($player, $ability['mzID'], 0)
                    && intval(GetTurnPlayer()) === $player && GetCurrentPhase() === 'MAIN'
                    && empty(GetLiveEffectStackEntries()) && GABotArmoirePair($player) !== null ? 20 : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'mb3iqw3kc6') {
                // Memory draw is delayed value: keep our own turn's resources and
                // two response cards, and do not spend into a pending effect.
                return CanActivateAbility($player, $ability['mzID'], 0)
                    && intval(GetTurnPlayer()) !== $player && empty(GetLiveEffectStackEntries())
                    && GABotLiveCount(GetHand($player)) >= 2
                    && GABotLiveCount(GetHand($player))
                        + count(GABotSpareReserveSources($player, $ability['mzID'])) >= 5
                    && CountAvailableReservePayments($player) >= 3
                    && GABotCanBenefitFromDraw($player) ? 15 : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'DNbIpzVgde') {
                return CanActivateAbility($player, $ability['mzID'], 0) && GABotCanBenefitFromDraw($player)
                    ? GABotDrawPriority($player) : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'LeyUk5auEP') {
                return CanActivateAbility($player, $ability['mzID'], 0) && GABotThuriblePayment($player) > 0 ? 25 : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'm31WVJ9F04') {
                return CanActivateAbility($player, $ability['mzID'], 0) ? GABotClarentScore($player, $source) : -1;
            }
            if ($source !== null && $source->CardID === 'T3cx65VM3D' && $ability['abilityIndex'] === 0) {
                // Deny two cards during our main phase, before our attacks. Cards moved to
                // memory return at recollection, so do not fire just before that refresh.
                return CanActivateAbility($player, $ability['mzID'], 0)
                    && intval(GetTurnPlayer()) === $player && GetCurrentPhase() === 'MAIN'
                    && GABotLiveCount(GetHand(3 - $player)) >= 2 ? 45 : -1;
            }
            if ($source !== null && $source->CardID === '5fnmnpavo4' && $ability['abilityIndex'] === 0) {
                $target = GABotPolarDepthsTarget($player, true);
                return CanActivateAbility($player, $ability['mzID'], 0) && $target !== null
                    ? ($target === 'NO' ? 90 : 25) : -1;
            }
            if ($source !== null && $source->CardID === 'k8bwlx70qj' && $ability['abilityIndex'] === 0) {
                return CanActivateAbility($player, $ability['mzID'], 0) ? GABotGenbuDefenseScore($player, $source) : -1;
            }
            if ($source !== null && $source->CardID === '2gv7DC0KID' && $ability['abilityIndex'] === 0) {
                // Grand Crusader's Ring has no ongoing passive to preserve. Keep one card
                // in the deck for the next mandatory draw; urgent responses score higher.
                return CanActivateAbility($player, $ability['mzID'], 0)
                    && GABotCanBenefitFromDraw($player) ? GABotDrawPriority($player) : -1;
            }
            if ($source !== null && $source->CardID === 'vzmnt0orxj' && $ability['abilityIndex'] === 0) {
                return CanActivateAbility($player, $ability['mzID'], 0) ? 150 : -1;
            }
            if ($source !== null && $source->CardID === 'Tx8noEw78s' && $ability['abilityIndex'] === 0) {
                return CanActivateAbility($player, $ability['mzID'], 0) ? GABotSynchronScore($player, $source) : -1;
            }
            // Starter bullets' REST: Load ability. The opportunity enumerator has checked speed
            // and activation costs; only take this known ability when an unloaded gun exists.
            if ($source !== null && $ability['abilityIndex'] === 0
                && in_array($source->CardID, ['l75tlzsmw3', '4x7e22tk3i', '97n2jnltv5', '3qu7d6sopo'], true)) {
                return CanActivateAbility($player, $ability['mzID'], 0) && !empty(GetUnloadedGuns($player)) ? 30 : -1;
            }
            return null;
        }
        $obj = GetZoneObject($answer);
        if ($obj === null || !CanActivateCardForSelection($player, $obj) || !CanAffordCardActivation($player, $obj)) return -1000;
        $combat = GABotCombatContext($player);
        // Evasive Maneuvers: use prevention when it turns a lethal hit into survival.
        if ($obj->CardID === '1n3gygojwk') {
            // Do not spend a second copy while the first is still resolving, or after damage.
            foreach (GetLiveEffectStackEntries() as $entry) {
                if ($entry->CardID === $obj->CardID && intval($entry->Controller) === $player) return -1;
            }
            if (DecisionQueueController::GetVariable('CombatDamageAmount') !== null) return -1;
            if ($combat !== null) {
                $target = GetZoneObject($combat['target']);
                foreach ($target->TurnEffects ?? [] as $effect) {
                    if (str_starts_with($effect, 'EVASIVE_MANEUVERS_')) {
                        $combat['threat'] -= intval(substr($effect, strlen('EVASIVE_MANEUVERS_')));
                        break;
                    }
                }
            }
            return $combat !== null && $combat['targetOwner'] === $player && $combat['threat'] >= $combat['hp']
                && $combat['threat'] - 2 < $combat['hp'] ? 100 : -1;
        }
        return null;
    }
    if ($handler === 'f15joh300z:0:CardActivated-1') {
        // Umbra Sight's extra draw deals 2 unpreventable damage per curse, including itself.
        $damage = 2 * (CountCursesInLineage($player) + 1);
        return GABotLiveCount(GetDeck($player)) > 1 && GABotRemainingHP(GABotOwnChampion($player)) > $damage + 4 ? 10 : -1;
    }
    if (in_array($tooltip, ['Recover_2?', 'Recover_3?', 'Recover_5?'], true)) {
        $champion = GABotOwnChampion($player);
        return $champion !== null && intval($champion->Damage ?? 0) > 0 ? 10 : -1;
    }
    if ($tooltip === 'Banish_floating_memory_to_draw?') {
        return GABotLiveCount(GetDeck($player)) > 1 && strpos($answer, 'myGraveyard-') === 0 ? 10 : -1;
    }
    if (str_starts_with($handler, 'DianaL2LoadChoice|')) {
        return GABotLiveCount(GetDeck($player)) > 2 ? 15 : -1;
    }
    return null;
}

// Explicit damage policies only: never interpret arbitrary targeting prompts as removal.
function GABotDamageTargetScore($player, $mz, $handler) {
    $parts = explode('|', $handler);
    if ($parts[0] === 'GADeclareTarget') {
        $state = json_decode(base64_decode($parts[1] ?? '', true) ?: '', true);
        if (base64_decode($parts[2] ?? '', true) !== 'primary') return null;
        $card = $state['cardID'] ?? '';
    } else {
        $card = explode(':', $handler)[0];
        if ($handler !== "$card:0:CardActivated-1") return null;
    }
    // Mote Sear (base damage), Voltaic Sphere, and Thunderclap.
    $damage = ['0G32Pp5atK' => 2, '0op3nq0ymv' => 3, '0xm513tj3j' => 4][$card] ?? null;
    if ($damage === null) return null;
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed)) return -100000;
    $ally = PropertyContains(EffectiveCardType($obj), 'ALLY');
    if ($card === '0op3nq0ymv' && $ally) $damage = 5;
    $hp = GABotRemainingHP($obj);
    $lethal = $hp > 0 && $damage >= $hp;
    $score = min($damage, $hp) * 8;
    $score += $ally ? ($lethal ? 65 + max(0, ObjectCurrentPower($obj)) * 12 : -25)
        : ($lethal ? 10000 : 30);
    // Mandatory choices can contain friendly units; prefer every opposing unit to those.
    return intval($obj->Controller) === intval($player) ? -20000 - $score : $score;
}

function GABotRecordUnsupportedDecision($legal) {
    $entry = array_intersect_key($legal, array_flip(['decisionType', 'tooltip', 'handler', 'unsupportedCards']));
    $key = hash('sha256', json_encode($entry));
    if (isset($GLOBALS['GABotUnsupportedOptionalDecisions'][$key])) return;
    $GLOBALS['GABotUnsupportedOptionalDecisions'][$key] = $entry;
    error_log('GABot: unsupported optional policy ' . json_encode($entry));
}

function GABotChooseDecision(array $actions, array $legal) {
    global $playerID;
    $saved = $playerID;
    $player = intval($legal['playerID']);
    $playerID = $player;
    try {
        $type = $legal['decisionType'];
        $handler = $legal['handler'] ?? '';
        if ($type === 'NUMBERCHOOSE' && $handler === 'PurifyingThuriblePay') {
            $bounds = explode('|', $legal['param']);
            $actions[0]['cardID'] = strval(max(intval($bounds[0]), min(intval($bounds[1] ?? 0), GABotThuriblePayment($player))));
            return $actions[0];
        }
        if ($type === 'MZREARRANGE' && $handler === 'GlimpseApply') {
            $actions[0]['cardID'] = GABotGlimpseChoice($player, $legal['param']);
            return $actions[0];
        }
        if ($type === 'MZMULTICHOOSE') {
            $min = $legal['minimum'];
            $max = $legal['maximum'];
            $candidates = $legal['candidates'];
            if ($handler === 'T3cx65VM3D:0:ActivateAbility-1') {
                // Only our own offered hand cards: keep usable, cheap cards available.
                usort($candidates, function($a, $b) use ($player) {
                    $value = function($mz) use ($player) {
                        $obj = GetZoneObject($mz);
                        return (CanPlayerUseCardElement($player, $obj->CardID, false, false) ? 100 : 0)
                            - max(0, intval(CardCost_reserve($obj->CardID)));
                    };
                    return $value($a) <=> $value($b);
                });
                $actions[0]['cardID'] = implode('&', array_slice($candidates, 0, $min)) ?: '-';
            } elseif (str_starts_with($handler, 'PAYFLOATING|')) {
                $candidates = array_values(array_filter($candidates, fn($mz) => strpos($mz, 'Graveyard-') !== false));
                $max = min($max, max(0, intval(explode('|', $handler)[1])));
                $actions[0]['cardID'] = implode('&', array_slice($candidates, 0, $max)) ?: '-';
            } elseif ($min === 0) {
                GABotRecordUnsupportedDecision($legal);
            }
            return $actions[0];
        }
        if ($type === 'MZCHOOSE') {
            $candidates = array_column($actions, 'cardID');
            if ($handler === 'ReserveCard_Process') {
                $spare = array_values(array_intersect(GABotSpareReserveSources($player), $candidates));
                $choice = $spare[0] ?? $candidates[0];
            } elseif (in_array($handler, ['MATERIALIZE', 'MATERIALIZE|NOCOST', 'WeaponSelected'], true) && $candidates[0] !== 'PASS') {
                usort($candidates, fn($a, $b) => GABotOptionalScore($player, $b, $legal) <=> GABotOptionalScore($player, $a, $legal));
                $choice = $candidates[0];
            } elseif ($handler === 'fm894uc4ij:0:ActivateAbility-1') {
                $pair = GABotArmoirePair($player, $candidates);
                $choice = $pair['omen'] ?? $candidates[0];
                DecisionQueueController::StoreVariable('GABotArmoireReplacement',
                    $pair === null ? '' : GetZoneObject($pair['hand'])->CardID);
            } elseif ($handler === 'fm894uc4ij:0:ActivateAbility-2') {
                $replacement = DecisionQueueController::GetVariable('GABotArmoireReplacement');
                $matching = array_values(array_filter($candidates,
                    fn($mz) => GetZoneObject($mz)->CardID === $replacement));
                // Revalidate against offered choices; a changed state must still answer legally.
                $choice = $matching[0] ?? $candidates[0];
            } elseif (str_starts_with($handler, 'PurifyingThuribleBanish|')) {
                $value = function($mz) use ($player) {
                    $obj = GetZoneObject($mz);
                    return (HasFloatingMemory($obj) ? 100 : 0)
                        + (IsGuoJiaBonus($player) && EffectiveCardElement($obj) === 'WATER' ? 50 : 0);
                };
                usort($candidates, fn($a, $b) => $value($a) <=> $value($b));
                $choice = $candidates[0];
            } elseif (stripos($legal['tooltip'] ?? '', 'attack_target') !== false) {
                $choice = GABotChooseCombatTarget($player, $candidates);
            } elseif ($handler === '1n3gygojwk:0:CardActivated-1') {
                $combat = GABotCombatContext($player);
                $choice = $combat !== null && $combat['targetOwner'] === $player && in_array($combat['target'], $candidates, true)
                    ? $combat['target'] : (array_values(array_filter($candidates, fn($mz) => intval(GetZoneObject($mz)->Controller ?? 0) === $player))[0] ?? $candidates[0]);
            } elseif (GABotDamageTargetScore($player, $candidates[0], $handler) !== null) {
                usort($candidates, fn($a, $b) => GABotDamageTargetScore($player, $b, $handler) <=> GABotDamageTargetScore($player, $a, $handler));
                $choice = $candidates[0];
            } elseif (($legal['param'] ?? '') === 'myHand' && $candidates[0] !== 'PASS') {
                usort($candidates, fn($a, $b) => CardMemoryCost(GetZoneObject($a)) <=> CardMemoryCost(GetZoneObject($b)));
                $choice = $candidates[0];
            } else { $choice = $candidates[0]; }
            foreach ($actions as $action) if ($action['cardID'] === $choice) return $action;
        }
        if (!in_array($type, ['MZMAYCHOOSE', 'YESNO'], true)) return $actions[0];
        $best = null; $bestScore = 0; $unsupported = false; $unsupportedCards = [];
        foreach ($actions as $action) {
            if (in_array($action['cardID'], ['PASS', 'NO'], true)) { $best ??= $action; continue; }
            $score = GABotOptionalScore($player, $action['cardID'], $legal);
            if ($score === null) {
                $unsupported = true;
                $ability = DecodeOpportunityAbilityChoice($action['cardID']);
                $mz = $ability['mzID'] ?? $action['cardID'];
                if ($mz !== 'YES') {
                    $source = GetZoneObject($mz);
                    if ($source !== null) $unsupportedCards[] = $source->CardID;
                }
                continue;
            }
            if ($score > $bestScore) { $bestScore = $score; $best = $action; }
        }
        if ($unsupported) {
            $legal['unsupportedCards'] = array_values(array_unique($unsupportedCards));
            sort($legal['unsupportedCards']);
            GABotRecordUnsupportedDecision($legal);
        }
        return $best ?? $actions[0];
    } finally { $playerID = $saved; }
}
