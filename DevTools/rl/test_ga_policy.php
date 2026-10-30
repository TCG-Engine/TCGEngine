<?php
// Unit tests use deliberately hostile hidden-zone sentinels: reads would throw.
define('RL_TRAINER_LIBRARY_ONLY', true);
require_once __DIR__ . '/train_selfplay_php.php';
function GetCurrentPhase() { return 'MAIN'; }
function GetTurnPlayer() { return 1; }
function GetField($p) { return $GLOBALS['fields'][$p]; }
function GetHand($p) { return $GLOBALS['hands'][$p]; }
function GetMemory($p) { return $GLOBALS['memory'][$p]; }
function GetLiveEffectStackEntries() { return []; }
function GABotDeckKnowledge($p) { return ['size' => 40, 'unseen' => ['known' => 4], 'exact' => false]; }
function GABotLiveCount($zone) { return count($zone); }
function GABotOwnChampion($p) { return $GLOBALS['fields'][$p][0] ?? null; }
function GABotRemainingHP($obj) { return 10 - $obj->Damage; }
function ObjectCurrentPower($obj) { return 3; }
function GetZoneObject($mz) { return $GLOBALS['zones'][$mz] ?? null; }
class HiddenCard { public function __get($key) { throw new Exception('Hidden identity read: ' . $key); } }
$checks = 0;
function check($condition, $label) { global $checks; ++$checks; if (!$condition) throw new Exception($label); }
$unit = (object)['CardID' => 'visible', 'Status' => 2, 'Damage' => 0];
$GLOBALS['fields'] = [1 => [$unit], 2 => [clone $unit]];
$GLOBALS['hands'] = [1 => [(object)['CardID' => 'mine']], 2 => [new HiddenCard]];
$GLOBALS['memory'] = [1 => [(object)['CardID' => 'my-memory']], 2 => [new HiddenCard]];
$key = GaRlStateKey(1, []);
$GLOBALS['hands'][2] = [new HiddenCard];
$GLOBALS['memory'][2] = [new HiddenCard];
check($key === GaRlStateKey(1, []), 'Opponent hidden identities must not change state');
$GLOBALS['zones'] = ['myField-0' => $unit, 'myField-9' => $unit];
check(GaRlActionKey(['mode'=>10002,'cardID'=>'myField-0!FSM!'], []) === GaRlActionKey(['mode'=>10002,'cardID'=>'myField-9!FSM!'], []), 'Stable action keys across positions');
foreach ([1, 2] as $seat) {
  $policy = new RlTabularPolicy(20, 1, .05, 'GrandArchiveSim:public-v1', 'ga-semantic-v1');
  $steps = [];
  foreach ([1, 2] as $p) $steps[] = ['state_key'=>'seat'.$p, 'turn_player'=>$p, 'action_index'=>0, 'action_keys'=>[0=>'play:a',1=>'pass'], 'legal_indices'=>[0,1]];
  $delta = $policy->episodeDeltaForPlayer($steps, $seat, 1);
  $policy->applyDelta($delta);
  check(isset($policy->logits['seat'.$seat]) && !isset($policy->logits['seat'.(3-$seat)]), 'Opponent cannot update learner');
  check($policy->logits['seat'.$seat]['play:a'] > $policy->logits['seat'.$seat]['pass'], 'Reward increases selected action');
  $before = $policy->payload();
  $policy->selectAction('unseen-evaluation-state', [1,1], 0, ['play:a','pass']);
  check($before === $policy->payload(), 'Inference must not mutate checkpoint');
  $loaded = RlTabularPolicy::fromPayload($before, 20, 1, .05);
  check($loaded->logits === $policy->logits, 'Semantic checkpoint round trip');
}
$coverage = ['decisions'=>0, 'choices'=>0, 'knownStateChoices'=>0, 'learnedPreferenceChoices'=>0];
$policy->logits['coverage'] = ['a'=>1.0, 'b'=>1.0, 'c'=>2.0];
GaRlRecordPolicyCoverage($coverage, $policy, 'unseen', [0,1], ['a','b']);
GaRlRecordPolicyCoverage($coverage, $policy, 'coverage', [0], ['a']);
GaRlRecordPolicyCoverage($coverage, $policy, 'coverage', [0,1], ['a','b']);
GaRlRecordPolicyCoverage($coverage, $policy, 'coverage', [0,1], ['a','c']);
check($coverage === ['decisions'=>4, 'choices'=>3, 'knownStateChoices'=>2, 'learnedPreferenceChoices'=>1], 'Coverage distinguishes known states, forced moves, and effective preferences');
$compact = GaRlCompactStateKey(1, []);
$GLOBALS['hands'][1][0]->CardID = 'different-own-card';
$GLOBALS['fields'][1][0]->CardID = 'different-public-unit';
check($compact === GaRlCompactStateKey(1, []), 'Equivalent public features generalize across identities');
$GLOBALS['fields'][1][0]->Damage = 9;
check($compact !== GaRlCompactStateKey(1, []), 'Critical champion health remains distinguishable');
$GLOBALS['fields'][1][0]->Damage = 0;
$GLOBALS['hands'][2] = [new HiddenCard, new HiddenCard];
$GLOBALS['memory'][2] = [new HiddenCard];
check($compact === GaRlCompactStateKey(1, []), 'Compact state excludes opponent hidden information');
$compactPolicy = new RlTabularPolicy(20, 1, .05, 'GrandArchiveSim:compact-v2', 'ga-semantic-v1');
$compactPolicy->applyDelta($compactPolicy->episodeDeltaForPlayer([
  ['state_key'=>$compact, 'turn_player'=>1, 'action_index'=>0, 'action_keys'=>['play','pass'], 'legal_indices'=>[0,1]]
], 1, 1));
$probs = $compactPolicy->actionProbabilities(GaRlCompactStateKey(1, []), [0,1], ['play','pass']);
check($probs[0][1] > $probs[1][1], 'Reward transfers to an equivalent new position');
$roundtrip = RlTabularPolicy::fromPayload($compactPolicy->payload(), 20, 1, .05);
check($roundtrip->stateKeyVersion === 'GrandArchiveSim:compact-v2' && $roundtrip->logits === $compactPolicy->logits, 'Compact checkpoint round trip');
echo "$checks checks passed\n";

$catalog = GaRlExpandActions(['playerID'=>1,'decisionType'=>'MZMULTICHOOSE','minimum'=>1,'maximum'=>2,'candidates'=>['a','b','c'],'actions'=>[]]);
check(count($catalog['actions']) === 6, 'All singleton and pair choices available at inference');
check(!$catalog['catalogTruncated'], 'Small catalogs exhaustive');
$linear = new RlTabularPolicy(20, 1, .05, 'GrandArchiveSim:linear-v1', 'ga-semantic-v1');
$ak = [GaRlActionKey(['mode'=>100,'cardID'=>'myField-0@Activate-0'], []), GaRlActionKey(['mode'=>100,'cardID'=>'PASS'], [])];
$state = json_encode(['ownHP'=>.5,'enemyHP'=>.2]);
$step = ['turn_player'=>1,'state_key'=>$state,'action_index'=>0,'action_keys'=>$ak,'legal_indices'=>[0,1]];
$linear->applyDelta($linear->episodeDeltaForPlayer([$step], 1, 1));
check($linear->actionProbabilities(json_encode(['ownHP'=>.6,'enemyHP'=>.3]), [0,1], $ak)[0][1] > .5, 'Shared weights generalize across distinct observations');
$copy = RlTabularPolicy::fromPayload($linear->payload(), 20, 1, .05);
check($copy->payload() === $linear->payload(), 'Linear checkpoint roundtrip');
$before = $linear->payload();
$linear->actionProbabilities($state, [0,1], $ak);
check($before === $linear->payload(), 'Linear inference immutable');
$opposite = new RlTabularPolicy(20, 1, .05, 'GrandArchiveSim:linear-v1', 'ga-semantic-v1');
$opposite->applyDelta($opposite->episodeDeltaForPlayer([$step], 1, -1));
check($opposite->actionProbabilities($state, [0,1], $ak)[0][1] < .5, 'Loss discourages selected action');
echo "Linear and catalog checks passed\n";
function CountAvailableReservePayments($p) { return 3; }
function GABotCombatContext($p) { return ['threat'=>5,'hp'=>4,'targetOwner'=>$p]; }
function CardType($id) { return 'ALLY'; }
function PropertyContains($value, $part) { return str_contains($value, $part); }
function GABotDrawOdds($p, $ids) { return ['lower'=>.1,'upper'=>.3]; }
$features = json_decode(GaRlFeatureState(1, []), true);
check($features['combatLethal'] === 1 && $features['drawLower:ALLY'] === .1, 'Combat and uncertain draw odds represented');
check(!str_contains(json_encode($features), 'my-memory'), 'No hidden identities in numeric features');
$first = GaRlFeatureState(1, []);
$GLOBALS['hands'][2] = array_map(fn($x) => new HiddenCard, $GLOBALS['hands'][2]);
check($first === GaRlFeatureState(1, []), 'Linear observations never read enemy hidden identities');
echo "Public feature checks passed\n";
$gradientPolicy = new RlTabularPolicy(20, .7, 1, 'GrandArchiveSim:linear-v1', 'ga-semantic-v1');
$gradient = $gradientPolicy->episodeDeltaForPlayer([$step], 1, 1);
$feature = 'role:activate';
$h = 1e-5;
$gradientPolicy->logits['__linear__'][$feature] = $h;
$plus = log($gradientPolicy->actionProbabilities($state, [0,1], $ak)[0][1]);
$gradientPolicy->logits['__linear__'][$feature] = -$h;
$minus = log($gradientPolicy->actionProbabilities($state, [0,1], $ak)[0][1]);
check(abs(($plus-$minus)/(2*$h) - $gradient['__linear__'][$feature]) < 1e-6, 'Shared-weight gradient matches finite differences including temperature');
echo "Gradient check passed\n";
