<?php
require_once __DIR__ . '/smoke.php';
require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../../FaBSim/LobbyAdapter.php';
$failures = [];
$reset = function () {
    InitializeGamestate();
    $GLOBALS['playerID'] = 1;
    SetSeatOrder('1234'); SetLiveSeats('1234'); SetTurnPlayer(1); SetTurnNumber(1);
    SetCurrentPhase('MAIN'); SetPriorityPlayer(1);
    $state = FaBStateDefaults(); $state['gameMode'] = 'UPF'; FaBSetState($state);
    foreach ([1,2,3,4] as $seat) {
        AddHero($seat, CardID:'rhinar', Owner:$seat, Controller:$seat);
        AddHealth($seat,20); AddActionPoints($seat,1);
        SetShortcutPreferencesState($seat, ['windows'=>['BLOCK'=>false,'ATTACK_REACTION'=>false,'DEFENSE_REACTION'=>false,'INSTANT_PRIORITY'=>false]]);
    }
};
$reset();
$check(FaBAdjacentOpponents(1) === [2,4], 'UPF must allow both neighbors, not the opposite seat.');
$check(array_column(FaBLegalAttackTargets(1),'player') === [2,4], 'UPF attack candidate list contains the wrong seats.');
$check(FaBResolveAttackTarget(['uid'=>GetHero(3)[0]->UniqueID],1) === null, 'A forged opposite-seat target was accepted.');
$state = FaBGetState(); $state['combatOpen']=true; $state['defender']=4; FaBSetState($state);
$check(array_column(FaBLegalAttackTargets(1),'player') === [4], 'UPF chain focus allowed changing opponents.');
FaBCloseCombatChain();
$check(count(FaBLegalAttackTargets(1)) === 2, 'Closing a chain did not release its focus.');
FaBEliminateSeat(2);
$check(FaBAdjacentOpponents(1) === [3,4], 'Elimination did not update adjacency.');
$check(FaBNextSeat(2) === 3, 'Eliminated seat must advance clockwise, not restart at seat 1.');
$reset();
foreach ([1,2,3,4] as $seat) {
    AddDeck($seat, CardID:'wounding_blow_red');
    DoDrawCard($seat,1);
    $check(FaBHandCount($seat) === 1, 'Opening draw failed for seat '.$seat);
}
$pitchDeck = FaBNormalizeTextDeck("Hero\n1 Rhinar\nDeck\n2 Wounding Blow (Red)\n2 Wounding Blow (Yellow)\n2 Wounding Blow (Blue)");
$check(array_values(array_unique($pitchDeck['mainDeck'])) === ['wounding_blow_red','wounding_blow_yellow','wounding_blow_blue'], 'Text import lost pitch colors.');
$room = (object)['isPrivate'=>true,'format'=>'upf','players'=>[]]; $adapter = new FaBLobbyAdapter();
foreach ([1,2,3,4] as $seat) {
    $p = new Player($seat,''); $p->setDeckOk(true); $p->setReady(true); $room->players[]=$p;
    $check((count($room->players) === 4) === empty($adapter->startBlockers($room)), 'UPF lobby start gate failed at '.$seat.' seats.');
}
$room->players[3]->setReady(false);
$check(!empty($adapter->startBlockers($room)), 'An unready fourth player did not block start.');
$room->players[3]->setReady(true); $room->players[3]->setDeckOk(false);
$check(!empty($adapter->startBlockers($room)), 'An invalid fourth deck did not block start.');
$reset();
foreach ([2,3,4] as $seat) AddHealth($seat,1);
for ($i=0; $i<4; ++$i) AddHand(1,CardID:'wounding_blow_red');
for ($step=0; $step<160 && intval(GetWinner()) === 0; ++$step) {
    $actor = intval(GetPriorityPlayer()); $state = FaBGetState(); $GLOBALS['playerID']=$actor;
    $queue = GetDecisionQueue($actor);
    if ($queue) {
        $answer = explode('&', $queue[0]->Param)[0];
        $controller = new DecisionQueueController(); $controller->PopDecision($actor); $controller->ExecuteStaticMethods($actor,$answer);
    } elseif ($actor === 1 && intval(GetTurnPlayer()) === 1 && $state['window'] === 'ACTION' && intval(GetActionPoints(1)) > 0 && FaBHandCount(1) > 0) {
        $refs=FaBChoiceRefs(1,'Hand');
        $check(DoPlayCard(1,$refs[0]), 'Four-player match could not announce an attack.');
    } else {
        $check(FaBPassPriority($actor), 'Four-player match became stuck passing priority.');
    }
    GameAfterEngineAction([],[]);
}
$check(intval(GetWinner()) === 1 && FaBLiveSeats() === [1], 'Four-player match did not finish after three eliminations.');
$reset();
AddHand(4, CardID:'wounding_blow_red');
FaBRequestIntimidate(1);
$check(GetDecisionQueue(1)[0]->Param === 'p2Hero-0&p4Hero-0', 'Intimidate did not offer both adjacent heroes.');
$customDQHandlers['FAB_INTIMIDATE'](1,[1],'p4Hero-0');
$check(FaBHandCount(4) === 0 && count(GetBanish(4)) === 1, 'Intimidate did not affect selected player four.');
$reset();
foreach ([1,2,3,4] as $seat) for ($i=0; $i<4; ++$i) AddDeck($seat,CardID:'wounding_blow_blue');
FaBEndTurn(1);
foreach ([1,2,3,4] as $seat) $check(FaBHandCount($seat) === 4, 'First turn refill failed for seat '.$seat);
// Exercise the card-click macro path: its static tail remains behind the target chooser.
foreach (['Hand', 'Arsenal', 'Weapons'] as $attackZone) {
    $reset(); AddResources(1,0);
    $source = match ($attackZone) {
        'Hand' => AddHand(1,CardID:'brutal_assault_red'),
        'Arsenal' => AddArsenal(1,CardID:'brutal_assault_red'),
        'Weapons' => AddWeapons(1,CardID:'romping_club',Owner:1,Controller:1,Status:2),
    };
    $pitch = AddHand(1,CardID:'wounding_blow_blue'); $pitchUID = intval($pitch->UniqueID);
    ActionMap('p1'.$attackZone.'-0');
    $dq = new DecisionQueueController();
    $check($dq->NextDecision(1)?->Type === 'MZCHOOSE', $attackZone.' attack did not request a target.');
    $check(!CanPlayCard(1,FaBFindUID($pitchUID)['mzID']), 'An unanswered target choice allowed another play.');
    $dq->PopDecision(1); $dq->ExecuteStaticMethods(1,'p4Hero-0');
    $state = FaBGetState();
    $check(FaBStackCount() === 1 && $state['window'] === 'PITCH' && is_array($state['pendingPayment']), $attackZone.' attack did not enter payment immediately after target selection.');
    $check(intval(FaBStackTop()?->Params['attackTarget']['player'] ?? 0) === 4 && $state['pendingAttackTarget'] === null, $attackZone.' attack did not consume its chosen target.');
    $check(DoPitchCard(1,FaBFindUID($pitchUID)['mzID']) && FaBGetState()['pendingPayment'] === null, $attackZone.' attack could not finish payment.');
    $check(FaBStackCount() === 1 && intval(GetActionPoints(1)) === 0 && count(GetDecisionQueue(1)) === 0, $attackZone.' attack duplicated its layer, cost, or target prompt.');
}
if ($failures) { foreach ($failures as $failure) fwrite(STDERR,"FAIL: $failure\n"); exit(1); }
echo "UPF checks passed.\n";
