<?php
require_once __DIR__ . '/upf_test.php';
$reset();
$state = FaBGetState(); $state['passiveSeats']=[2,3,4]; FaBSetState($state);
foreach ([2,3,4] as $seat) AddHealth($seat,1);
for ($i=0; $i<4; ++$i) AddHand(1,CardID:'wounding_blow_red');
for ($step=0; $step<100 && intval(GetWinner()) === 0; ++$step) {
    $check(intval(GetPriorityPlayer()) === 1, 'Goldfish left priority waiting for a bot.');
    if (intval(GetPriorityPlayer()) !== 1) break;
    $GLOBALS['playerID']=1; $state=FaBGetState(); $queue=GetDecisionQueue(1);
    if ($queue) {
        $answer=explode('&',$queue[0]->Param)[0];
        $controller=new DecisionQueueController(); $controller->PopDecision(1); $controller->ExecuteStaticMethods(1,$answer);
    } elseif ($state['window'] === 'ACTION' && intval(GetActionPoints(1)) > 0 && FaBHandCount(1)>0) {
        $refs=FaBChoiceRefs(1,'Hand'); $check(DoPlayCard(1,$refs[0]), 'Could not attack goldfish.');
    } else { $check(FaBPassPriority(1), 'Human priority pass failed.'); }
    GameAfterEngineAction([],[]);
}
$check(intval(GetWinner()) === 1, 'Human could not finish a match against three goldfish.');
$reset(); $state=FaBGetState(); $state['passiveSeats']=[2,4]; FaBSetState($state);
FaBEndTurn(1);
$check(intval(GetTurnPlayer()) === 3, 'Mixed room failed to skip a goldfish turn.');
if ($failures) { foreach ($failures as $failure) fwrite(STDERR,"FAIL: $failure\n"); exit(1); }
echo "UPF bot gameplay passed.\n";
