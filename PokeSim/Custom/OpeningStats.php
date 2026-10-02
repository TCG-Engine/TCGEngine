<?php
require_once __DIR__.'/OpeningProfiles.php';

/** Private serialized diagnostics: never included in opponent observations.
 * Snapshots describe observed blockers, not proof that no alternative line exists.
 */
function PokeOpeningInit(): void {
    $rows = [];
    foreach ([1,2] as $player) {
        $deck = PokeVar('deckKey:'.$player, 'custom');
        $first = $player === GetFirstPlayer();
        $rows[$player] = ['version'=>1, 'player'=>$player, 'deck'=>$deck,
            'order'=>$first ? 'first' : 'second', 'eligibleTurn'=>$first ? 2 : 1,
            'goal'=>PokeOpeningProfile($deck)['name'],
            'deckHash'=>hash('sha256', json_encode(PokeVar('decks', [])[$player-1] ?? [])),
            'status'=>'pending', 'reached'=>false,
            'attackDeclared'=>false, 'attackResolved'=>false, 'goalAttack'=>false,
            'fullyEnabledAttack'=>false, 'anyLegalSeen'=>false, 'goalLegalSeen'=>false,
            'milestones'=>[], 'blockers'=>[], 'trace'=>[], 'traceTruncated'=>false,
            'steps'=>0, 'damage'=>0, 'prizesTaken'=>0];
    }
    PokeSetVar('openingStats', $rows);
}
function PokeOpeningMatches(array $profile, string $card, int $index): bool {
    return !$profile['targets'] || in_array($index, $profile['targets'][$card] ?? [], true);
}
function PokeOpeningSnapshot(int $player): array {
    $profile = PokeOpeningProfile(PokeVar('deckKey:'.$player, 'custom'));
    $candidates = []; $inHand = []; $inDiscard = []; $energyInHand = []; $energyInDiscard = [];
    foreach (['Hand','Discard'] as $zone) foreach (PokeObjects($player, $zone) as $obj) {
        if (CardType($obj->CardID)==='Energy') {
            if ($zone==='Hand') $energyInHand[]=$obj->CardID; else $energyInDiscard[]=$obj->CardID;
        }
        if (isset($profile['targets'][$obj->CardID]) || (!$profile['targets'] && CardType($obj->CardID)==='Pokemon' && CardAttacks($obj->CardID))) {
            if ($zone === 'Hand') $inHand[] = $obj->CardID; else $inDiscard[] = $obj->CardID;
        }
    }
    $sources = [];
    foreach (PokeFieldRefs($player) as $ref) $sources[] = [$ref, GetZoneObject($ref)->CardID];
    $activeRef = PokeFirstRef($player, 'Active');
    if ($activeRef !== '') {
        $activeObj = GetZoneObject($activeRef);
        if (!HasNoAbilities($activeObj) && EvaluateAttackCopyAllowed($activeObj->CardID,$player,$activeObj,$activeObj)>0)
            foreach (PokeObjects($player,'Bench') as $bench) $sources[] = [$activeRef, $bench->CardID];
    }
    foreach ($sources as [$ref, $attackID]) {
        $obj = GetZoneObject($ref);
        foreach (CardAttacks($attackID) ?? [] as $index=>$attack) {
            if (!PokeOpeningMatches($profile, $attackID, $index)) continue;
            $cost = $attack['cost'] ?? []; $gap = count($cost);
            // Minimum unmatched cost units using the engine's Energy matching.
            for ($size=count($cost); $size>=0; --$size) {
                foreach (PokeCombinations($cost, $size) as $subset) if (PokeHasAttackEnergy($obj, $subset)) {
                    $gap = count($cost)-$size; break 2;
                }
            }
            $candidates[] = ['card'=>$obj->CardID, 'attackCard'=>$attackID, 'index'=>$index, 'zone'=>$obj->Location,
                'energyGap'=>$gap, 'cost'=>$cost, 'energy'=>$obj->Energy,
                'ready'=>($profile['ready'])($player, $obj),
                'conditionBlocked'=>!empty($obj->Conditions['Asleep']) || !empty($obj->Conditions['Paralyzed'])];
        }
    }
    $legal = array_values(array_filter(PokeLegalActions($player), fn($a)=>$a['type']==='attack'));
    $goalLegal = array_filter($legal, function($a) use ($profile) {
        $source = GetZoneObject($a['copySource'] ?? $a['source']);
        return PokeOpeningMatches($profile, $source->CardID, $a['index']);
    });
    $blockers = [];
    if (!$candidates && !$goalLegal) $blockers[] = 'attacker_unavailable';
    if ($candidates && !array_filter($candidates, fn($c)=>$c['energyGap']===0)) $blockers[] = 'energy_shortfall';
    $powered = array_filter($candidates, fn($c)=>$c['energyGap']===0);
    if ($powered && !array_filter($powered, fn($c)=>$c['zone']==='Active')) $blockers[] = 'access_to_active';
    if ($candidates && !array_filter($candidates, fn($c)=>$c['ready'])) $blockers[] = 'goal_prerequisite_unmet';
    $active = array_filter($powered, fn($c)=>$c['zone']==='Active');
    if ($active && !array_filter($active, fn($c)=>!$c['conditionBlocked'])) $blockers[] = 'special_condition';
    return ['candidates'=>$candidates, 'attackerInHand'=>$inHand, 'attackerInDiscard'=>$inDiscard,
        'energyInHand'=>$energyInHand, 'energyInDiscard'=>$energyInDiscard,
        'energyUsed'=>(bool)GetEnergyUsed($player), 'supporterUsed'=>(bool)GetSupporterUsed($player),
        'anyLegal'=>(bool)$legal, 'goalLegal'=>(bool)$goalLegal, 'blockers'=>$blockers,
        'milestones'=>($profile['milestones'])($player)];
}
function PokeOpeningObserve(): void {
    $rows = PokeVar('openingStats', []); $player = GetTurnPlayer();
    if (!isset($rows[$player]) || GetCurrentPhase() !== 'MAIN' || GetWinner()) return;
    $row = &$rows[$player];
    if ($row['status'] !== 'pending' || $row['attackDeclared'] || GetPlayerTurns($player) > $row['eligibleTurn']) return;
    if ((new DecisionQueueController())->AnyQueuePending()) return;
    $snapshot = PokeOpeningSnapshot($player);
    if (GetPlayerTurns($player) === $row['eligibleTurn'] && !$row['reached']) {
        $row['reached'] = true; $row['prizesAtStart'] = PokeCount($player, 'Prizes');
        $row['startSnapshot'] = $snapshot;
    }
    $flags = ['attackerOnField'=>(bool)$snapshot['candidates'],
        'attackerPowered'=>(bool)array_filter($snapshot['candidates'], fn($c)=>$c['energyGap']===0),
        'attackerActive'=>(bool)array_filter($snapshot['candidates'], fn($c)=>$c['zone']==='Active'),
        'attachmentSpent'=>$snapshot['energyUsed'], 'supporterSpent'=>$snapshot['supporterUsed']] + $snapshot['milestones'];
    // Availability on the prohibited first turn is not an opening attack opportunity.
    if ($row['reached']) {
        $row['anyLegalSeen'] = $row['anyLegalSeen'] || $snapshot['anyLegal'];
        $row['goalLegalSeen'] = $row['goalLegalSeen'] || $snapshot['goalLegal'];
        $flags['anyAttackLegal'] = $snapshot['anyLegal']; $flags['goalAttackLegal'] = $snapshot['goalLegal'];
    }
    foreach ($flags as $name=>$value) if ($value && !isset($row['milestones'][$name]))
        $row['milestones'][$name] = ['turn'=>GetPlayerTurns($player), 'step'=>$row['steps']];
    $row['snapshot'] = $snapshot;
    PokeSetVar('openingStats', $rows);
}
/** Called after validation, before mutation. Chooser labels include only offered information. */
function PokeOpeningAction(array $action): void {
    PokeOpeningObserve();
    $rows = PokeVar('openingStats', []); $player = (int)$action['player'];
    if (!isset($rows[$player]) || $rows[$player]['status'] !== 'pending' || GetCurrentPhase() !== 'MAIN'
        || GetTurnPlayer() !== $player || GetPlayerTurns($player) > $rows[$player]['eligibleTurn']) return;
    $row = &$rows[$player]; ++$row['steps'];
    $entry = ['turn'=>GetPlayerTurns($player), 'step'=>$row['steps'], 'type'=>$action['type']];
    // Record resource commitment at the accepted action, even when its effect awaits choices.
    $spent = $action['type']==='attach' ? 'attachmentSpent' : null;
    if ($action['type']==='trainer' && CardTrainerType(GetZoneObject($action['source'])->CardID)==='Supporter') $spent='supporterSpent';
    if ($spent && !isset($row['milestones'][$spent])) $row['milestones'][$spent]=['turn'=>$entry['turn'], 'step'=>$entry['step']];
    foreach (['source','target','copySource'] as $key) if (isset($action[$key])) {
        $entry[$key] = GetZoneObject($action[$key])->CardID;
        $entry[$key.'Name'] = CardName($entry[$key]) ?? $entry[$key];
    }
    if ($action['type']==='decision') {
        $decision = PokeDecisionOptions($player); $entry['prompt'] = $decision['prompt'];
        $entry['choices'] = [];
        foreach ($decision['choices'] as $choice) if (in_array($choice['value'], explode('&', $action['value']), true))
            $entry['choices'][] = $choice['label'];
        $entry['value'] = $action['value'];
    }
    if (count($row['trace']) < 160) $row['trace'][] = $entry; else $row['traceTruncated'] = true;
    if ($action['type']==='attack' && $row['reached']) {
        $profile = PokeOpeningProfile($row['deck']);
        $obj = GetZoneObject($action['source']); $id = GetZoneObject($action['copySource'] ?? $action['source'])->CardID;
        $row['attackDeclared'] = true; $row['attack'] = ['card'=>$id, 'attacker'=>$obj->CardID, 'index'=>$action['index']];
        $row['goalAttack'] = PokeOpeningMatches($profile, $id, $action['index']);
        $row['goalReadyAtDeclaration'] = $row['goalAttack'] && ($profile['ready'])($player, $obj);
    }
    PokeSetVar('openingStats', $rows);
}
function PokeOpeningResolved(int $player): void {
    $rows = PokeVar('openingStats', []);
    if (isset($rows[$player]) && $rows[$player]['status']==='pending' && $rows[$player]['attackDeclared']) {
        $rows[$player]['attackResolved'] = true;
        $rows[$player]['fullyEnabledAttack'] = $rows[$player]['goalReadyAtDeclaration'] ?? false; PokeSetVar('openingStats', $rows);
    }
}
function PokeOpeningFinishTurn(): void {
    $before = PokeVar('openingStats', []);
    if (empty($before[GetTurnPlayer()]['attackDeclared'])) PokeOpeningObserve();
    $rows = PokeVar('openingStats', []); $player = GetTurnPlayer();
    if (!isset($rows[$player]) || !$rows[$player]['reached'] || $rows[$player]['status'] !== 'pending') return;
    $row = &$rows[$player]; $row['status'] = 'complete';
    $row['blockers'] = $row['snapshot']['blockers'] ?? [];
    if ($row['fullyEnabledAttack']) $row['blockers'] = [];
    if ($row['goalAttack'] && empty($row['goalReadyAtDeclaration']) && !in_array('goal_prerequisite_unmet',$row['blockers'],true))
        $row['blockers'][] = 'goal_prerequisite_unmet';
    if (!$row['attackDeclared'] && $row['anyLegalSeen']) $row['blockers'][] = 'legal_attack_unused';
    if (!$row['goalAttack'] && $row['goalLegalSeen']) $row['blockers'][] = 'legal_goal_unused';
    if ($row['attackDeclared'] && !$row['goalAttack']) $row['blockers'][] = 'fallback_attack';
    if ($row['attackDeclared'] && !$row['attackResolved']) $row['blockers'][] = 'attack_failed_to_resolve';
    $row['prizesTaken'] = max(0, ($row['prizesAtStart'] ?? PokeCount($player, 'Prizes'))-PokeCount($player, 'Prizes'));
    foreach (array_reverse(PokeVar('damageTurns', [])) as $turn) if ($turn['player']===$player) { $row['damage'] = $turn['damage']; break; }
    PokeSetVar('openingStats', $rows);
}
function PokeOpeningEndGame(): void {
    PokeOpeningFinishTurn();
    $rows = PokeVar('openingStats', []);
    foreach ($rows as &$row) if ($row['status']==='pending') $row['status']='game_ended_before_opportunity';
    PokeSetVar('openingStats', $rows);
}
function PokeOpeningResults(): array {
    $rows = PokeVar('openingStats', []);
    foreach ($rows as &$row) if ($row['status']==='pending') $row['status']='incomplete';
    return array_values($rows);
}

/** Completed match denominators only; capped/error matches remain explicit. */
function PokeOpeningSummary(array $games): array {
    $groups = [];
    foreach ($games as $game) foreach ($game['openingStats'] ?? [] as $row) {
        $key = $row['deck'].':'.$row['order'];
        if (!isset($groups[$key])) $groups[$key] = ['deck'=>$row['deck'], 'order'=>$row['order'],
            'goal'=>$row['goal'], 'games'=>0, 'reached'=>0, 'earlyEnded'=>0, 'incomplete'=>0,
            'declared'=>0, 'resolved'=>0, 'goalAttacks'=>0, 'enabled'=>0, 'blockers'=>[],
            'conversion'=>['enabled'=>['games'=>0,'wins'=>0], 'missed'=>['games'=>0,'wins'=>0]]];
        $group = &$groups[$key];
        if ($game['status'] !== 'complete' || $row['status']==='incomplete') { ++$group['incomplete']; unset($group); continue; }
        ++$group['games'];
        if ($row['status']==='game_ended_before_opportunity') { ++$group['earlyEnded']; unset($group); continue; }
        if ($row['status'] !== 'complete') { unset($group); continue; }
        ++$group['reached'];
        $group['declared'] += (int)$row['attackDeclared']; $group['resolved'] += (int)$row['attackResolved'];
        $group['goalAttacks'] += (int)$row['goalAttack']; $group['enabled'] += (int)$row['fullyEnabledAttack'];
        foreach ($row['blockers'] as $blocker) $group['blockers'][$blocker] = ($group['blockers'][$blocker] ?? 0)+1;
        $conversion = $row['fullyEnabledAttack'] ? 'enabled' : 'missed';
        ++$group['conversion'][$conversion]['games'];
        $group['conversion'][$conversion]['wins'] += (int)($game['winner']===$row['player']);
        unset($group);
    }
    foreach ($groups as &$group) {
        $group['attackRateReached'] = $group['reached'] ? $group['declared']/$group['reached'] : null;
        $group['enabledRateReached'] = $group['reached'] ? $group['enabled']/$group['reached'] : null;
        $group['enabledRateAllGames'] = $group['games'] ? $group['enabled']/$group['games'] : null;
    }
    return array_values($groups);
}
