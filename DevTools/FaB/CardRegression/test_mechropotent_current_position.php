<?php
declare(strict_types=1);
$repo=realpath(dirname(__DIR__,3));if($repo===false)throw new RuntimeException('Repository root unavailable');chdir($repo);
require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
$checks=0;
function same1447($actual,$expected,string $label):void {++$GLOBALS['checks'];if($actual!==$expected)throw new RuntimeException($label.': actual='.json_encode($actual).' expected='.json_encode($expected));}
InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;$GLOBALS['updateNumber']=0;
$GLOBALS['gameName']='regression_mechropotent_initial_1447_'.bin2hex(random_bytes(6));
SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);SetTurnNumber(2);SetCurrentPhase('MAIN');SetPriorityPlayer(1);
FaBSetState(array_replace(FaBStateDefaults(),['gameMode'=>'','passiveSeats'=>[]]));
// Constructor only: actual transform output, not a claimed replay of casting.
// The four Evos' original under-cards were spent previously and remain in graveyard.
$source=AddStack(CardID:'singularity_red',Owner:1,Controller:1,Kind:'ACTION',SourceZone:'Hand',SourceUniqueID:1);
AddHero(2,CardID:'bravo_showstopper',Owner:2,Controller:2);
$evos=['evo_command_center_yellow','evo_engine_room_yellow','evo_smoothbore_yellow','evo_thruster_yellow'];
foreach($evos as$id)AddEquipment(1,CardID:$id,Owner:1,Controller:1);
AddWeapons(1,CardID:'teklo_leveler',Owner:1,Controller:1);
AddHero(1,CardID:'teklovossen_esteemed_magnate',Owner:1,Controller:1);
AddHealth(1,40);AddHealth(2,40);AddActionPoints(1,1);AddActionPoints(2,0);AddResources(1,0);AddResources(2,0);
foreach([1,2]as$p)SetShortcutPreferencesState($p,['windows'=>array_fill_keys(array_keys(GetShortcutWindowRegistry()),false)]);
foreach(['teklo_base_head','teklo_base_chest','teklo_base_arms','teklo_base_legs']as$id)AddGraveyard(1,CardID:$id,Owner:1,Controller:1);
$attack=AddHand(1,CardID:'zero_to_sixty_red',Owner:1,Controller:1);
AddHand(1,CardID:'hyper_driver_red',Owner:1,Controller:1);AddHand(1,CardID:'wounding_blow_blue',Owner:1,Controller:1);
AddDeck(1,CardID:'zero_to_sixty_blue',Owner:1,Controller:1);
same1447(intval($source->UniqueID),1,'Actual physical Singularity1');
same1447(intval(GetHero(1)[0]->UniqueID),8,'Actual original Hero8');same1447(FaBEvoCount(1),4,'Four actual equipped Evos before transform');
same1447(FaBEVOGoAgain(1,$attack),false,'Ordinary original Hero grants no Mechanologist attack go again');
same1447(intval($attack->UniqueID),13,'Actual support attack13');
// Honor the normal physical Stack self-movement guard; no fabricated pending frame.
DoResolveCard(1,'Stack-0');
$hero=GetHero(1)[0];
same1447($hero->CardID,'teklovossen_the_mechropotent','Native transformed Hero');same1447(intval($hero->UniqueID),1,'Native physical form1');
same1447(intval(GetHealth(1)),40,'Current life remains40');same1447(FaBObjectCounters($hero),[],'Native form has no omitted Hero counters');
same1447(count(FaBChoiceRefs(1,'Equipment')),0,'Materials left Equipment');same1447(count(FaBChoiceRefs(1,'Weapons')),0,'Weapon moved to Soul');same1447(FaBStackCount(),0,'Physical Singularity moved out of Stack');
$soul=GetSoul(1);same1447(array_map(fn($o)=>$o->CardID,$soul),array_merge($evos,['teklo_leveler','teklovossen_esteemed_magnate']),'Native exact Soul material order');
same1447(array_map(fn($o)=>intval($o->UniqueID),$soul),[3,4,5,6,7,8],'Original Soul UIDs preserved without ID rewrites');
same1447(FaBEvoCount(1),4,'Form counts as four Evos without equipped Evos');same1447(FaBEVOGoAgain(1,$attack),true,'Current form grants Mech AA go again');
$generic=(object)['CardID'=>'wounding_blow_red','Controller'=>1,'Owner'=>1,'UniqueID'=>999];same1447(FaBEVOGoAgain(1,$generic),false,'Generic AA does not gain Mechanologist go again');
$hero->TurnEffects=['NO_ABILITIES'];same1447(FaBEvoCount(1),0,'Blanked Hero does not count as four Evos');same1447(FaBEVOGoAgain(1,$attack),false,'Blanked Hero grants no go again');$hero->TurnEffects=[];
same1447(FaBEvoCount(1),4,'Restore unchanged native Hero before binding');same1447(intval(GetUniqueIDCounter()),16,'Exact UID16 with no new transform proxies');same1447(intval(GetRandomCounter()),0,'No setup RNG');
// Setup-only canonicalization of completed callback context, never an active frame.
same1447(FaBStackCount(),0,'Completed constructor Stack quiescent');
foreach([1,2,3,4]as$p)same1447(count(GetDecisionQueue($p)),0,'Completed constructor queue'.$p.' quiescent');
same1447(FaBGetState()['pendingPayment']??null,null,'No pending payment');same1447(FaBGetState()['resolvingReaction']??null,null,'No managed resolution remains');
$vars=DecisionQueueController::DecodeVariablesPublic(GetDecisionQueueVariables());
same1447($vars,['fabSourceZone'=>'Hand','mzID'=>'p1Arena-0','evoEvent'=>''],'Exact inert callback-local whitelist, no await metadata');
$priorState=FaBGetState();SetDecisionQueueVariables('');same1447(FaBGetState(),$priorState,'Context cleanup preserves every card/turn state field');
echo "PASS native Mechropotent current position: ".$checks." checks\n";
