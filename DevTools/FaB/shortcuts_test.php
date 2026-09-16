<?php
require_once __DIR__ . '/smoke.php';
$failures = [];
$resetShortcuts = function(int $seats = 2) {
    InitializeGamestate();
    $GLOBALS['playerID'] = 1;
    SetSeatOrder(substr('1234', 0, $seats)); SetLiveSeats(substr('1234', 0, $seats));
    SetTurnPlayer(1); SetTurnNumber(1); SetCurrentPhase('MAIN'); SetPriorityPlayer(1);
    FaBSetState(FaBStateDefaults());
    for ($p = 1; $p <= $seats; ++$p) {
        AddHero($p, CardID:'ira_crimson_haze', Owner:$p, Controller:$p);
        AddHealth($p, 20); AddActionPoints($p, 1);
    }
};
$resetShortcuts();
foreach (GetShortcutWindowRegistry() as $id => $spec) {
    $check(ShouldAutoPassShortcutWindow(1, $id) === !in_array($id, ['BLOCK','ATTACK_REACTION','DEFENSE_REACTION'], true), 'Wrong default for '.$id);
}
$state = FaBGetState(); $state['attacker'] = 1; $state['defender'] = 2;
$state['attackTarget'] = ['type'=>'HERO','player'=>2];
foreach (['ACTION','PRIORITY','ATTACK','DEFEND_DECLARE','DEFEND_PRIORITY','REACTION','DAMAGE','RESOLUTION','END_PHASE'] as $window) {
    $state['window'] = $window;
    $check(isset(GetShortcutWindowRegistry()[FaBShortcutWindow(1, $state)]), 'Unmapped priority window '.$window);
}
$state['window'] = 'PITCH';
$check(FaBShortcutWindow(1, $state) === '', 'Payment must never be shortcut.');

// Enabled shortcuts pass even when an instant is legal; disabling one stops it.
foreach ([false, true] as $enabled) {
    $resetShortcuts();
    AddHand(1, CardID:'sigil_of_solace_red', Owner:1, Controller:1);
    $state = FaBGetState(); $state['window']='ATTACK'; $state['combatStep']='ATTACK';
    $state['combatOpen']=true; $state['attacker']=1; $state['defender']=2;
    $state['attackTarget']=['type'=>'HERO','player'=>2]; FaBSetState($state);
    $check(CanPlayCard(1, 'p1Hand-0'), 'Instant fixture is not playable.');
    SetShortcutPreferencesState(1, ['windows'=>['ATTACK_PRIORITY'=>$enabled]]);
    FaBAutoPassShortcuts();
    $check(FaBGetState()['window'] === ($enabled ? 'DEFEND_DECLARE' : 'ATTACK'), 'Before-block shortcut ignored preference or stopped for an instant.');
}

// Reaction priority stops only for the attacker and actual defending heroes.
$resetShortcuts(4);
$state = FaBGetState(); $state['window']='REACTION'; $state['combatStep']='REACTION';
$state['combatOpen']=true; $state['attacker']=1; $state['defender']=4;
$state['attackTarget']=['type'=>'HERO','player'=>4]; FaBSetState($state);
SetPriorityPlayer(2); FaBAutoPassShortcuts();
$check(intval(GetPriorityPlayer()) === 4 && intval(GetConsecutivePasses()) === 2, 'Uninvolved seats did not skip reactions or defender was skipped.');
SetPriorityPlayer(1); SetConsecutivePasses(0); FaBAutoPassShortcuts();
$check(intval(GetPriorityPlayer()) === 1 && intval(GetConsecutivePasses()) === 0, 'Attack reactions were skipped by default.');
$state['attackTargets']=[['type'=>'HERO','player'=>2],['type'=>'HERO','player'=>4]];
$check(FaBShortcutWindow(2,$state)==='DEFENSE_REACTION' && FaBShortcutWindow(4,$state)==='DEFENSE_REACTION', 'Multiple defenders did not retain defense reactions.');

$resetShortcuts();
AddHand(1, CardID:'wounding_blow_red', Owner:1, Controller:1);
$check(FaBPlayerHasPriorityAction(1), 'Action fixture has no legal action.');
FaBAutoPassShortcuts();
$check(FaBGetState()['window']==='ACTION' && intval(GetPriorityPlayer())===1, 'Own playable action was skipped.');
$state=FaBGetState(); $state['window']='END_PHASE'; FaBSetState($state);
FaBAutoPassShortcuts();
$check(FaBGetState()['window']==='END_PHASE' && intval(GetTurnNumber())===1, 'Arsenal opportunity was skipped.');

$resetShortcuts();
$state=FaBGetState(); $state['window']='ATTACK'; FaBSetState($state);
DecisionQueueController::AddDecision(2, 'YESNO', 'Choose', 1);
FaBAutoPassShortcuts();
$check(intval(GetConsecutivePasses())===0 && FaBGetState()['window']==='ATTACK', 'Shortcut passed while another seat had a choice.');

// All-enabled empty four-seat windows progress without running through later turns.
$resetShortcuts(4);
FaBAutoPassShortcuts();
$check(intval(GetTurnNumber())===2 && intval(GetTurnPlayer())===2, 'Empty priority windows stalled or skipped multiple turns.');
if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: $failure\n");
    exit(1);
}
echo "FaB shortcut checks passed.\n";
