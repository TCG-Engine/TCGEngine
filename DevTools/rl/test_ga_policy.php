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

function EffectiveCardType($obj) { return $obj->Type ?? 'CHAMPION'; }
function IsDistant($obj) { return !empty($obj->Distant); }
function FunctionalWeaponIsLoaded($obj) { return !empty($obj->Loaded); }
function GetCounterCount($obj, $name) { return $obj->Counters[$name] ?? 0; }
$weapon = (object)['CardID'=>'public-weapon','Status'=>2,'Damage'=>0,'Type'=>'WEAPON',
  'Loaded'=>true,'Counters'=>['durability'=>3]];
$GLOBALS['fields'][1][] = $weapon;
$GLOBALS['zones']['myField-1'] = $weapon;
$legal = ['actions'=>[['mode'=>100,'cardID'=>'myField-1']]];
$unloaded = GaRlFeatureState(1, $legal);
$weapon->Loaded = false;
check($unloaded !== GaRlFeatureState(1, $legal), 'Loaded and empty weapons distinguishable');
$weapon->Loaded = true;
$GLOBALS['fields'][1][0]->Distant = true;
$tactical = json_decode(GaRlFeatureState(1, $legal), true);
check($tactical['ownChampionDistant'] === 1 && $tactical['enemyChampionDistant'] === 0, 'Distant tracked independently by seat');
check($tactical['own:loadedWeapon'] === .2 && $tactical['enemy:loadedWeapon'] === 0, 'Public weapon counts tracked by seat');
$descriptor = $tactical['_actions'][GaRlActionKey($legal['actions'][0], $legal)];
check($descriptor['loadedWeapon'] === 1 && $descriptor['weaponDurability'] === .6, 'Weapon choice retains load and durability');
$swapped = json_decode(GaRlFeatureState(2, []), true);
check($swapped['enemyChampionDistant'] === 1 && $swapped['enemy:loadedWeapon'] === .2, 'Tactical features follow learner seat');
$weapon->removed = true;
check(json_decode(GaRlFeatureState(1, []), true)['own:weapon'] === 0, 'Removed weapons excluded');
echo "Tactical feature checks passed ($checks total checks)\n";

// Opposite rewards on the same observation must not leak across archetypes.
$adapter = $linear->copy();
$base = $adapter->logits;
$adapter->enableArchetypeAdapters();
$adapter->activeArchetype = 'Ciel — Coup';
$initial = $adapter->actionProbabilities($state, [0,1], $ak);
check($initial === $linear->actionProbabilities($state, [0,1], $ak), 'Zero adapter exactly preserves base');
$adapter->applyDelta($adapter->episodeDeltaForPlayer([$step], 1, 1));
$ciel = $adapter->actionProbabilities($state, [0,1], $ak);
check($ciel[0][1] > $initial[0][1], 'Own archetype learns');
check($adapter->logits['__linear__'] === $base['__linear__'], 'Shared weights frozen');
$adapter->activeArchetype = 'Ciel — Different strategy';
check($adapter->actionProbabilities($state,[0,1],$ak) === $initial, 'Same champion different archetype isolated');
$adapter->applyDelta($adapter->episodeDeltaForPlayer([$step], 1, -1));
check($adapter->actionProbabilities($state,[0,1],$ak)[0][1] < $initial[0][1], 'Conflicting strategy learns independently');
$adapter->activeArchetype = 'Ciel — Coup';
check($adapter->actionProbabilities($state,[0,1],$ak) === $ciel, 'Other archetype training cannot alter Ciel');
try { $adapter->applyDelta(['__linear__'=>['bias'=>1]]); check(false,'Must reject shared update'); } catch (LogicException $e) {}
$loaded = RlTabularPolicy::fromPayload($adapter->payload(),20,1,.05);
check($loaded->payload() === $adapter->payload(), 'Adapter checkpoint roundtrip');
check($loaded->activeArchetype === '', 'Checkpoint contains no stale routing');
$loaded->activeArchetype = 'Ciel — Coup';
check($loaded->actionProbabilities($state,[0,1],$ak) === $ciel, 'Reload retains adapter scores');
check($loaded->copy()->actionProbabilities($state,[0,1],$ak) === $ciel, 'Copy retains adapter routing');
$loaded->activeArchetype = 'unseen archetype';
$payload = $loaded->payload();
check($loaded->actionProbabilities($state,[0,1],$ak) === $initial, 'Unseen archetype uses base');
check($loaded->payload() === $payload, 'Unseen inference does not create weights');
$loaded->activeArchetype = '';
try { $loaded->episodeDeltaForPlayer([$step],1,1); check(false,'Missing routing must fail'); } catch (LogicException $e) {}
echo "Archetype isolation checks passed ($checks total)\n";

$GLOBALS['zones'] = [
  'myGraveyard-0'=>(object)['CardID'=>'visible-own'],
  'theirGraveyard-2'=>(object)['CardID'=>'visible-enemy'],
  'myGraveyard-3'=>(object)['CardID'=>'removed','removed'=>true],
  'theirHand-0'=>new HiddenCard(), 'theirDeck-0'=>new HiddenCard(),
];
$reviewActions = [['cardID'=>'myGraveyard-0&theirGraveyard-2'],
  ['cardID'=>'theirHand-0'], ['cardID'=>'theirDeck-0'], ['cardID'=>'myGraveyard-3']];
check(GaRlPublicGraveyardChoices($reviewActions) === [
  'myGraveyard-0'=>'visible-own', 'theirGraveyard-2'=>'visible-enemy'],
  'Review metadata resolves only live public graveyards, never hidden cards');
check(GaRlActionKey(['cardID'=>'myGraveyard-0'], []) === '["main",0,"myGraveyard-0","","",[]]',
  'Public review metadata does not change checkpoint action keys');
echo "Review metadata checks passed ($checks total)\n";
