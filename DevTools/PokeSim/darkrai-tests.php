<?php
require __DIR__.'/dragapult-tests.php';
$beforeChecks=$checks;
function darkraiBoard(string $id='me05-101'): void { dragapultBoard($id); GetZoneObject('p1Active-0')->Energy=['mee-007','mee-007','mee-007']; }
foreach (['30th-092','me01-115','sv06-135','me02-067','me02-068','me05-101'] as $id) verifyDragapult(PokeCardImplemented($id),'Implemented '.$id);
$entries=array_map(fn($id)=>['id'=>$id,'count'=>1],['30th-092','me01-115','sv06-135','me02-067','me02-068','me05-101']); $entries[]=['id'=>'mee-007','count'=>54];
verifyDragapult(PokeValidateDeck($entries)===[],'All six printings pass deck validation');
foreach ([0=>100,10=>240] as $existing=>$damage) {
    darkraiBoard('30th-092'); GetZoneObject('p2Active-0')->Damage=$existing; dragapultAction('attack');
    verifyDragapult(GetZoneObject('p2Active-0')->Damage===$existing+$damage,'Lunatic Claw checks damage before its attack');
}
darkraiBoard(); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===110,'Dusk Raid base damage');
darkraiBoard(); GetZoneObject('p1Active-0')->Damage=10; dragapultBench(1,'me02-067'); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===110,'Dusk Raid ignores damage on Active');
darkraiBoard(); dragapultBench(1,'me02-067'); GetZoneObject('p1Bench-0')->Damage=10; dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===220,'Dusk Raid boosts from damaged Bench');
darkraiBoard(); dragapultBench(1,'me02-067'); dragapultBench(1,'me02-067'); GetZoneObject('p1Bench-0')->Damage=10; GetZoneObject('p1Bench-1')->Damage=20; dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===220,'Dusk Raid boost is not per damaged Pokémon');
darkraiBoard(); dragapultAction('attack',1); verifyDragapult(GetZoneObject('p2Active-0')->Damage===0 && GetTurnPlayer()===2,'Abyss Eye without conditions does nothing');
foreach (['Poisoned','Burned','Confused','Asleep','Paralyzed'] as $condition) {
    darkraiBoard(); dragapultBench(2,'me02-067'); GetZoneObject('p2Active-0')->Conditions=[$condition=>$condition==='Poisoned'?10:true]; dragapultAction('attack',1);
    verifyDragapult(PokeCount(2,'Active')===0 && GetPrizeClaims(1)===2,'Abyss Eye knocks out with '.$condition);
}
darkraiBoard(); GetZoneObject('p2Active-0')->Conditions=['Confused'=>true]; GetZoneObject('p2Active-0')->Energy=['sv05-161']; dragapultAction('attack',1);
verifyDragapult(GetZoneObject('p2Active-0')->Damage===0,'Mist Energy prevents Abyss Eye');
darkraiBoard(); GetZoneObject('p2Active-0')->CardID='me05-005'; GetZoneObject('p2Active-0')->Conditions=['Confused'=>true]; dragapultAction('attack',1);
verifyDragapult(GetZoneObject('p2Active-0')->Damage===0,'Hide n Sneak prevents Abyss Eye');
verifyDragapult(PokePrizeValue('me05-101')===3,'Mega Darkrai is worth three Prizes');

darkraiBoard('sv06-135'); $GLOBALS['p1Deck']=[];
foreach (['sv08.5-060','sv06-079','mee-007'] as $id) PokeAdd(1,'Deck',$id);
GetZoneObject('p1Active-0')->Damage=20; GetZoneObject('p1Active-0')->Conditions=['Poisoned'=>10]; GetZoneObject('p1Active-0')->TurnEffects=['noRetreatThrough'=>99];
AddPlayerTurns(1,1); SetFirstPlayer(2); GetZoneObject('p1Active-0')->EnteredTurn=GetTurnNumber();
dragapultAction('attack'); verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['sv08.5-060'],'Ascension offers only cards evolving from Eevee');
dragapultAnswer('p1Deck-0');
verifyDragapult(GetZoneObject('p1Active-0')->CardID==='sv08.5-060' && GetZoneObject('p1Active-0')->Evolutions===['sv06-135'],'Ascension evolves from deck on first turn in play');
verifyDragapult(GetZoneObject('p1Active-0')->Damage===20 && count(GetZoneObject('p1Active-0')->Energy)===3,'Ascension preserves damage and Energy');
verifyDragapult(GetZoneObject('p1Active-0')->Conditions===[] && GetZoneObject('p1Active-0')->TurnEffects===[] && GetTurnPlayer()===2,'Ascension clears conditions and effects and ends attack');
darkraiBoard('sv06-135'); dragapultAction('attack'); dragapultAnswer('-'); verifyDragapult(GetZoneObject('p1Active-0')->CardID==='sv06-135','Ascension may fail its search');
foreach ([1=>40,2=>20] as $seed=>$damage) { darkraiBoard('sv06-135'); SetRandomState($seed); dragapultAction('attack',1); verifyDragapult(GetZoneObject('p2Active-0')->Damage===$damage,'Quick Attack coin outcome'); }
// Evolution from a deck must not fire abilities requiring evolution from the hand.
darkraiBoard('sv08-129'); $card=PokeAdd(1,'Deck','sv08-130'); PokeAdd(1,'Discard','mee-008');
PokeEvolve(1,PokeRef(1,'Deck',$card->mzIndex),'p1Active-0'); PokeDrain(); verifyDragapult(PokeDecisionOptions(1)===null,'Deck evolution does not trigger Assemble Alloy');

darkraiBoard('me02-067'); PokeAdd(1,'Deck','me05-101'); PokeAdd(1,'Deck','me02-068'); PokeAdd(1,'Deck','sv06-135'); dragapultAction('attack');
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['me05-101','sv06-135'],'Call for Family searches Basics including Mega ex and excludes evolutions');
dragapultAnswer('p1Deck-20&p1Deck-22'); verifyDragapult(PokeCount(1,'Bench')===2 && GetTurnPlayer()===2,'Call for Family Benches two');
darkraiBoard('me02-067'); for ($i=0;$i<4;++$i) dragapultBench(1,'me02-067'); PokeAdd(1,'Deck','me02-067'); dragapultAction('attack'); verifyDragapult(PokeDecisionOptions(1)['max']===1,'Call for Family respects open Bench slots'); dragapultAnswer('-');
darkraiBoard('me02-067'); for ($i=0;$i<5;++$i) dragapultBench(1,'me02-067'); dragapultAction('attack'); verifyDragapult(GetTurnPlayer()===2 && PokeCount(1,'Bench')===5,'Call for Family on full Bench');
darkraiBoard('me02-067'); dragapultAction('attack',1); verifyDragapult(GetZoneObject('p2Active-0')->Damage===20,'Playful Kick');
darkraiBoard('me02-068'); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===100,'Gentle Slap');

darkraiBoard('me02-068'); verifyDragapult(!dragapultHasAction('ability'),'Surge requires a Benched Darkness Pokémon');
dragapultBench(1,'sv06-135'); verifyDragapult(!dragapultHasAction('ability'),'Surge excludes non-Darkness Bench');
dragapultBench(1,'me05-101'); PokeAdd(1,'Deck','mee-007'); PokeAdd(1,'Deck','sv05-161'); PokeAdd(1,'Deck','mee-002');
dragapultAction('ability'); verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-007'],'Surge searches only Basic Darkness Energy');
dragapultAnswer('p1Deck-20'); verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['me05-101'],'Surge targets only Benched Darkness'); dragapultAnswer('p1Bench-1');
verifyDragapult(GetZoneObject('p1Bench-1')->Energy===['mee-007'] && GetZoneObject('p1Bench-1')->Damage===20,'Surge attaches and puts two counters on recipient');
verifyDragapult(!GetEnergyUsed(1) && !dragapultHasAction('ability'),'Surge preserves manual attachment and is once per Pokémon');
dragapultBench(1,'me02-068'); verifyDragapult(dragapultHasAction('ability'),'Each Toxtricity may use Surge');
darkraiBoard('me02-068'); dragapultBench(1,'me05-101'); dragapultAction('ability'); dragapultAnswer('-'); verifyDragapult(GetZoneObject('p1Bench-0')->Damage===0 && !dragapultHasAction('ability'),'Surge may fail search without counters but uses ability');
darkraiBoard('me02-068'); dragapultBench(1,'me02-067'); GetZoneObject('p1Bench-0')->Damage=(int)CardHp('me02-067')-10; PokeAdd(1,'Deck','mee-007');
dragapultAction('ability'); dragapultAnswer('p1Deck-20'); dragapultAnswer('p1Bench-0'); verifyDragapult(PokeCount(1,'Bench')===0 && GetPrizeClaims(2)===1,'Surge can knock out recipient and awards Prize');

darkraiBoard(); PokeAdd(1,'Hand','me01-115'); verifyDragapult(!dragapultHasAction('trainer'),'Energy Switch needs another Pokémon');
darkraiBoard(); dragapultBench(1,'me02-067'); GetZoneObject('p1Active-0')->Energy=['sv05-161']; PokeAdd(1,'Hand','me01-115'); verifyDragapult(!dragapultHasAction('trainer'),'Energy Switch cannot move Special Energy');
darkraiBoard(); dragapultBench(1,'me02-067'); GetZoneObject('p1Active-0')->Energy=['sv05-161','mee-007','mee-002']; dragapultPlay('me01-115'); dragapultAnswer('p1Active-0');
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-007','mee-002'],'Energy Switch chooser offers only Basic Energy');
dragapultAnswer('p1TempZone-1'); verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['me02-067'],'Energy Switch excludes source from destinations'); dragapultAnswer('p1Bench-0');
verifyDragapult(GetZoneObject('p1Active-0')->Energy===['sv05-161','mee-007'] && GetZoneObject('p1Bench-0')->Energy===['mee-002'],'Energy Switch moves chosen Basic Energy');
verifyDragapult(PokeCount(1,'TempZone')===0 && PokeCount(1,'Discard')===1 && !GetEnergyUsed(1),'Energy Switch removes previews, discards no Energy and preserves manual attachment');
echo 'PASS '.($checks-$beforeChecks)." Darkrai package checks\n";
