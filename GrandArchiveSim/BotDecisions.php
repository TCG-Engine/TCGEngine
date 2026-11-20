<?php
require_once __DIR__ . "/BotKnowledge.php";
// Find a payable Trefle domain subset without exploring every combination.
function GABotTreflePayment($candidates, $minimum, $maximum) {
    $paths = [0 => [0 => []]];
    foreach (array_unique($candidates) as $ref) {
        $obj = GetZoneObject($ref);
        if ($obj === null || $obj->removed) continue;
        $cost = max(0, intval(CardCost_reserve($obj->CardID)));
        $previous = $paths;
        foreach ($previous as $count => $sums) {
            if ($count >= $maximum) continue;
            foreach ($sums as $sum => $chosen) {
                if ($sum + $cost > 10) continue;
                $paths[$count + 1][$sum + $cost] ??= array_merge($chosen, [$ref]);
            }
        }
    }
    for ($count = max(2, $minimum); $count <= $maximum; ++$count) {
        if (isset($paths[$count][10])) return implode('&', $paths[$count][10]);
    }
    return null;
}
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

// Decode only Cerulean's declaration frame; other modal cards retain their policies.
function GABotCeruleanKey($handler) {
    $parts = explode('|', $handler);
    if (($parts[0] ?? '') !== 'GADeclareTarget') return null;
    $state = json_decode(base64_decode($parts[1] ?? '', true) ?: '', true);
    return ($state['cardID'] ?? '') === 'ipl6gt7lh9' ? base64_decode($parts[2] ?? '', true) : null;
}

function GABotCeruleanTargetScore($player, $key, $ref) {
    $obj = GetZoneObject($ref);
    if ($obj === null || !empty($obj->removed) || intval($obj->Controller) === $player) return -1;
    if ($key === 'negate') {
        if (!in_array($ref, GetEffectStackActivationTargets($player), true)
            || PropertyContains(EffectiveCardType($obj), 'ATTACK')) return -1;
        // A tax has value even if paid. Never inspect the opponent's hand to
        // predict payment, or label the activation as guaranteed negation.
        return 40;
    }
    if ($key !== 'power' || !in_array($ref, FilterSpellshroudTargets(GAAllFieldTargets()), true)) return -1;
    $combat = GABotCombatContext($player);
    if ($combat === null || DecisionQueueController::GetVariable('CombatDamageAmount') !== null) return -1;
    return $combat['owner'] !== $player && $combat['targetOwner'] === $player
        && $combat['attacker'] === $ref && $combat['threat'] >= $combat['hp']
        && $combat['threat'] - 3 < $combat['hp'] ? 100 : -1;
}

function GABotCeruleanModeScores($player) {
    $scores = [0 => -1, 1 => -1, 2 => -100];
    foreach (GetEffectStackActivationTargets($player) as $ref)
        $scores[0] = max($scores[0], GABotCeruleanTargetScore($player, 'negate', $ref));
    foreach (GAAllFieldTargets() as $ref)
        $scores[1] = max($scores[1], GABotCeruleanTargetScore($player, 'power', $ref));
    // Memory draw is delayed value. Preserve resources during our turn and
    // pending threats; keep a card in the deck for the mandatory draw.
    if (intval(GetTurnPlayer()) !== $player && empty(GetLiveEffectStackEntries())
        && GABotCombatContext($player) === null && GABotLiveCount(GetHand($player)) >= 5
        && GABotCanBenefitFromDraw($player)) $scores[2] = 10;
    return $scores;
}

// Red Hare cycles only cards with an understood graveyard payoff.
function GABotRedHareDiscardScore($obj) {
    if ($obj === null || !empty($obj->removed)) return -1;
    if ($obj->CardID === '7w4v1hgl3e') return 40;
    return HasFloatingMemory($obj) ? 30 : -1;
}

function GABotRestorativePaymentScore($player, $ref) {
    $obj = GetZoneObject($ref);
    $champion = GABotOwnChampion($player);
    if (!str_starts_with($ref, 'myGraveyard-') || $obj === null || !empty($obj->removed)
        || !PropertyContains(EffectiveCardElement($obj), 'FIRE') || $champion === null) return -1;
    $recovery = min(intval($champion->Damage), GABotRecoveryAmount($player, 2));
    if ($recovery <= 0) return -1;
    $urgent = GABotRemainingHP($champion) <= 5;
    if (!$urgent && ($recovery < 2 || HasFloatingMemory($obj) || $obj->CardID === '7w4v1hgl3e')) return -1;
    return 20 + $recovery * 5 - (HasFloatingMemory($obj) || $obj->CardID === '7w4v1hgl3e' ? 10 : 0);
}

// Conservative public-state policy: a threshold crossing justifies three graveyard cards.
// Protection is not simulated here; decline protected targets rather than claim a kill.
function GABotRendingFlamesScore($player) {
    $combat = GABotCombatContext($player);
    if ($combat === null || $combat['owner'] !== $player || $combat['targetOwner'] === $player
        || DecisionQueueController::GetVariable('CombatDamageAmount') !== null
        || AttackHasDamageDoubleEffect($player)
        || count(ZoneSearch('myGraveyard', cardElements: ['FIRE'])) < 3) return -1;
    $target = GetZoneObject($combat['target']);
    // Avoid assuming that temporary or field-based protection can be overwhelmed.
    if (array_diff($target->TurnEffects ?? [], ['ENTERED_THIS_TURN'])) return -1;
    foreach (GetGlobalEffects($combat['targetOwner']) as $effect) {
        if ($effect !== null && empty($effect->removed)) return -1;
    }
    foreach (GetField($combat['targetOwner']) as $unit) {
        if ($unit === null || !empty($unit->removed) || HasNoAbilities($unit)) continue;
        if (in_array($unit->CardID, ['Zu53izIFTX', 'w6OqqsfEso', 'tsvbgl6ffq', 'sqGcyYocLW', 'Zxab4Vi0wx', 'yicNKtzC3H', 'fbvt9rdhkj', 'm69XrVkaVh', 'nm77bnz4cc', '64xGWbG9Xf'], true)) return -1;
    }
    return $combat['threat'] > 0 && $combat['hp'] > $combat['threat']
        && $combat['hp'] <= 2 * $combat['threat'] ? 60 : -1;
}

function GABotOptionalScore($player, $answer, $legal) {
    $handler = $legal['handler'] ?? '';
    $tooltip = $legal['tooltip'] ?? '';
    if ($handler === 'RendingFlamesConfirm') return GABotRendingFlamesScore($player);
    $ceruleanKey = GABotCeruleanKey($handler);
    if (in_array($ceruleanKey, ['negate', 'power'], true)) return GABotCeruleanTargetScore($player, $ceruleanKey, $answer);
    if ($handler === 'DsiRzt0trX:0:OnAttack-1') {
        // Use only our offered hand card and known graveyard value, never the next draw.
        if (!str_starts_with($answer, 'myHand-') || !GABotCanBenefitFromDraw($player)) return -1;
        return GABotRedHareDiscardScore(GetZoneObject($answer));
    }
    if ($handler === 'RedHareDiscardDraw') {
        if (!GABotCanBenefitFromDraw($player)) return -1;
        foreach (GetHand($player) as $obj) if (GABotRedHareDiscardScore($obj) > 0) return 20;
        return -1;
    }
    if (in_array($handler, ['ek7r2d7uz4:0:CardActivated-1', 'ek7r2d7uz4:0:CardActivated-2', 'ek7r2d7uz4:0:CardActivated-3'], true)) {
        return GABotRestorativePaymentScore($player, $answer);
    }
    if ($handler === 'KindleProcess') {
        $obj = GetZoneObject($answer);
        $used = intval(DecisionQueueController::GetVariable('kindleBanished'));
        if (!str_starts_with($answer, 'myGraveyard-') || $obj === null || !empty($obj->removed)
            || !PropertyContains(EffectiveCardElement($obj), 'FIRE')
            || $used >= intval(DecisionQueueController::GetVariable('kindleMax'))
            || $used >= intval(DecisionQueueController::GetVariable('kindleReserveCost'))) return -1;
        // March Hare returns to the field when kindled with its element bonus.
        if ($obj->CardID === '7w4v1hgl3e' && IsElementBonusActive($player, $obj->CardID)) return 100;
        // Prefer ordinary fire cards to floating memory, retaining flexible future payment.
        return HasFloatingMemory($obj) ? 10 : 30;
    }
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
            if ($source !== null && $ability['abilityIndex'] === 0
                && in_array($source->CardID, ['8yzADlgx4R', 'ugly4wiffe', 'ScGcOmkoQt'], true)) {
                if (!CanActivateAbility($player, $ability['mzID'], 0)) return -1;
                if ($source->CardID === 'ScGcOmkoQt') {
                    // Stealth does not undo an already declared attack. Cycle safely,
                    // but do not give the opponent stealth when they have the only allies.
                    $friendly = false; $enemy = false;
                    foreach (array_merge(GetField($player), GetField(3 - $player)) as $unit) {
                        if ($unit === null || !empty($unit->removed)
                            || !PropertyContains(EffectiveCardType($unit), 'ALLY')) continue;
                        if (intval($unit->Controller) === $player) $friendly = true;
                        else $enemy = true;
                    }
                    return (!$enemy || $friendly) && GABotCanBenefitFromDraw($player)
                        ? GABotDrawPriority($player) : -1;
                }
                $combat = GABotCombatContext($player);
                if ($combat === null || DecisionQueueController::GetVariable('CombatDamageAmount') !== null) return -1;
                if ($source->CardID === 'ugly4wiffe') {
                    return CountAvailableReservePayments($player, $ability['mzID']) >= 2
                        && $combat['owner'] !== $player && $combat['target'] === $ability['mzID']
                        && $combat['threat'] >= $combat['hp'] ? 100 : -1;
                }
                // This generated ability checks preparation inside its resolution, so
                // CanActivateAbility alone does not establish that the buff can happen.
                $champion = GABotOwnChampion($player);
                if ($champion === null || GetCounterCount($champion, 'preparation') < 1
                    || intval($source->Status) !== 2
                    || !IsClassBonusActive($player, explode(',', CardClasses($source->CardID)))) return -1;
                // Weapon refs use the attacker's perspective; compare only on our attack.
                return $combat['owner'] === $player && GetCombatWeapon() === $ability['mzID']
                    && $combat['targetOwner'] !== $player && $combat['threat'] < $combat['hp']
                    && $combat['threat'] + 2 >= $combat['hp'] ? 90 : -1;
            }
            if ($source !== null && $ability['abilityIndex'] === 0 && $source->CardID === 'xnrw8qq1uw') {
                if (!CanActivateAbility($player, $ability['mzID'], 0)
                    || GetCurrentPhase() !== 'BREC' || intval(GetTurnPlayer()) === $player) return -1;
                // Preserve the consumable once a tax is active or another copy is pending.
                if (GlobalEffectCount($player, 'xnrw8qq1uw') + GlobalEffectCount(3-$player, 'xnrw8qq1uw') > 0) return -1;
                foreach (GetLiveEffectStackEntries() as $entry) {
                    if ($entry->CardID === 'xnrw8qq1uw') return -1;
                }
                $attackers = 0; $power = 0;
                foreach (GetField(3-$player) as $unit) {
                    if ($unit === null || !empty($unit->removed) || intval($unit->Controller) === $player
                        || !PropertyContains(EffectiveCardType($unit), 'ALLY')) continue;
                    $amount = max(0, intval(ObjectCurrentPower($unit)));
                    if ($amount === 0) continue;
                    ++$attackers; $power += $amount;
                }
                // Recollection precedes waking rested allies. Count their public pressure,
                // not their current rest status or the identities in the opponent's hand.
                $champion = GABotOwnChampion($player);
                return $attackers >= 2 || ($champion !== null && $power > 0
                    && $power >= GABotRemainingHP($champion)) ? 45 : -1;
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
        if ($obj->CardID === 'ek7r2d7uz4') {
            foreach (GetLiveEffectStackEntries() as $entry) {
                if ($entry->CardID === $obj->CardID && intval($entry->Controller) === $player) return -1;
            }
            $champion = GABotOwnChampion($player);
            if ($champion === null || (intval($champion->Damage) < 4 && GABotRemainingHP($champion) > 5)) return -1;
            $best = -1;
            foreach (GetGraveyard($player) as $i => $card)
                $best = max($best, GABotRestorativePaymentScore($player, 'myGraveyard-'.$i));
            return $best;
        }
        if ($obj->CardID === 'ipl6gt7lh9') {
            foreach (GetLiveEffectStackEntries() as $entry) {
                if ($entry->CardID === $obj->CardID && intval($entry->Controller) === $player) return -1;
            }
            return max(GABotCeruleanModeScores($player));
        }
        if (in_array($obj->CardID, ['6ffqsuo6gb', 'cpvn96659y'], true)) {
            // Do not spend another copy before the first response resolves.
            foreach (GetLiveEffectStackEntries() as $entry) {
                if ($entry->CardID === $obj->CardID && intval($entry->Controller) === $player) return -1;
            }
            if ($obj->CardID === 'cpvn96659y') {
                $targets = FilterSpellshroudTargets(array_merge(
                    ZoneSearch('theirField', ['ITEM']), ZoneSearch('theirField', ['WEAPON'])));
                $best = -1;
                foreach ($targets as $target) $best = max($best, GABotFracturizeTargetScore($player, $target));
                return $best;
            }
            $best = -1;
            foreach (GAActivationTargetSpecs($player, $obj) ?: [] as $spec) {
                if ($spec['key'] !== 'primary') continue;
                foreach ($spec['targets'] as $target) {
                    $unit = GetZoneObject($target);
                    if (intval($unit->Controller) === $player) continue;
                    // Preserve the spell for a kill; incidental chip damage can wait.
                    if (GABotRemainingHP($unit) > 0 && GABotRemainingHP($unit) <= GABotRefractingDamage($player)) {
                        $best = max($best, GABotDamageTargetScore($player, $target, '6ffqsuo6gb:0:CardActivated-1'));
                    }
                }
            }
            return $best;
        }
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

// Count live, visible Fractals; never inspect a deck or opposing hand.
function GABotRefractingDamage($player) {
    $damage = 1;
    foreach (GetField($player) as $obj) {
        if ($obj !== null && empty($obj->removed)
            && PropertyContains(EffectiveCardSubtypes($obj), 'FRACTAL')) ++$damage;
    }
    return $damage;
}

function GABotFracturizeTargetScore($player, $mz) {
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed) || intval($obj->Controller) === $player) return -10000;
    // Converting a weapon denies future attacks but grants Reservable. Do not
    // trade a card merely to convert an item whose passive value is unknown.
    if (!PropertyContains(EffectiveCardType($obj), 'WEAPON')) return -1;
    $power = max(0, intval(ObjectCurrentPower($obj)));
    return $power > 0 ? 30 + 10 * $power : -1;
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
        if ($handler !== "$card:0:CardActivated-1" && $handler !== "m6h38lrj52:0:Enter-1") return null;
    }
    // Fixed damage effects; target-dependent amounts are resolved below.
    $damage = ['0G32Pp5atK' => 2, '0op3nq0ymv' => 3, '0xm513tj3j' => 4, 'iohZMWh5v5' => 4, 'hd0sxpu7cp' => 2, 'm6h38lrj52' => 2][$card] ?? null;
    if ($card === '6ffqsuo6gb') $damage = GABotRefractingDamage($player);
    if ($damage === null) return null;
    $obj = GetZoneObject($mz);
    if ($obj === null || !empty($obj->removed)) return -100000;
    $ally = PropertyContains(EffectiveCardType($obj), 'ALLY');
    if ($card === 'hd0sxpu7cp') {
        $graveyard = intval($obj->Controller) === intval($player) ? 'myGraveyard' : 'theirGraveyard';
        $damage = count(ZoneSearch($graveyard)) >= 8 ? 6 : 2;
    }
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
        if ($type === 'MZMODAL' && GABotCeruleanKey($handler) === 'mode') {
            $parts = explode('|', $legal['param'], 3);
            $labels = explode('&', $parts[2] ?? '');
            $modeIDs = ['Negate_non_attack' => 0, 'Unit_attack_power' => 1, 'Draw_into_memory' => 2];
            $scores = GABotCeruleanModeScores($player);
            $rank = [];
            foreach ($labels as $index => $label) {
                if (!isset($modeIDs[$label])) return $actions[0];
                $rank[$index] = $scores[$modeIDs[$label]];
            }
            arsort($rank, SORT_NUMERIC);
            // Wire indices refer to the displayed subset, not original mode IDs.
            $actions[0]['cardID'] = implode(',', array_slice(array_keys($rank), 0, intval($parts[0])));
            return $actions[0];
        }
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
            if ($handler === 'RendingFlamesProcess') {
                // Offered public graveyard cards only; retain reusable reserve fuel first.
                usort($candidates, function($a, $b) {
                    $value = function($mz) {
                        $obj = GetZoneObject($mz);
                        return (HasFloatingMemory($obj) ? 100 : 0)
                            + ($obj->CardID === '7w4v1hgl3e' ? 50 : 0);
                    };
                    return $value($a) <=> $value($b);
                });
                $actions[0]['cardID'] = implode('&', array_slice($candidates, 0, $min));
            } elseif ($handler === 'T3cx65VM3D:0:ActivateAbility-1') {
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
            if ($handler === 'RedHareDiscardFinish') {
                usort($candidates, fn($a, $b) => GABotRedHareDiscardScore(GetZoneObject($b)) <=> GABotRedHareDiscardScore(GetZoneObject($a)));
                $choice = $candidates[0];
            } elseif ($handler === 'ReserveCard_Process') {
                $spare = array_values(array_intersect(GABotSpareReserveSources($player), $candidates));
                $choice = $spare[0] ?? $candidates[0];
            } elseif (in_array($handler, ['MATERIALIZE', 'MATERIALIZE|NOCOST', 'WeaponSelected'], true) && $candidates[0] !== 'PASS') {
                usort($candidates, fn($a, $b) => GABotOptionalScore($player, $b, $legal) <=> GABotOptionalScore($player, $a, $legal));
                $choice = $candidates[0];
            } elseif ($handler === 'ScGcOmkoQt:0:ActivateAbility-1') {
                $value = function($mz) use ($player) {
                    $obj = GetZoneObject($mz);
                    if (intval($obj->Controller) !== $player) return -10000;
                    return (HasStealth($obj) ? 0 : 100) + max(0, intval(ObjectCurrentPower($obj)));
                };
                usort($candidates, fn($a, $b) => $value($b) <=> $value($a));
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
            } elseif ($handler === 'cpvn96659y:0:CardActivated-1') {
                usort($candidates, fn($a, $b) => GABotFracturizeTargetScore($player, $b) <=> GABotFracturizeTargetScore($player, $a));
                $choice = $candidates[0];
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
