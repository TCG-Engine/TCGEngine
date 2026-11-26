<?php
declare(strict_types=1);
$repo=__DIR__;while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);chdir($repo);
require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
$checks=0;
function same1443($a,$b,string $label):void {++$GLOBALS['checks'];if($a!==$b)throw new RuntimeException($label.': actual='.json_encode($a).' expected='.json_encode($b));}
InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;$GLOBALS['updateNumber']=0;
$GLOBALS['gameName']='regression_levia_initial_1443_'.bin2hex(random_bytes(6));
SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);SetTurnNumber(2);SetCurrentPhase('MAIN');SetPriorityPlayer(1);
FaBSetState(array_replace(FaBStateDefaults(),['gameMode'=>'','passiveSeats'=>[]]));
// Allocate the real inventory form before the base Hero. Native transformation
// then yields Hero1 / opposing Hero2 / original Hero in Soul3, without ID edits.
$form=AddInventory(1,CardID:'blasmophet_levia_consumed',Owner:1,Controller:1);
AddHero(2,CardID:'bravo_showstopper',Owner:2,Controller:2);
AddHero(1,CardID:'levia_shadowborn_abomination',Owner:1,Controller:1);
AddHealth(1,13);AddHealth(2,40);AddActionPoints(1,1);AddActionPoints(2,0);AddResources(1,0);AddResources(2,0);
foreach([1,2]as$p)SetShortcutPreferencesState($p,['windows'=>array_fill_keys(array_keys(GetShortcutWindowRegistry()),false)]);
$first=AddBanish(1,CardID:'consuming_command_blue',Owner:1,Controller:1,FaceDown:0);
$second=AddBanish(1,CardID:'fasting_carcass_red',Owner:1,Controller:1,FaceDown:0);
AddHand(1,CardID:'wounding_blow_red',Owner:1,Controller:1);AddHand(1,CardID:'wounding_blow_blue',Owner:1,Controller:1);
same1443(intval($form->UniqueID),1,'Real form allocated1');same1443(intval($first->UniqueID),4,'Prior banished first card4');same1443(intval($second->UniqueID),5,'Prior banished second card5');
same1443(FaBDTDBanishPlayable(1,$first),false,'Base Levia provides no first-card banish permission');
same1443(FaBDTDBanishPlayable(1,$second),false,'Base Levia provides no second-card banish permission');
FaBDTDTransform(1,'blasmophet_levia_consumed');
$hero=array_values(GetHero(1))[0];$soul=array_values(GetSoul(1))[0];
same1443($hero->CardID,'blasmophet_levia_consumed','Native current form');same1443(intval($hero->UniqueID),1,'Form stays physicalUID1');
same1443(intval(GetHealth(1)),13,'Native current-form life13');same1443(FaBObjectCounters($hero),[],'Native form has no discarded base-Hero counter');
same1443($soul->CardID,'levia_shadowborn_abomination','Original Hero actually moved to Soul');same1443(intval($soul->UniqueID),3,'Original physical Hero3 in Soul');
same1443(count(FaBChoiceRefs(1,'Inventory')),0,'Actual inventory form moved out');
same1443(FaBDTDBanishPlayable(1,$first),true,'Current form enables first BloodDebt card');same1443(FaBDTDBanishPlayable(1,$second),true,'Current form enables the other BloodDebt card before use');
same1443(FaBDTDBanishPlayable(1,$first,false),false,'No intrinsic first-card banish permission');same1443(FaBDTDBanishPlayable(1,$second,false),false,'No intrinsic second-card banish permission');
same1443(FaBDTDCount(1,'BLASMOPHET_PLAY'),0,'Once-turn permission unused');same1443(intval($first->FaceDown),0,'First banish card predates transformation');same1443(intval($second->FaceDown),0,'Second banish card predates transformation');same1443(intval(GetUniqueIDCounter()),7,'Exact final initialUID7');
echo "Levia transformed-position component: PASS ($checks checks). No authentication, files, gameplay or replay.\n";
