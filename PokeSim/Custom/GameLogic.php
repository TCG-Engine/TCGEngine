<?php
require_once __DIR__ . '/DamageStats.php';
require_once __DIR__ . '/OpeningStats.php';
/** Pokémon rules and headless action surface over generated zones and macros. */
$customDQHandlers = [];
$customDQHandlers['PokeFinishAttack'] = function($player, $parts, $lastDecision) {
    PokeOpeningResolved((int)$player);
    PokeResolveKnockouts(); if (GetCurrentPhase() === 'MAIN') PokeSetVar('endAttack', true);
};
$customDQHandlers['PokePromote'] = function($player, $parts, $lastDecision) {
    if (is_string($lastDecision) && str_starts_with($lastDecision, "p{$player}Bench-")) PokeSwitch($player, $lastDecision, false);
};
$customDQHandlers['PokeTakePrizes'] = function($player, $parts, $lastDecision) {
    foreach (explode('&', (string)$lastDecision) as $ref) {
        if (str_starts_with($ref, "p{$player}Prizes-")) PokeMoveSimple($ref, $player, 'Hand');
    }
    AddPrizeClaims($player, 0);
};
$customDQHandlers['PokeMulliganBonus'] = function($player, $parts, $lastDecision) {
    PokeDraw($player, max(0, (int)$lastDecision), false);
};

function PokeVar(string $name, $default = null) { return DecisionQueueController::GetVariable('Poke:' . $name) ?? $default; }
function PokeSetVar(string $name, $value): void { DecisionQueueController::StoreVariable('Poke:' . $name, $value); }
function PokeRef(int $player, string $zone, int $index): string { return "p$player$zone-$index"; }
function PokeObjects(int $player, string $zone): array { return array_filter(GetZone("p$player$zone"), fn($obj) => !$obj->Removed()); }
function PokeCount(int $player, string $zone): int { return count(PokeObjects($player, $zone)); }
function PokeFirstRef(int $player, string $zone): string { $keys = array_keys(PokeObjects($player, $zone)); return $keys ? PokeRef($player, $zone, $keys[0]) : ''; }
function PokeFieldRefs(int $player): array {
    $refs = [];
    foreach (['Active', 'Bench'] as $zone) foreach (PokeObjects($player, $zone) as $i => $obj) $refs[] = PokeRef($player, $zone, $i);
    return $refs;
}
function PokeAdd(int $player, string $zone, string $id, $source = null) {
    return MZAddZone($player, 'my' . $zone, $id, $source);
}
function PokeMoveSimple(string $ref, int $player, string $destination) {
    $obj = GetZoneObject($ref);
    if (!$obj || $obj->Removed()) return null;
    $id = $obj->CardID; $obj->Remove();
    return PokeAdd($player, $destination, $id);
}
function PokeCompact(): void {
    foreach ([1, 2] as $seat) foreach (['Active', 'Bench', 'Hand', 'Deck', 'Discard', 'Prizes', 'TempZone'] as $zoneName) {
        $zone = &GetZone("p$seat$zoneName");
        $zone = array_values(array_filter($zone, fn($obj) => !$obj->Removed()));
        foreach ($zone as $i => $obj) { $obj->mzIndex = $i; $obj->Location = $zoneName; $obj->PlayerID = $seat; $obj->BuildIndex(); }
    }
}
function PokeRandom(int $max): int {
    $x = (int)GetRandomState();
    $x ^= ($x << 13) & 0xffffffff; $x ^= $x >> 17; $x ^= ($x << 5) & 0xffffffff;
    $x &= 0xffffffff; SetRandomState($x ?: 1);
    return $x % ($max + 1);
}
function PokeShuffleArray(array &$array): void {
    for ($i = count($array) - 1; $i > 0; --$i) { $j = PokeRandom($i); [$array[$i], $array[$j]] = [$array[$j], $array[$i]]; }
}
function PokeShuffle(int $player): void {
    $deck = &GetDeck($player);
    $deck = array_values(PokeObjects($player, 'Deck')); PokeShuffleArray($deck);
    foreach ($deck as $i => $obj) $obj->mzIndex = $i;
}
function PokeDraw(int $player, int $count = 1, bool $loseIfEmpty = true): void {
    for ($i = 0; $i < $count; ++$i) {
        $ref = PokeFirstRef($player, 'Deck');
        if ($ref === '') { if ($loseIfEmpty) PokeWin(3 - $player, 'deck-out'); return; }
        PokeMoveSimple($ref, $player, 'Hand');
    }
}
function PokeLog(string $event, array $fields = []): void {
    $log = PokeVar('log', []); $log[] = ['turn' => GetTurnNumber(), 'event' => $event] + $fields;
    PokeSetVar('log', array_slice($log, -200));
}
function PokeWin(int $player, string $reason): void {
    PokeOpeningEndGame();
    PokeDamageFinishTurn();
    SetWinner($player); SetCurrentPhase('GAME_OVER');
    foreach ([1,2] as $seat) { $queue = &GetDecisionQueue($seat); $queue = []; }
    PokeLog('game-over', ['winner' => $player, 'reason' => $reason]);
}

function PokeCreateGame(array $deck1, array $deck2, int $seed = 1, int $firstPlayer = 0, int $prizeCount = 6): void {
    foreach ([$deck1, $deck2] as $deck) {
        $errors = PokeValidateDeck($deck);
        if ($errors) throw new InvalidArgumentException(implode('; ', $errors));
    }
    InitializeGamestate(); $GLOBALS['playerID'] = 1; $GLOBALS['currentPlayer'] = 1;
    SetRandomState($seed & 0xffffffff ?: 1);
    SetFirstPlayer(in_array($firstPlayer, [1, 2], true) ? $firstPlayer : PokeRandom(1) + 1);
    PokeSetVar('statsFirstPlayer', GetFirstPlayer());
    SetTurnPlayer(GetFirstPlayer()); SetCurrentPhase('SETUP');
    PokeSetVar('decks', [$deck1, $deck2]); PokeSetVar('prizeCount', $prizeCount);
    PokeSetVar('initialSeed',$seed);
    foreach ([1=>$deck1,2=>$deck2] as $seat=>$deck) PokeSetVar('deckKey:'.$seat,PokeDetectDeck($deck));
    PokeOpeningInit();
    foreach ([1 => $deck1, 2 => $deck2] as $seat => $deck) {
        foreach ($deck as $entry) for ($i = 0; $i < $entry['count']; ++$i) PokeAdd($seat, 'Deck', $entry['id']);
        PokeShuffle($seat);
        do {
            PokeDraw($seat, 7, false);
            $basics = array_filter(PokeObjects($seat, 'Hand'), fn($obj) => CardType($obj->CardID) === 'Pokemon' && CardStage($obj->CardID) === 'Basic');
            if ($basics) break;
            PokeLog('mulligan', ['player' => $seat, 'cards' => array_column(PokeObjects($seat, 'Hand'), 'CardID')]);
            foreach (PokeObjects($seat, 'Hand') as $i => $obj) PokeMoveSimple(PokeRef($seat, 'Hand', $i), $seat, 'Deck');
            AddMulligans($seat, GetMulligans($seat) + 1); PokeShuffle($seat);
            if (GetMulligans($seat) > 1000) throw new RuntimeException('Mulligan limit exceeded');
        } while (true);
    }
    PokeCompact();
}

function PokeFinishSetup(): void {
    if (!PokeVar('setupBonuses', false)) {
        foreach ([1,2] as $seat) for ($i=0;$i<PokeVar('prizeCount',6);++$i) PokeMoveSimple(PokeFirstRef($seat,'Deck'),$seat,'Prizes');
        PokeSetVar('setupBonuses', true); $hasBonus = false;
        foreach ([1,2] as $seat) {
            $bonus = max(0, GetMulligans(3-$seat)-GetMulligans($seat));
            if (!$bonus) continue;
            $hasBonus = true;
            DecisionQueueController::AddDecision($seat,'NUMBERCHOOSE',"0|$bonus",1,'Draw_optional_mulligan_bonus_cards');
            DecisionQueueController::AddDecision($seat,'CUSTOM','PokeMulliganBonus',1,'',1);
        }
        if ($hasBonus) { foreach ([1,2] as $seat) AddSetupReady($seat,false); return; }
    }
    SetCurrentPhase('MAIN'); PokeMainPhase();
}

function PokePlayBasicFromZone(int $player, string $ref, string $zone = 'Bench'): void {
    $obj = GetZoneObject($ref);
    if (!$obj || $obj->Removed() || CardStage($obj->CardID) !== 'Basic' || CardType($obj->CardID) !== 'Pokemon') return;
    if ($zone === 'Bench' && PokeCount($player, 'Bench') >= 5) return;
    $fromHand = str_contains($ref, 'Hand-');
    $pokemon = PokeMoveSimple($ref, $player, $zone);
    $pokemon->Controller = $player; $pokemon->EnteredTurn = GetTurnNumber(); $pokemon->EvolvedTurn = -1;
    PokeLog('play-pokemon', ['player' => $player, 'card' => $pokemon->CardID, 'zone' => $zone]);
    if ($fromHand && $zone === 'Bench' && GetCurrentPhase() === 'MAIN') PokemonBenched($player, PokeRef($player,$zone,$pokemon->mzIndex));
}
function PokeCanEvolve(int $player, $evolution, $pokemon): bool {
    return GetPlayerTurns($player) > 1 && CardType($evolution->CardID) === 'Pokemon'
        && CardEvolveFrom($evolution->CardID) === CardName($pokemon->CardID)
        && $pokemon->EnteredTurn < GetTurnNumber() && $pokemon->EvolvedTurn < GetTurnNumber();
}
function PokeEvolve(int $player, string $handRef, string $targetRef): void {
    $card = GetZoneObject($handRef); $pokemon = GetZoneObject($targetRef);
    $pokemon->Evolutions[] = $pokemon->CardID; $pokemon->CardID = $card->CardID;
    $pokemon->EvolvedTurn = GetTurnNumber(); $pokemon->Conditions = []; $pokemon->TurnEffects = [];
    $card->Remove(); PokeLog('evolve', ['player' => $player, 'card' => $pokemon->CardID]);
}
function PokeSwitch(int $player, string $benchRef, bool $clear = true): void {
    $bench = GetZoneObject($benchRef);
    if (!$bench || $bench->Removed()) return;
    $activeRef = PokeFirstRef($player, 'Active'); $active = $activeRef === '' ? null : GetZoneObject($activeRef);
    if ($active) {
        $active->Conditions = []; $active->TurnEffects = [];
        $copy = PokeAdd($player, 'Bench', $active->CardID, $active); $copy->removed = false;
        $copy->Controller = $player; $active->Remove();
    }
    $newActive = PokeAdd($player, 'Active', $bench->CardID, $bench); $newActive->removed = false; $newActive->Controller = $player;
    $newActive->Counters['movedActiveTurn'] = GetTurnNumber();
    $bench->Remove();
    PokeLog('switch', ['player' => $player, 'card' => $newActive->CardID]);
}
function PokeCanPlayTrainer(int $player, string $ref): bool {
    $obj = GetZoneObject($ref);
    if (!$obj || !PokeCardImplemented($obj->CardID) || CardType($obj->CardID) !== 'Trainer') return false;
    if (CardTrainerType($obj->CardID) === 'Supporter' && (GetSupporterUsed($player) || ($player === GetFirstPlayer() && GetPlayerTurns($player) === 1))) return false;
    if (CardTrainerType($obj->CardID) === 'Stadium' && (PokeVar('stadiumPlayed:'.$player) === GetTurnNumber() || (PokeStadium() && CardName(PokeStadium()->CardID) === CardName($obj->CardID)))) return false;
    return !function_exists('PokeGeneratedCanPlayTrainer') || PokeGeneratedCanPlayTrainer($player, $ref);
}
function PokePlayTrainer(int $player, string $ref): void {
    $obj = GetZoneObject($ref); $id = $obj->CardID;
    if (CardTrainerType($id) === 'Supporter') AddSupporterUsed($player, true);
    $played = PokeMoveSimple($ref, $player, 'Discard');
    // The resolving Trainer is already out of hand, so costs cannot discard it.
    TrainerPlayed($player, PokeRef($player, 'Discard', $played->mzIndex));
    PokeLog('play-trainer', ['player' => $player, 'card' => $id]);
}
function PokeAttach(int $player, string $handRef, string $targetRef): void {
    $energy = GetZoneObject($handRef); $pokemon = GetZoneObject($targetRef); $id = $energy->CardID;
    $pokemon->Energy[] = $id; $energy->Remove(); AddEnergyUsed($player, true);
    EnergyAttached($player, $targetRef, $id);
    PokeLog('attach-energy', ['player' => $player, 'energy' => $id, 'target' => $pokemon->CardID]);
}
function PokeEnergyTypes(string $id): array {
    if (CardTypes($id)) return explode(',', CardTypes($id));
    foreach (['Grass','Fire','Water','Lightning','Psychic','Fighting','Darkness','Metal','Fairy'] as $type) if (str_contains(CardName($id) ?? '', $type)) return [$type];
    return [];
}
function PokeHasAttackEnergy($pokemon, array $cost): bool {
    $cost = array_values(array_filter($cost, fn($type)=>!in_array($type,['None','Free'],true)));
    $energies = array_map('PokeEnergyTypes', $pokemon->Energy);
    // Backtracking avoids spending a flexible Energy on the wrong colored pip.
    usort($cost, fn($a, $b) => ($a === 'Colorless') <=> ($b === 'Colorless'));
    $match = function($i, $remaining) use (&$match, $cost) {
        if ($i >= count($cost)) return true;
        foreach ($remaining as $n => $types) {
            if ($cost[$i] !== 'Colorless' && !in_array($cost[$i], $types, true)) continue;
            $next = $remaining; unset($next[$n]); if ($match($i + 1, $next)) return true;
        }
        return false;
    };
    return $match(0, $energies);
}
function PokeRetreatCost($pokemon): int {
    $base=(int)CardRetreat($pokemon->CardID);
    $modifier=EvaluateRetreatCostModifier($pokemon->CardID,$pokemon->Controller,$pokemon,$base,$pokemon);
    if ($pokemon->Tool !== '-') $modifier+=EvaluateRetreatCostModifier($pokemon->Tool,$pokemon->Controller,$pokemon,$base,$pokemon);
    return max(0,$base+$modifier);
}
function PokeCombinations(array $values, int $size): array {
    if ($size === 0) return [[]];
    if ($size < 0 || $size > count($values)) return [];
    $result = [];
    foreach ($values as $i => $value) foreach (PokeCombinations(array_slice($values, $i + 1), $size - 1) as $tail) $result[] = array_merge([$value], $tail);
    return $result;
}

function PokePendingPlayer(): int {
    foreach ([1,2] as $seat) if (GetDecisionQueue($seat)) return $seat;
    if (GetCurrentPhase() === 'SETUP') return !GetSetupReady(1) ? 1 : 2;
    return GetTurnPlayer();
}
function PokeLegalActions(int $player): array {
    if (!in_array($player, [1,2], true) || GetWinner()) return [];
    if ((new DecisionQueueController())->AnyQueuePending()) return [];
    $actions = [];
    if (GetCurrentPhase() === 'SETUP') {
        if (GetSetupReady($player)) return [];
        foreach (PokeObjects($player, 'Hand') as $i => $obj) {
            if (CardType($obj->CardID) !== 'Pokemon' || CardStage($obj->CardID) !== 'Basic') continue;
            if (!PokeCount($player, 'Active')) $actions[] = ['type' => 'setup-active', 'source' => PokeRef($player, 'Hand', $i)];
            elseif (PokeCount($player, 'Bench') < 5) $actions[] = ['type' => 'bench', 'source' => PokeRef($player, 'Hand', $i)];
        }
        if (PokeCount($player, 'Active')) $actions[] = ['type' => 'ready'];
    } elseif ($player === GetTurnPlayer() && GetCurrentPhase() === 'MAIN') {
        foreach (PokeObjects($player, 'Hand') as $i => $obj) {
            $ref = PokeRef($player, 'Hand', $i);
            if (EffectiveCardType($obj) === 'Pokemon') {
                if (CardStage($obj->CardID) === 'Basic' && PokeCount($player, 'Bench') < 5) $actions[] = ['type' => 'bench', 'source' => $ref];
                foreach (PokeFieldRefs($player) as $target) if (PokeCanEvolve($player, $obj, GetZoneObject($target))) $actions[] = ['type' => 'evolve', 'source' => $ref, 'target' => $target];
            } elseif (EffectiveCardType($obj) === 'Energy' && !GetEnergyUsed($player)) {
                foreach (PokeFieldRefs($player) as $target) $actions[] = ['type' => 'attach', 'source' => $ref, 'target' => $target];
            } elseif (PokeCanPlayTrainer($player, $ref)) $actions[] = ['type' => 'trainer', 'source' => $ref];
        }
        $activeRef = PokeFirstRef($player, 'Active'); $active = $activeRef ? GetZoneObject($activeRef) : null;
        if ($active && !GetRetreatUsed($player) && empty($active->Conditions['Asleep']) && empty($active->Conditions['Paralyzed'])) {
            foreach (PokeCombinations(array_keys($active->Energy), PokeRetreatCost($active)) as $payment) {
                foreach (PokeObjects($player, 'Bench') as $i => $obj) $actions[] = ['type' => 'retreat', 'target' => PokeRef($player, 'Bench', $i), 'payment' => $payment];
            }
        }
        if ($active && empty($active->Conditions['Asleep']) && empty($active->Conditions['Paralyzed']) && !($player === GetFirstPlayer() && GetPlayerTurns($player) === 1)) {
            foreach (CardAttacks($active->CardID) ?? [] as $i => $attack) {
                if (PokeHasAttackEnergy($active, $attack['cost'] ?? [])) $actions[] = ['type' => 'attack', 'source' => $activeRef, 'index' => $i];
            }
            if (!HasNoAbilities($active) && EvaluateAttackCopyAllowed($active->CardID,$player,$active,$active)>0) {
                foreach (PokeObjects($player,'Bench') as $n=>$bench) foreach (CardAttacks($bench->CardID)??[] as $i=>$attack) {
                    if (PokeHasAttackEnergy($active,$attack['cost']??[])) $actions[]=['type'=>'attack','source'=>$activeRef,'index'=>$i,'copySource'=>PokeRef($player,'Bench',$n)];
                }
            }
        }
        foreach (PokeFieldRefs($player) as $ref) {
            $obj = GetZoneObject($ref); $count = function_exists('CardActivateAbilityCount') ? CardActivateAbilityCount($obj->CardID) : 0;
            for ($i = 0; $i < $count; ++$i) if (!HasNoAbilities($obj) && !PokeSelfKnockoutAbilityBlocked($obj,$i) && (!function_exists('PokeGeneratedCanActivateAbility') || PokeGeneratedCanActivateAbility($player, $ref, $i))) $actions[] = ['type' => 'ability', 'source' => $ref, 'index' => $i];
        }
        $actions[] = ['type' => 'end'];
    }
    foreach ($actions as &$action) $action['player'] = $player;
    return $actions;
}

function PokeApplyAction(array $action): void {
    $player = (int)($action['player'] ?? 0); $GLOBALS['playerID'] = $player;
    if (($action['type'] ?? '') === 'decision') { PokeAnswerDecision($player, (string)($action['value'] ?? '')); return; }
    if (($action['type'] ?? '') === 'concede' && in_array($player, [1,2], true) && !GetWinner()) { PokeWin(3-$player, 'concede'); return; }
    // Exact legal-action membership validates source ownership, targets and payments
    // before any mutation. Browser and headless clients use the identical gate.
    $legal = false;
    foreach (PokeLegalActions($player) as $candidate) {
        ksort($candidate); $incoming = $action; ksort($incoming);
        if ($candidate === $incoming) { $legal = true; break; }
    }
    if (!$legal) throw new InvalidArgumentException('Illegal PokeSim action');
    PokeOpeningAction($action);
    switch ($action['type']) {
        case 'setup-active': PokePlayBasicFromZone($player, $action['source'], 'Active'); break;
        case 'bench': PokePlayBasicFromZone($player, $action['source']); break;
        case 'ready':
            AddSetupReady($player, true);
            if (GetSetupReady(1) && GetSetupReady(2)) {
                PokeFinishSetup();
            }
            break;
        case 'evolve': PokeEvolve($player, $action['source'], $action['target']); break;
        case 'trainer': PokePlayTrainer($player, $action['source']); break;
        case 'attach': PokeAttach($player, $action['source'], $action['target']); break;
        case 'retreat':
            $active = GetZoneObject(PokeFirstRef($player, 'Active'));
            foreach ($action['payment'] as $index) { PokeAdd($player, 'Discard', $active->Energy[$index]); unset($active->Energy[$index]); }
            $active->Energy = array_values($active->Energy); AddRetreatUsed($player, true); PokeSwitch($player, $action['target']); break;
        case 'attack':
            $active = GetZoneObject($action['source']);
            PokeSetVar('copiedAttack',isset($action['copySource']) ? GetZoneObject($action['copySource'])->CardID : null);
            if (!empty($active->Conditions['Confused']) && PokeRandom(1) === 0) {
                $active->Damage += 30; PokeResolveKnockouts(); PokeSetVar('endAttack', true);
            } else Attack($player, $action['source'], $action['index']);
            break;
        case 'ability':
            PokeSetVar('resolvingAbility',true); ActivateAbility($player, $action['source'], $action['index']); PokeSetVar('resolvingAbility',false); break;
        case 'end': PokeEndTurn(); break;
    }
    PokeDrain();
    $GLOBALS['updateNumber'] = ($GLOBALS['updateNumber'] ?? 0) + 1;
}

function PokeAnswerDecision(int $player, string $value): void {
    if (!in_array($player, [1,2], true) || GetWinner()) throw new InvalidArgumentException('No pending decision');
    $controller = new DecisionQueueController(); $decision = $controller->NextDecision($player);
    if (!$decision) throw new InvalidArgumentException('No decision for this player');
    $options = PokeDecisionOptions($player);
    $selected = [];
    switch ($decision->Type) {
        case 'MZCHOOSE': case 'MZMAYCHOOSE':
            if (in_array($value, ['-', 'PASS'], true) && $decision->Type === 'MZMAYCHOOSE') break;
            if (!in_array($value, array_column($options['choices'], 'value'), true)) throw new InvalidArgumentException('Invalid choice');
            break;
        case 'MZMULTICHOOSE':
            $selected = $value === '-' ? [] : explode('&', $value);
            if (count($selected) !== count(array_unique($selected)) || count($selected) < $options['min'] || count($selected) > $options['max']) throw new InvalidArgumentException('Invalid selection count');
            foreach ($selected as $ref) if (!in_array($ref, array_column($options['choices'], 'value'), true)) throw new InvalidArgumentException('Invalid card choice');
            break;
        case 'NUMBERCHOOSE':
            if (!ctype_digit($value) || (int)$value < $options['min'] || (int)$value > $options['max']) throw new InvalidArgumentException('Invalid number');
            break;
        case 'YESNO': if (!in_array($value, ['YES', 'NO'], true)) throw new InvalidArgumentException('Invalid yes/no'); break;
        default: throw new RuntimeException('Unsupported decision type: ' . $decision->Type);
    }
    if ($decision->Type === 'MZMAYCHOOSE' && $value === 'PASS') $value = '-';
    PokeOpeningAction(['type'=>'decision', 'player'=>$player, 'value'=>$value]);
    $GLOBALS['playerID'] = $player; $controller->PopDecision($player); $controller->ExecuteStaticMethods($player, $value);
    PokeDrain(); $GLOBALS['updateNumber'] = ($GLOBALS['updateNumber'] ?? 0) + 1;
}
function PokeDecisionOptions(int $player): ?array {
    $decision = (new DecisionQueueController())->NextDecision($player);
    if (!$decision) return null;
    $min = 1; $max = 1; $spec = $decision->Param;
    if ($decision->Type === 'MZMULTICHOOSE') { [$min, $max, $spec] = explode('|', $spec, 3); }
    elseif ($decision->Type === 'NUMBERCHOOSE') { [$min, $max] = explode('|', $spec, 2); $spec = ''; }
    elseif ($decision->Type === 'MZMAYCHOOSE') $min = 0;
    $choices = [];
    foreach (explode('&', $spec) as $ref) {
        if (!preg_match('/^p[12][A-Za-z]+-\d+$/D', $ref)) continue;
        $obj = GetZoneObject($ref); if (!$obj || $obj->Removed()) continue;
        $hidden = str_contains($ref, 'Prizes-');
        $choices[] = ['value' => $ref, 'card' => $hidden ? null : $obj->CardID, 'label' => $hidden ? 'Face-down Prize ' . (1 + $obj->mzIndex) : CardName($obj->CardID)];
    }
    if ($decision->Type === 'YESNO') $choices = [['value'=>'YES', 'label'=>'Yes'], ['value'=>'NO','label'=>'No']];
    return ['player' => $player, 'type' => $decision->Type, 'prompt' => str_replace('_', ' ', $decision->Tooltip), 'min' => (int)$min, 'max' => (int)$max, 'choices' => $choices];
}

function PokeDrain(): void {
    for ($iteration = 0; $iteration < 50; ++$iteration) {
        if (GetWinner()) return;
        foreach ([1,2] as $seat) { $GLOBALS['playerID'] = $seat; (new DecisionQueueController())->ExecuteStaticMethods($seat, '-'); }
        if ((new DecisionQueueController())->AnyQueuePending()) return;
        if (PokeVar('endAttack', false)) { PokeSetVar('endAttack', false); PokeEndTurn(); continue; }
        PokeCompact(); $GLOBALS['playerID'] = GetTurnPlayer(); PokeOpeningObserve(); return;
    }
    throw new RuntimeException('PokeSim decision loop exceeded its bound');
}
function PokeEndTurn(): void {
    PokeOpeningFinishTurn();
    PokeDamageFinishTurn();
    PokeSetVar('blackBelt:' . GetTurnPlayer(), null);
    SetCurrentPhase('CHECKUP'); PokeCheckupPhase(); AutoAdvance();
}
function PokeSetupPhase(): void {}
function PokeGameOverPhase(): void {}
function PokeMainPhase(): void {
    if (GetWinner()) return;
    if (GetTurnNumber() > 0) SetTurnPlayer(3 - GetTurnPlayer());
    SetTurnNumber(GetTurnNumber() + 1); $seat = GetTurnPlayer();
    AddPlayerTurns($seat, GetPlayerTurns($seat) + 1);
    PokeDamageStartTurn($seat);
    AddEnergyUsed($seat, false); AddSupporterUsed($seat, false); AddRetreatUsed($seat, false);
    SetMacroTurnIndex('{}'); PokeDraw($seat, 1);
    PokeLog('turn-start', ['player' => $seat]);
    PokeOpeningObserve();
}
function PokeCheckupPhase(): void {
    foreach ([1,2] as $seat) {
        $ref = PokeFirstRef($seat, 'Active'); if ($ref === '') continue; $obj = GetZoneObject($ref);
        if (!empty($obj->Conditions['Poisoned'])) $obj->Damage += max(10, (int)$obj->Conditions['Poisoned']);
        if (!empty($obj->Conditions['Burned'])) { $obj->Damage += 20; if (PokeRandom(1)) unset($obj->Conditions['Burned']); }
        if (!empty($obj->Conditions['Asleep']) && PokeRandom(1)) unset($obj->Conditions['Asleep']);
        if ($seat === GetTurnPlayer()) unset($obj->Conditions['Paralyzed']);
    }
    PokeResolveKnockouts();
}
function PokeObservation(int $viewer = 1): array {
    $players = [];
    foreach ([1,2] as $seat) {
        $row = ['deckCount' => PokeCount($seat, 'Deck'), 'prizeCount' => PokeCount($seat, 'Prizes'), 'handCount' => PokeCount($seat, 'Hand'), 'turns' => GetPlayerTurns($seat), 'energyUsed'=>GetEnergyUsed($seat), 'supporterUsed'=>GetSupporterUsed($seat)];
        foreach (['Active', 'Bench', 'Discard', 'Hand', 'TempZone'] as $zone) {
            $row[$zone] = [];
            if (in_array($zone, ['Hand','TempZone'], true) && $viewer !== $seat) continue;
            if (GetCurrentPhase() === 'SETUP' && $seat !== $viewer && in_array($zone, ['Active','Bench'], true)) continue;
            foreach (PokeObjects($seat, $zone) as $i => $obj) {
                $row[$zone][] = ['ref'=>PokeRef($seat,$zone,$i),'id'=>$obj->CardID,'name'=>CardName($obj->CardID),
                    'damage'=>$obj->Damage ?? 0,'hp'=>CardHp($obj->CardID),'energy'=>$obj->Energy ?? [],'conditions'=>$obj->Conditions ?? [], 'evolutions'=>$obj->Evolutions ?? [],'tool'=>$obj->Tool??'-','toolName'=>isset($obj->Tool)&&$obj->Tool!=='-'?CardName($obj->Tool):null,'counters'=>$obj->Counters??[],
                    'attachedEnergy'=>array_map(fn($id)=>['id'=>$id,'name'=>CardName($id)??$id],$obj->Energy??[]),
                    'attacks'=>CardAttacks($obj->CardID) ?? [],'abilities'=>CardAbilities($obj->CardID) ?? [],'effect'=>CardEffect($obj->CardID) ?? ''];
            }
        }
        $row['deckKey']=PokeVar('deckKey:'.$seat,'sinistcha'); $row['deckName']=PokeDeckName($row['deckKey']);
        $players[$seat] = $row;
    }
    $log = PokeVar('log', []);
    if (GetCurrentPhase() === 'SETUP') $log = array_values(array_filter($log, fn($event)=>!in_array($event['event'], ['play-pokemon','switch'], true) || ($event['player'] ?? 0) === $viewer));
    return ['viewer'=>$viewer, 'players'=>$players, 'phase'=>GetCurrentPhase(), 'turn'=>GetTurnNumber(), 'turnPlayer'=>GetTurnPlayer(), 'firstPlayer'=>GetFirstPlayer(),'seed'=>PokeVar('initialSeed'),
        'damageTurns'=>PokeVar('damageTurns', []), 'winner'=>GetWinner(), 'stadium'=>PokeStadium()?['id'=>PokeStadium()->CardID,'name'=>CardName(PokeStadium()->CardID)]:null, 'actions'=>PokeLegalActions($viewer), 'decision'=>PokeDecisionOptions($viewer), 'log'=>$log, 'revision'=>$GLOBALS['updateNumber'] ?? 0];
}
