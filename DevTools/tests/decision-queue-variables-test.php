<?php
require dirname(__DIR__, 2).'/Core/DecisionQueueController.php';
$variables = '{}'; $checks = 0;
function GetDecisionQueueVariables() { return $GLOBALS['variables']; }
function SetDecisionQueueVariables($value) { $GLOBALS['variables'] = $value; }
function variableCheck($condition, $message) {
    ++$GLOBALS['checks'];
    if (!$condition) throw new RuntimeException($message);
}
DecisionQueueController::StoreVariable('nested', ['trace'=>[['value'=>1]]]);
$snapshot = GetDecisionQueueVariables();
$copy = DecisionQueueController::GetVariable('nested');
$copy['trace'][0]['value'] = 99;
variableCheck(DecisionQueueController::GetVariable('nested')['trace'][0]['value'] === 1, 'Caller mutation leaked into cached data');
DecisionQueueController::StoreVariable('nested', ['trace'=>[['value'=>2]]]);
variableCheck(DecisionQueueController::GetVariable('nested')['trace'][0]['value'] === 2, 'Store did not invalidate cache');
SetDecisionQueueVariables($snapshot);
variableCheck(DecisionQueueController::GetVariable('nested')['trace'][0]['value'] === 1, 'Snapshot restore returned stale data');
SetDecisionQueueVariables('{"other":true}');
variableCheck(DecisionQueueController::GetVariable('nested') === null, 'Consecutive game retained old variables');
variableCheck(DecisionQueueController::GetVariable('other') === true, 'Direct setter was not observed');
DecisionQueueController::ClearVariable('other');
variableCheck(DecisionQueueController::GetVariable('other') === null, 'ClearVariable returned stale data');
DecisionQueueController::StoreVariable('temporary', 7);
DecisionQueueController::ClearVariables();
variableCheck(DecisionQueueController::GetVariable('temporary') === null, 'ClearVariables returned stale data');
foreach (['PASS=2|winner=1'=>['PASS'=>'2','winner'=>'1'], '3'=>['PASS'=>'3'], ''=>[], 'null'=>[], 'invalid'=>[]] as $raw=>$expected) {
    variableCheck(DecisionQueueController::DecodeVariablesPublic($raw) === $expected, 'Legacy or malformed decoding changed');
}
SetDecisionQueueVariables('{"nullable":null,"zero":0,"false":false}');
variableCheck(DecisionQueueController::GetVariable('nullable') === null && DecisionQueueController::GetVariable('zero') === 0 && DecisionQueueController::GetVariable('false') === false, 'Scalar types changed');
$frame = DecisionQueueController::BeginAwaitFrame('test', ['value'=>42]);
variableCheck(DecisionQueueController::GetVariable('__awaitFrames')[$frame]['locals']['value'] === 42, 'Await frame write returned stale data');
DecisionQueueController::SetAwaitFrameLocal($frame, 'value', 43);
variableCheck(DecisionQueueController::GetVariable('__awaitFrames')[$frame]['locals']['value'] === 43, 'Await frame update returned stale data');
DecisionQueueController::FinishAwaitFrame($frame);
variableCheck(DecisionQueueController::GetVariable('__awaitFrames') === null, 'Await frame cleanup returned stale data');
echo "PASS $checks decision queue variable checks\n";
