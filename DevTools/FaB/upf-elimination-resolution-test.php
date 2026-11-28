<?php
require_once __DIR__ . '/../../Core/EngineActionRunner.php';

EngineLoadRootRuntime('FaBSim');
$GLOBALS['gameName'] = 'regression_upf_elimination_resolution';
$GLOBALS['playerID'] = 1;

function requireEliminationCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function passEliminationWindow(): void {
    $window = FaBGetState()['window'];
    for ($i = 0; $i < 5 && FaBGetState()['window'] === $window && !FaBHasPendingDecision(); ++$i) {
        requireEliminationCheck(FaBPassPriority(intval(GetPriorityPlayer()), true), 'Could not pass priority.');
        GameAfterEngineAction('PASS', true);
    }
}

foreach ([3, 4] as $defenderLife) {
    InitializeGamestate();
    SetSeatOrder('1234'); SetLiveSeats('1234'); SetTurnPlayer(1); SetTurnNumber(1);
    SetCurrentPhase('MAIN'); SetPriorityPlayer(1);
    $state = FaBStateDefaults(); $state['gameMode'] = 'UPF'; FaBSetState($state);
    foreach (range(1, 4) as $seat) {
        AddHero($seat, CardID: $seat === 1 ? 'fai' : 'rhinar', Owner: $seat, Controller: $seat);
        AddHealth($seat, $seat === 2 ? $defenderLife : 20);
        AddActionPoints($seat, 0);
        SetShortcutPreferencesState($seat, ['windows' => array_fill_keys(array_keys(GetShortcutWindowRegistry()), false)]);
    }
    $attack = AddCombatChain(1, CardID: 'rising_resentment_red', Owner: 1, Controller: 1, Role: 'ATTACK', ChainLink: 1);
    $followUp = AddHand(1, CardID: 'lava_vein_loyalty_red', Owner: 1, Controller: 1);
    $target = FaBAttackTargetDescriptor(FaBFindUID(intval(GetHero(2)[0]->UniqueID)));
    FaBSetState(array_replace($state, ['window'=>'REACTION', 'combatStep'=>'REACTION', 'combatOpen'=>true,
        'chainLink'=>1, 'attacker'=>1, 'defender'=>2, 'attackUID'=>intval($attack->UniqueID), 'attackTarget'=>$target, 'attackTargets'=>[$target]]));
    passEliminationWindow();
    requireEliminationCheck(intval(GetHealth(2)) === $defenderLife - 3, 'Wrong hit damage.');
    requireEliminationCheck(FaBSeatIsLive(2) === ($defenderLife > 3), 'Wrong elimination state.');
    requireEliminationCheck(FaBGetState()['combatStep'] === 'DAMAGE' && FaBFaiChainCount(1) === 1, 'Hit choice lost the active Draconic link.');
    $choice = GetDecisionQueue(1)[0] ?? null;
    requireEliminationCheck($choice !== null && $choice->Type === 'MZMAYCHOOSE' && $choice->Param === 'p1Hand-0', 'Expected a real optional hand choice.');
    $dq = new DecisionQueueController();
    $dq->PopDecision(1); $dq->ExecuteStaticMethods(1, 'p1Hand-0');
    GameAfterEngineAction('DECISION', true);
    $find = FaBFindUID(intval($followUp->UniqueID));
    requireEliminationCheck(($find['zone'] ?? '') === 'Banish' && intval($find['object']->PlayableFromBanish) === 1, 'Selected attack did not become playable from banish.');
    if (FaBGetState()['window'] === 'DAMAGE') passEliminationWindow();
    requireEliminationCheck(FaBGetState()['window'] === 'RESOLUTION' && intval(GetActionPoints(1)) === 1, 'Lethal or nonlethal go again did not grant exactly one action point.');
    requireEliminationCheck(array_column(FaBLegalAttackTargets(1), 'player') === ($defenderLife === 3 ? [] : [2]), 'Open chain lost its original focus.');
    passEliminationWindow();
    $find = FaBFindUID(intval($followUp->UniqueID));
    requireEliminationCheck(FaBGetState()['window'] === 'ACTION' && !FaBGetState()['combatOpen'] && intval(GetActionPoints(1)) === 1, 'Normal close lost the action point.');
    requireEliminationCheck(array_column(FaBLegalAttackTargets(1), 'player') === ($defenderLife === 3 ? [3, 4] : [2, 4]), 'After close, the wrong heroes are adjacent.');
    requireEliminationCheck(CanPlayCard(1, $find['mzID']), 'Closing the chain lost turn-long banish permission.');
    requireEliminationCheck(DoPlayCard(1, $find['mzID']), 'Could not announce a banished follow-up on a new chain.');
    $choice = GetDecisionQueue(1)[0] ?? null;
    $newTarget = $defenderLife === 3 ? 'p3Hero-0' : 'p2Hero-0';
    requireEliminationCheck($choice !== null && in_array($newTarget, explode('&', $choice->Param), true), 'The next legal hero was not available.');
    $dq->PopDecision(1); $dq->ExecuteStaticMethods(1, $newTarget);
    GameAfterEngineAction('DECISION', true);
    requireEliminationCheck(intval(GetActionPoints(1)) === 0 && FaBStackTop()?->CardID === 'lava_vein_loyalty_red', 'The next attack did not pay its action point and enter the stack.');
}
echo "PASS: Rising Resentment hit choice, go again, chain focus and follow-up attack with or without a defender knockout.\n";

