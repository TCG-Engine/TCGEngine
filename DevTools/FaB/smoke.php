<?php

require_once __DIR__ . '/../../FaBSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../FaBSim/GamestateParser.php';
require_once __DIR__ . '/../../FaBSim/ZoneAccessors.php';
require_once __DIR__ . '/../../FaBSim/ZoneClasses.php';
require_once __DIR__ . '/../../FaBSim/Custom/DeckImport.php';
require_once __DIR__ . '/../../Core/HTTPLibraries.php';
require_once __DIR__ . '/../../Core/EngineActionRunner.php';

$failures = [];
$check = function($condition, $message) use (&$failures) {
    if (!$condition) $failures[] = $message;
};

$check(CardName('whelming_gustwave_red') === 'Whelming Gustwave', 'Talishar-style red pitch id did not resolve.');
$check(intval(CardPitch('whelming_gustwave_blue')) === 3, 'Blue pitch metadata did not resolve.');
$check(in_array('Hero', CardTypes('ira_crimson_haze'), true), 'Hero type metadata did not resolve.');
$check(count(GetAllCardIds()) > 4900, 'The generated FaBSim card catalog is missing or incomplete.');

$deckSchema = file_get_contents(__DIR__ . '/../../Schemas/FaBDeck/GameSchema.txt');
ob_start();
include __DIR__ . '/../../FaBDeck/Custom/GameLayout.php';
$deckLayout = ob_get_clean();
$clientDictionaries = glob(__DIR__ . '/../../FaBSim/GeneratedCode/GeneratedCardDictionaries_*.js');
$check(strpos($deckSchema, 'AssetReflection: FaBSim') !== false, 'FaBDeck is not reflecting FaBSim assets.');
$check(strpos($deckLayout, 'id="myCardPaneSlot"') !== false, 'FaBDeck is missing the generated renderer card-pane binding.');
$check(strpos($deckLayout, 'id="myHeroSlot"') !== false, 'FaBDeck is missing the generated renderer hero binding.');
$check(is_array($clientDictionaries) && count($clientDictionaries) > 0, 'The generated FaBSim client dictionary is missing.');

$counterRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcgengine-fab-counter-' . bin2hex(random_bytes(6));
$firstGameID = GetGameCounter($counterRoot);
$check($firstGameID === 101, 'A missing Games directory was not initialized correctly.');
$check(is_file($counterRoot . DIRECTORY_SEPARATOR . 'GameIDCounter.txt'), 'The game counter file was not created.');
$check(is_dir($counterRoot . DIRECTORY_SEPARATOR . '101'), 'The first game directory was not created.');
@rmdir($counterRoot . DIRECTORY_SEPARATOR . '101');
@unlink($counterRoot . DIRECTORY_SEPARATOR . 'GameIDCounter.txt');
@rmdir($counterRoot);

$deck = FaBNormalizeTextDeck(<<<'DECK'
Hero
1 Ira, Crimson Haze
Weapons
2 Harmonized Kodachi
Deck
3 Whelming Gustwave (Red)
3 Whelming Gustwave (Blue)
DECK);

$check(!empty($deck['success']), 'Text deck did not import.');
$check($deck['hero'] === 'ira_crimson_haze', 'Imported hero id is wrong.');
$check(count($deck['weapons']) === 2, 'Weapon quantities were not expanded.');
$check(count($deck['mainDeck']) === 6, 'Main-deck quantities were not expanded.');
$check(empty($deck['unresolved']), 'Known sample deck produced unresolved rows.');

$fabraryDeck = FaBNormalizeDeckPayload([
    'name' => 'Fabrary fixture',
    'cards' => [
        ['identifier' => 'ira-crimson-haze', 'total' => 1, 'sideboardTotal' => 0],
        ['identifier' => 'harmonized-kodachi', 'total' => 2, 'sideboardTotal' => 0],
        ['identifier' => 'mask-of-momentum', 'total' => 1, 'sideboardTotal' => 0],
        ['identifier' => 'whelming-gustwave-red', 'total' => 3, 'sideboardTotal' => 1],
    ],
]);
$check(!empty($fabraryDeck['success']), 'Talishar/Fabrary payload did not import.');
$check($fabraryDeck['hero'] === 'ira_crimson_haze', 'Fabrary hero was not classified.');
$check(count($fabraryDeck['weapons']) === 2, 'Fabrary weapons were not classified.');
$check(count($fabraryDeck['equipment']) === 1, 'Fabrary equipment was not classified.');
$check(count($fabraryDeck['mainDeck']) === 2, 'Fabrary main-deck totals were not normalized.');
$check(count($fabraryDeck['inventory']) === 1, 'Fabrary sideboard totals were not normalized.');

InitializeGamestate();
$playerID = 1;
SetSeatOrder('12');
SetLiveSeats('12');
SetTurnPlayer(1);
SetTurnNumber(1);
SetCurrentPhase('MAIN');
SetPriorityPlayer(1);
FaBSetState(FaBStateDefaults());
AddResources(1, 0);
AddActionPoints(1, 1);
AddHealth(1, 20);
AddHealth(2, 20);
AddHero(2, CardID:'ira_crimson_haze', Owner:2, Controller:2, Status:2);
$defenderHeroUID = intval(GetHero(2)[0]->UniqueID); $frameAnimations = [];
$attack = AddHand(1, CardID:'wounded_bull_red');
$pitchCard = AddHand(1, CardID:'wounded_bull_blue');
$attackUID = intval($attack->UniqueID);
$pitchUID = intval($pitchCard->UniqueID);

$check(!CanPitchCard(1, 'p1Hand-1'), 'Pitching was legal outside a payment window.');
$check(DoPlayCard(1, 'p1Hand-0'), 'Announcing a payable attack failed.');
$attackLayer = FaBStackTop();
$check(count(GetDecisionQueue(1)) === 0 && intval($attackLayer->Params['attackTarget']['uid'] ?? 0) === intval(GetHero(2)[0]->UniqueID), 'A single legal hero target was not auto-selected by stable ID.');
$check(FaBGetState()['window'] === 'PITCH', 'The attack did not open a pitch payment window.');
$check(DoPitchCard(1, FaBFindUID($pitchUID)['mzID']), 'Click-to-pitch failed during payment.');
$check(FaBGetState()['pendingPayment'] === null, 'Payment did not complete after sufficient pitch.');
$check(FaBFindUID($pitchUID)['zone'] === 'Pitch', 'Pitched card did not enter pitch.');
$check(intval(GetResources(1)) === 0, 'Attack payment did not consume the pitched resources.');
$check(ShouldAutoPassShortcutWindow(1, 'INSTANT_PRIORITY'), 'Instant priority is not shortcut by default.');
$check(FaBFindUID($attackUID)['zone'] === 'CombatChain', 'Resolved attack did not enter the combat chain.');
$check(FaBFindUID($attackUID)['object']->UniqueID === $attackUID, 'Moving the attack changed its unique ID.');
$check(FaBGetState()['window'] === 'DEFEND_DECLARE', 'Attack resolution did not reach defend declaration after attack-step priority.');

$check(FaBPassPriority(2), 'Defender could not finish declaring blocks.');
$check(FaBPassPriority(1), 'Attacker could not pass reactions.');
$check(FaBPassPriority(2), 'Defender could not pass reactions.');
$check(intval(GetHealth(2)) === 13, 'Unblocked Wounded Bull did not deal 7 damage.');
$lungeFrame = null; $damageFrame = null; foreach($frameAnimations as $frame){if(($frame['type']??'')==='CARD_LUNGE')$lungeFrame=$frame;if(($frame['type']??'')==='DAMAGE')$damageFrame=$frame;}
$check(intval($lungeFrame['destinationUniqueID']??0)===$defenderHeroUID&&intval($damageFrame['uniqueID']??0)===$defenderHeroUID,'Attack lunge and damage animations were not bound to the chosen target identity.');
$check(FaBGetState()['window'] === 'ACTION', 'Combat did not return to the action window.');

FaBCloseCombatChain();
$check(FaBFindUID($attackUID)['zone'] === 'Graveyard', 'Closing combat did not move the attack to graveyard.');

// With shortcuts disabled, every rules step must remain independently observable.
InitializeGamestate();
$playerID = 1; SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetTurnNumber(1); SetCurrentPhase('MAIN'); SetPriorityPlayer(1); FaBSetState(FaBStateDefaults());
SetShortcutPreferencesState(1, ['windows' => ['BLOCK'=>false, 'ATTACK_REACTION'=>false, 'DEFENSE_REACTION'=>false, 'INSTANT_PRIORITY'=>false]]);
SetShortcutPreferencesState(2, ['windows' => ['BLOCK'=>false, 'ATTACK_REACTION'=>false, 'DEFENSE_REACTION'=>false, 'INSTANT_PRIORITY'=>false]]);
AddHealth(1, 20); AddHealth(2, 20); AddResources(1, 0); AddActionPoints(1, 1);
AddHero(2, CardID:'ira_crimson_haze', Owner:2, Controller:2, Status:2);
$stepAttack = AddHand(1, CardID:'wounded_bull_red'); $stepPitch = AddHand(1, CardID:'wounded_bull_blue');
$stepAttackUID = intval($stepAttack->UniqueID); $stepPitchUID = intval($stepPitch->UniqueID);
$check(DoPlayCard(1, 'p1Hand-0') && DoPitchCard(1, FaBFindUID($stepPitchUID)['mzID']), 'Step-by-step attack announcement failed.');
$check(FaBPassPriority(2) && FaBPassPriority(1), 'Attack layer did not resolve after successive passes.');
$check(FaBGetState()['window'] === 'ATTACK' && FaBGetState()['combatStep'] === 'ATTACK', 'Attack Step was not observable.');
$check(FaBPassPriority(1) && FaBPassPriority(2), 'Attack Step priority did not complete.');
$check(FaBGetState()['window'] === 'DEFEND_DECLARE', 'Defend declaration was not observable.');
$check(FaBPassPriority(2), 'Empty defend declaration could not be committed.');
$check(FaBGetState()['window'] === 'DEFEND_PRIORITY', 'Defend Step priority was not observable.');
$check(FaBPassPriority(1) && FaBPassPriority(2), 'Defend Step priority did not complete.');
$check(FaBGetState()['window'] === 'REACTION', 'Reaction Step was not observable.');
$check(FaBPassPriority(1) && FaBPassPriority(2), 'Reaction Step priority did not complete.');
$check(FaBGetState()['window'] === 'DAMAGE' && intval(FaBGetState()['damageDealt']) === 7, 'Damage Step or its combat calculation was not observable.');
$check(FaBPassPriority(1) && FaBPassPriority(2), 'Damage Step priority did not complete.');
$check(FaBGetState()['window'] === 'RESOLUTION', 'Resolution Step was not observable.');
$check(FaBPassPriority(1) && FaBPassPriority(2), 'Resolution Step priority did not complete.');
$check(FaBGetState()['window'] === 'ACTION' && empty(FaBGetState()['combatOpen']), 'Close Step did not return to the Action Phase.');
$check(FaBFindUID($stepAttackUID)['zone'] === 'Graveyard', 'Close Step did not clear the resolved attack.');

InitializeGamestate();
$playerID=1;SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);SetCurrentPhase('MAIN');SetPriorityPlayer(1);FaBSetState(FaBStateDefaults());AddHealth(1,10);AddHealth(2,20);
$heart=AddHand(1,CardID:'heart_of_fyendal_blue');FaBWTRCardPitched(1,$heart->CardID);$check(intval(GetHealth(1))===11,'Heart of Fyendal pitch trigger did not gain life.');
$drone=AddHand(1,CardID:'drone_of_brutality_red');$droneUID=intval($drone->UniqueID);FaBMoveUID($droneUID,'Graveyard',1);$check(FaBFindUID($droneUID)['zone']==='Deck','Drone of Brutality graveyard replacement failed.');
FaBWTRAddEffect(1,'PREVENT_DAMAGE',3);$dealt=DoDamage(2,'',1,5,'PHYSICAL');$check($dealt===2&&intval(GetHealth(1))===9,'Bone Head Barrier style prevention did not consume correctly.');

InitializeGamestate();
$playerID=1;SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);SetCurrentPhase('MAIN');SetPriorityPlayer(1);FaBSetState(FaBStateDefaults());AddHealth(1,20);AddHealth(2,20);AddResources(1,3);AddActionPoints(1,1);AddHero(2,CardID:'ira_crimson_haze',Owner:2,Controller:2,Status:2);
$anothos=AddWeapons(1,CardID:'anothos',Owner:1,Controller:1,Status:2);$check(FaBWTRActivate(1,'p1Weapons-0'),'Anothos weapon activation failed.');$check(FaBFindUID(intval($anothos->UniqueID))['zone']==='Weapons','Activating a weapon removed its persistent weapon object.');

InitializeGamestate();
$playerID=1;SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);SetCurrentPhase('MAIN');SetPriorityPlayer(1);FaBSetState(FaBStateDefaults());AddHealth(1,20);AddHealth(2,20);AddResources(1,0);AddActionPoints(1,1);
SetShortcutPreferencesState(1,['windows'=>['BLOCK'=>false,'ATTACK_REACTION'=>false,'DEFENSE_REACTION'=>false,'INSTANT_PRIORITY'=>false]]);
SetShortcutPreferencesState(2,['windows'=>['BLOCK'=>false,'ATTACK_REACTION'=>false,'DEFENSE_REACTION'=>false,'INSTANT_PRIORITY'=>false]]);
AddHero(2,CardID:'ira_crimson_haze',Owner:2,Controller:2,Status:2);
$kodachi=AddWeapons(1,CardID:'harmonized_kodachi',Owner:1,Controller:1,Status:2);$kodachiUID=intval($kodachi->UniqueID);$kodachiPitch=AddHand(1,CardID:'flic_flak_blue');$kodachiPitchUID=intval($kodachiPitch->UniqueID);
$check(FaBWTRCanActivate(1,'p1Weapons-0'),'Kodachi was not legal as the opening attack with a pitchable card.');
$check(FaBWTRActivate(1,'p1Weapons-0'),'Opening Kodachi attack could not be announced.');
$check(FaBGetState()['window']==='PITCH'&&intval(FaBFindUID($kodachiUID)['object']->Status)===2,'Kodachi did not open a payment window while remaining ready before payment.');
$check(DoPitchCard(1,FaBFindUID($kodachiPitchUID)['mzID']),'Kodachi attack cost could not be paid by pitching from hand.');
$check(intval(GetResources(1))===2&&intval(GetActionPoints(1))===0&&intval(FaBFindUID($kodachiUID)['object']->Status)===1,'Kodachi payment did not spend one resource/action point and exhaust the weapon.');
$check(FaBPassPriority(2)&&FaBPassPriority(1),'Opening Kodachi attack layer did not resolve.');
$kodachiAttack=FaBFindUID(intval(FaBGetState()['attackUID']));
$check(FaBGetState()['window']==='ATTACK'&&($kodachiAttack['object']->CardID??'')==='harmonized_kodachi','Opening Kodachi did not become the active attack.');

InitializeGamestate();
$playerID = 3;
SetSeatOrder('1234');
SetLiveSeats('1234');
$seat3Card = AddHand(3, CardID:'scar_for_a_scar_red');
$seat4Card = AddHand(4, CardID:'sink_below_red');
$woundedBull = AddHand(3, CardID:'wounded_bull_blue');
AddResources(3, 2);
AddHealth(3, 10);
$check(EvaluateAttackPowerModifier('wounded_bull_blue', 3, $woundedBull, 5, $woundedBull) === 1,
    'Generated Wounded Bull macro did not see a higher-life opposing hero.');
$check(FaBSeatCount() === 4, 'Four-seat game state was not recognized.');
$check(FaBNextSeat(4) === 1, 'Four-seat turn order did not wrap clockwise.');
$check(intval(GetResources(3)) === 2, 'Seat 3 value-zone writes fell through to another seat.');
$check(FaBFindUID(intval($seat3Card->UniqueID))['player'] === 3, 'Seat 3 card identity did not resolve to its owner.');
$check(FaBFindUID(intval($seat4Card->UniqueID))['player'] === 4, 'Seat 4 card identity did not resolve to its owner.');
$check(intval($seat3Card->UniqueID) !== intval($seat4Card->UniqueID), 'Unique IDs collided across seats.');
SaveUndoVersion(3, 'Four-seat fixture');
$check(count(GetVersions(3)) === 1, 'Four-seat undo snapshot was not saved.');

InitializeGamestate();
$playerID = 1;
SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetCurrentPhase('MAIN'); SetPriorityPlayer(1);
$endState = FaBStateDefaults(); $endState['window'] = 'END_PHASE'; FaBSetState($endState); AddHealth(1, 20); AddHealth(2, 20); AddResources(1, 0); AddActionPoints(1, 1);
$arsenalCandidate = AddHand(1, CardID:'wounding_blow_red');
$check(FaBCanArsenal(1, 'p1Hand-0'), 'End-phase arsenal action was not available from hand.');
$check(FaBArsenalCard(1, 'p1Hand-0'), 'Putting a card in arsenal failed.');
$check(FaBFindUID(intval($arsenalCandidate->UniqueID))['zone'] === 'Arsenal', 'Arsenalled card lost identity or entered the wrong zone.');

InitializeGamestate();
$playerID = 1;
SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetTurnNumber(1); SetCurrentPhase('MAIN'); SetPriorityPlayer(1);
$goldfishState = FaBStateDefaults(); $goldfishState['passiveSeats'] = [2]; $goldfishState['gameMode'] = 'GOLDFISH'; FaBSetState($goldfishState);
AddHealth(1, 20); AddHealth(2, 20); AddResources(1, 0); AddActionPoints(1, 1);
FaBEnsureGoldfishOpponents($goldfishState);
$check(!empty(GetHero(2)) && (GetHero(2)[0]->CardID ?? '') === 'ira_crimson_haze', 'Goldfish mode did not create a visible passive opponent hero.');
$frameAnimations = [];
$goldfishHeroUID = intval(GetHero(2)[0]->UniqueID);
$check(DoDamage(1, '', 2, 3, 'PHYSICAL') === 3 && intval(GetHealth(2)) === 17, 'Goldfish opponent did not take damage.');
$goldfishDamageFrame = end($frameAnimations);
$check(($goldfishDamageFrame['type'] ?? '') === 'DAMAGE' && intval($goldfishDamageFrame['uniqueID'] ?? 0) === $goldfishHeroUID && intval($goldfishDamageFrame['amount'] ?? 0) === 3, 'Goldfish damage did not queue a hero-identity damage animation.');
$check(FaBPassPriority(1), 'Goldfish action-window pass failed.');
$check(FaBGetState()['window'] === 'END_PHASE', 'The passive opponent did not auto-pass the action window.');
$check(intval(GetPriorityPlayer()) === 1, 'Priority did not return to the goldfish player for end phase.');
$check(FaBPassPriority(1), 'Goldfish end-phase pass failed.');
$check(intval(GetTurnPlayer()) === 1, 'The passive goldfish seat incorrectly received a turn.');
$check(intval(GetTurnNumber()) === 2, 'Goldfish end phase did not advance the turn number.');
$check(FaBGetState()['passiveSeats'] === [2], 'Goldfish metadata was lost across the turn reset.');

InitializeGamestate();
$playerID=1;SetSeatOrder('123');SetLiveSeats('123');SetTurnPlayer(1);SetCurrentPhase('MAIN');SetPriorityPlayer(1);FaBSetState(FaBStateDefaults());
foreach([1,2,3]as$seat)SetShortcutPreferencesState($seat,['windows'=>['BLOCK'=>false,'ATTACK_REACTION'=>false,'DEFENSE_REACTION'=>false,'INSTANT_PRIORITY'=>false]]);
AddHero(1,CardID:'katsu_the_wanderer',Owner:1,Controller:1,Status:2);AddHero(2,CardID:'ira_crimson_haze',Owner:2,Controller:2,Status:2);AddHero(3,CardID:'bravo',Owner:3,Controller:3,Status:2);
$attackablePermanent=AddArena(2,CardID:'quicken',Owner:2,Controller:2,Status:2);$attackablePermanent->TurnEffects=['ATTACKABLE'];
$multiTargets=FaBLegalAttackTargets(1);$targetUIDs=array_map(fn($target)=>intval($target['uid']),$multiTargets);
$check(count($multiTargets)===3&&in_array(intval(GetHero(2)[0]->UniqueID),$targetUIDs,true)&&in_array(intval(GetHero(3)[0]->UniqueID),$targetUIDs,true)&&in_array(intval($attackablePermanent->UniqueID),$targetUIDs,true),'Attack target discovery did not scale across heroes, additional players, and attackable permanents.');
AddHealth(1,20);AddHealth(2,20);AddHealth(3,20);AddResources(1,0);AddActionPoints(1,1);$targetedAttack=AddHand(1,CardID:'wounding_blow_red');$targetedAttackUID=intval($targetedAttack->UniqueID);
$check(DoPlayCard(1,'p1Hand-0')&&count(GetDecisionQueue(1))>=2&&FaBFindUID($targetedAttackUID)['zone']==='Hand','Multiple attack targets did not pause announcement for target selection.');
$customDQHandlers['FAB_ATTACK_TARGET'](1,[$targetedAttackUID,'PLAY'],'p3Hero-0');$targetedLayer=FaBStackTop();
$check(intval($targetedLayer->Params['attackTarget']['uid']??0)===intval(GetHero(3)[0]->UniqueID),'Chosen multiplayer attack target was not persisted by stable hero identity.');

InitializeGamestate();
$playerID = 1;
SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetTurnNumber(1); SetCurrentPhase('MAIN'); SetPriorityPlayer(1);
SetGameState(json_encode(array_diff_key(FaBStateDefaults(), ['passiveSeats' => true, 'gameMode' => true])));
AddHero(1, CardID:'katsu_the_wanderer', Owner:1, Controller:1, Status:2);
$check(FaBIsPassiveSeat(2), 'A legacy goldfish game did not infer its empty passive seat.');
StartOfTurnPhase();
$check(FaBGetState()['passiveSeats'] === [2], 'Inferred legacy goldfish metadata was not retained after reset.');

InitializeGamestate();
$playerID = 1; SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetPriorityPlayer(1); SetCurrentPhase('MAIN');
$crushState = FaBStateDefaults(); $crushState['attacker'] = 1; $crushState['defender'] = 2; $crushState['chainLink'] = 1; FaBSetState($crushState);
$crush = AddCombatChain(1, CardID:'crippling_crush_red', Owner:1, Controller:1, Role:'ATTACK', ChainLink:1);
AddHand(2, CardID:'wounding_blow_red'); AddHand(2, CardID:'wounding_blow_yellow'); AddHand(2, CardID:'wounding_blow_blue');
OnHit(1, 'p1CombatChain-0', 4);
$check(FaBHandCount(2) === 1, 'Generated Crippling Crush hit macro did not discard two random cards.');

InitializeGamestate();
$playerID = 1; SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetPriorityPlayer(1); SetCurrentPhase('MAIN');
$comboState = FaBStateDefaults(); $comboState['attacker'] = 1; $comboState['defender'] = 2; $comboState['previousAttackCardID'] = 'surging_strike_red'; FaBSetState($comboState);
$gustwave = AddCombatChain(1, CardID:'whelming_gustwave_red', Owner:1, Controller:1, Role:'ATTACK', ChainLink:2);
$check(EvaluateAttackPowerModifier('whelming_gustwave_red', 1, $gustwave, 3, $gustwave) === 1, 'Whelming Gustwave combo did not gain power.');
$check(EvaluateGoAgainModifier('whelming_gustwave_red', 1, $gustwave, 0, $gustwave) === 1, 'Whelming Gustwave combo did not gain go again.');

InitializeGamestate();
$playerID = 1; SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetPriorityPlayer(2); SetCurrentPhase('MAIN');
$dominateState = FaBStateDefaults(); $dominateState['window'] = 'DEFEND_DECLARE'; $dominateState['combatOpen'] = true; $dominateState['combatStep'] = 'DEFEND'; $dominateState['attacker'] = 1; $dominateState['defender'] = 2; $dominateState['chainLink'] = 1; FaBSetState($dominateState);
$dominateAttack = AddCombatChain(1, CardID:'disable_red', Owner:1, Controller:1, Role:'ATTACK', ChainLink:1);
$dominateAttack->TurnEffects = ['DOMINATE']; $dominateState['attackUID'] = intval($dominateAttack->UniqueID); FaBSetState($dominateState);
AddHand(2, CardID:'wounding_blow_red'); AddHand(2, CardID:'wounding_blow_yellow');
$check(FaBDeclareBlock(2, 'p2Hand-0'), 'First hand block against dominate was rejected.');
$check(!FaBCanBlock(2, 'p2Hand-1'), 'Dominate allowed a second card from hand to defend.');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: $failure\n");
    exit(1);
}

echo "FaB smoke checks passed.\n";

?>
