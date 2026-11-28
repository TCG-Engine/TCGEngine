<?php
/** Small authoring setup checks; no game files, fixtures, or replay suite. */
declare(strict_types=1);
$repo = dirname(__DIR__, 3);
chdir($repo);
require_once $repo . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('FaBSim');
require_once __DIR__ . '/initial_agent_setup.php';
$checks = 0;
function agentSame($actual, $expected, string $label): void {
    ++$GLOBALS['checks'];
    if ($actual !== $expected) throw new RuntimeException($label . ': values differ.');
}
function agentReject(array $case, array $seats, string $label): void {
    try { FCRInitialAgentForms($case, $seats); }
    catch (RuntimeException $expected) { ++$GLOBALS['checks']; return; }
    throw new RuntimeException($label . ': expected setup rejection.');
}
$seats = [1, 2, 3, 4];
agentSame(FCRInitialAgentForms([], $seats), [], 'Legacy absent field');
agentSame(FCRInitialAgentForms(['initialAgentBases' => []], $seats), [], 'Explicit empty field');
$case = ['heroes' => ['arakni_orb_weaver', 'dorinthea', 'dorinthea', 'dorinthea'],
    'initialAgentBases' => [1 => 'arakni']];
$forms = FCRInitialAgentForms($case, $seats);
agentSame($forms, [1 => ['base' => 'arakni', 'form' => 'arakni_orb_weaver', 'index' => 2]], 'Exact native form binding');
agentReject(array_replace($case, ['initialAgentBases' => [5 => 'arakni']]), $seats, 'Inactive seat');
agentReject(array_replace($case, ['initialAgentBases' => ['arakni']]), $seats, 'List is not a seat map');
agentReject(array_replace($case, ['initialAgentBases' => [1 => 'dorinthea']]), $seats, 'Non-Arakni base');
agentReject(array_replace($case, ['initialAgentBases' => [1 => 'arakni_huntsman']]), $seats, 'Adult base in UPF');
agentReject(array_replace($case, ['heroes' => ['arakni', 'dorinthea', 'dorinthea', 'dorinthea']]), $seats, 'Current form must be Agent');
agentReject(array_replace($case, ['heroes' => ['arakni_trap_door', 'dorinthea', 'dorinthea', 'dorinthea']]), $seats, 'Pending entry trigger outside setup contract');
InitializeGamestate();
SetSeatOrder('1234'); SetLiveSeats('1234'); SetTurnPlayer(1); SetTurnNumber(2);
SetCurrentPhase('MAIN'); SetPriorityPlayer(1); FaBSetState(FaBStateDefaults());
foreach ($seats as $seat) {
    AddHero($seat, CardID: $forms[$seat]['base'] ?? $case['heroes'][$seat - 1], Owner: $seat, Controller: $seat);
    AddHealth($seat, 20);
}
$hero = GetHero(1)[0];
$identity = [$hero->UniqueID, $hero->Owner, $hero->Controller, $hero->Status, GetHealth(1)];
$uid = GetUniqueIDCounter();
foreach ($forms as $seat => $form) FaBHNTBecome($seat, $form['index']);
agentSame($hero->CardID, 'arakni_orb_weaver', 'Canonical current form');
agentSame(FaBObjectCounters($hero), ['HNT_BASE_HERO' => 'arakni'], 'Native base Hero counter');
agentSame([$hero->UniqueID, $hero->Owner, $hero->Controller, $hero->Status, GetHealth(1)], $identity, 'Identity and health preserved');
agentSame(GetUniqueIDCounter(), $uid, 'No replacement object allocated');
agentSame(count(GetStack()), 0, 'No initial become trigger for Orb');
agentSame(GetHero(2)[0]->CardID, 'dorinthea', 'Unmapped Hero unchanged');
$before = json_encode(FaBObjectCounters($hero));
foreach (FCRInitialAgentForms([], $seats) as $seat => $form) FaBHNTBecome($seat, $form['index']);
agentSame(json_encode(FaBObjectCounters($hero)), $before, 'Absent option performs no transition');
$triggerCase = array_replace($case, ['heroes' => ['arakni_trap_door', 'dorinthea', 'dorinthea', 'dorinthea'],
    'allowInitialAgentEntryTrigger' => true]);
$triggerForms = FCRInitialAgentForms($triggerCase, $seats);
agentSame($triggerForms[1]['index'], 5, 'Explicit Trap Door form');
agentReject(array_replace($case, ['allowInitialAgentEntryTrigger' => true]), $seats, 'Flag without an entry form');
agentReject(array_replace($triggerCase, ['allowInitialAgentEntryTrigger' => 'true']), $seats, 'Flag must be boolean');
agentReject(array_replace($triggerCase, ['initialAgentBases' => []]), $seats, 'Flag without mapping');
$twoForms = $triggerCase;
$twoForms['heroes'][1] = 'arakni_orb_weaver';
$twoForms['initialAgentBases'][2] = 'arakni';
agentReject($twoForms, $seats, 'Mixed initial forms with entry layer');
foreach ($triggerForms as $seat => $form) FaBHNTBecome($seat, $form['index']);
agentSame($hero->CardID, 'arakni_trap_door', 'Native entry form');
agentSame(FaBObjectCounters($hero), ['HNT_BASE_HERO' => 'arakni'], 'Entry preserves original base');
agentSame([$hero->UniqueID, $hero->Owner, $hero->Controller, $hero->Status, GetHealth(1)], $identity, 'Entry preserves physical identity and life');
agentSame(count(GetStack()), 1, 'One native become trigger queued');
$layer = GetStack()[0];
agentSame($layer->CardID, 'arakni_trap_door', 'Native layer source card');
agentSame(intval($layer->SourceUniqueID), 1, 'Native layer physical Hero source');
agentSame(intval($layer->Controller), 1, 'Native layer controller');
agentSame($layer->Params['rosTrigger'], true, 'Native ROS trigger classification');
agentSame(GetUniqueIDCounter(), $uid + 1, 'Only layer identity allocated');
agentSame(FaBGetState()['window'], 'PRIORITY', 'Initial layer is responseable');
echo "[PASS] Initial Agent setup: $checks checks, no game files or saved fixtures.\n";
