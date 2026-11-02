<?php
require __DIR__.'/dragapult-tests.php';
$beforeChecks=$checks;
function lucarioBoard(string $id='me01-077'): void {
    dragapultBoard($id); GetZoneObject('p1Active-0')->Energy=['mee-006','mee-006','mee-006'];
}
function lucarioAttackAvailable(int $index): bool { return (bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='attack' && $a['index']===$index); }
foreach (['me01-076','me01-077','me01-073','sv05-145','me01-124'] as $id) verifyDragapult(PokeCardImplemented($id),'Implemented '.$id);
$entries=array_map(fn($id)=>['id'=>$id,'count'=>1],['me01-076','me01-077','me01-073','sv05-145','me01-124']); $entries[]=['id'=>'mee-006','count'=>55];
verifyDragapult(PokeValidateDeck($entries)===[],'All five requested printings pass deck validation');
foreach (['me01-076'=>[0,30],'me01-077'=>[1,270]] as $id=>[$index,$damage]) {
    lucarioBoard($id); dragapultAction('attack',$index);
    verifyDragapult(GetZoneObject('p2Active-0')->Damage===$damage,'Attack damage '.$id);
    SetTurnPlayer(1); SetTurnNumber(8); PokeStateImport(PokeStateExport());
    verifyDragapult(!lucarioAttackAvailable($index) && !PokeGeneratedCanAttack(1,'p1Active-0',$index),'Attack blocked next own turn '.$id);
    $state=PokeStateExport();
    try { dragapultAction('attack',$index); throw new RuntimeException('Blocked attack accepted'); } catch (InvalidArgumentException $e) {}
    verifyDragapult(PokeStateExport()===$state,'Blocked attack rejected before mutation');
    if ($id==='me01-077') verifyDragapult(lucarioAttackAvailable(0),'Mega Brave does not block Aura Jab');
    SetTurnNumber(10); verifyDragapult(lucarioAttackAvailable($index),'Attack restriction expires '.$id);
    SetTurnNumber(8); dragapultBench(1,'me01-076'); PokeSwitch(1,'p1Bench-0'); PokeCompact(); PokeSwitch(1,'p1Bench-0'); PokeCompact();
    verifyDragapult(lucarioAttackAvailable($index),'Switching clears attack restriction '.$id);
}
lucarioBoard('me01-076'); dragapultAction('attack'); SetTurnPlayer(1); SetTurnNumber(8);
GetZoneObject('p1Active-0')->EnteredTurn=1; GetZoneObject('p1Active-0')->EvolvedTurn=-1;
$evo=PokeAdd(1,'Hand','me01-077'); PokeEvolve(1,PokeRef(1,'Hand',$evo->mzIndex),'p1Active-0');
verifyDragapult(GetZoneObject('p1Active-0')->TurnEffects===[] && lucarioAttackAvailable(1),'Evolution clears Riolu attack effects');

lucarioBoard(); dragapultBench(1,'me01-076'); dragapultBench(1,'me02.5-159');
foreach (['mee-006','mee-006','mee-006','mee-006','mee-002','sv05-161'] as $id) PokeAdd(1,'Discard',$id);
dragapultAction('attack');
verifyDragapult(GetZoneObject('p2Active-0')->Damage===130,'Aura Jab damage before choices');
verifyDragapult(count(PokeDecisionOptions(1)['choices'])===4 && PokeDecisionOptions(1)['max']===3,'Aura Jab offers only Basic Fighting up to three');
dragapultAnswer('p1Discard-0&p1Discard-1&p1Discard-2');
verifyDragapult(count(PokeDecisionOptions(1)['choices'])===2,'Aura Jab only attaches to Bench');
dragapultAnswer('p1Bench-0'); dragapultAnswer('p1Bench-1'); dragapultAnswer('p1Bench-0');
verifyDragapult(GetZoneObject('p1Bench-0')->Energy===['mee-006','mee-006'] && GetZoneObject('p1Bench-1')->Energy===['mee-006'],'Aura Jab distributes across saved choices');
verifyDragapult(PokeCount(1,'Discard')===3 && GetTurnPlayer()===2,'Aura Jab finishes after all attachments');
lucarioBoard(); dragapultBench(1,'me01-076'); PokeAdd(1,'Discard','mee-006'); dragapultAction('attack'); dragapultAnswer('-');
verifyDragapult(GetTurnPlayer()===2 && GetZoneObject('p1Bench-0')->Energy===[],'Aura Jab may attach zero');
lucarioBoard(); PokeAdd(1,'Discard','mee-006'); dragapultAction('attack'); verifyDragapult(GetTurnPlayer()===2,'Aura Jab with no Bench');
lucarioBoard(); dragapultBench(1,'me01-076'); dragapultAction('attack'); verifyDragapult(GetTurnPlayer()===2,'Aura Jab with no matching Energy');
verifyDragapult(PokePrizeValue('me01-077')===3,'Mega Lucario awards three Prizes');

lucarioBoard('me01-073'); dragapultAction('attack');
verifyDragapult(GetZoneObject('p2Active-0')->Damage===210 && GetZoneObject('p1Active-0')->Damage===70,'Wild Press deals opponent and self damage');
lucarioBoard('me01-073'); dragapultPlay('me01-124'); dragapultPlay('me01-124'); dragapultAction('attack');
verifyDragapult(GetZoneObject('p2Active-0')->Damage===270 && GetZoneObject('p1Active-0')->Damage===70,'Premium Power stacks without boosting self damage');
lucarioBoard('me01-073'); dragapultBench(1,'me01-076'); GetZoneObject('p1Active-0')->Damage=(int)CardHp('me01-073')-60;
dragapultAction('attack'); verifyDragapult(PokeCount(1,'Active')===0 && GetPrizeClaims(2)===1,'Wild Press self knockout awards opponent a Prize');
lucarioBoard('me01-072'); GetZoneObject('p1Active-0')->EnteredTurn=1; GetZoneObject('p1Active-0')->EvolvedTurn=-1; dragapultBench(2,'me01-076');
$card=PokeAdd(1,'Hand','me01-073'); PokeApplyAction(['type'=>'evolve','player'=>1,'source'=>PokeRef(1,'Hand',$card->mzIndex),'target'=>'p1Active-0']);
dragapultAnswer('p2Bench-0'); verifyDragapult(GetZoneObject(PokeFirstRef(2,'Active'))->CardID==='me01-076','Heave-Ho Catcher gusts on evolution');
lucarioBoard('me01-072'); dragapultBench(2,'me01-076'); $card=PokeAdd(1,'Hand','me01-073'); PokeEvolve(1,PokeRef(1,'Hand',$card->mzIndex),'p1Active-0'); PokeDrain(); dragapultAnswer('-');
verifyDragapult(GetZoneObject('p2Active-0')->CardID==='me02.5-160','Heave-Ho Catcher is optional');
lucarioBoard('me01-072'); $card=PokeAdd(1,'Hand','me01-073'); PokeEvolve(1,PokeRef(1,'Hand',$card->mzIndex),'p1Active-0'); PokeDrain(); verifyDragapult(PokeDecisionOptions(1)===null,'Heave-Ho Catcher without opposing Bench');

lucarioBoard(); $GLOBALS['p1Deck']=[]; foreach (['mee-006','mee-005','mee-002','mee-008'] as $id) PokeAdd(1,'Deck',$id);
dragapultPlay('sv05-145'); dragapultAnswer('p1Deck-0&p1Deck-2');
verifyDragapult(PokeDecisionOptions(1)['type']==='MZCHOOSE' && PokeCount(1,'Deck')===2,'Codebreaking shuffles remainder before ordering');
dragapultAnswer('p1TempZone-1');
$deck=array_values(array_map(fn($o)=>$o->CardID,PokeObjects(1,'Deck')));
verifyDragapult(array_slice($deck,0,2)===['mee-002','mee-006'] && count($deck)===4,'Codebreaking puts chosen cards on top in desired order');
verifyDragapult(PokeCount(1,'TempZone')===0 && PokeCount(1,'Hand')===0,'Codebreaking leaves no temporary cards or draws');
verifyDragapult(!array_filter(PokeVar('log',[]),fn($e)=>($e['event']??'')==='reveal-search'),'Codebreaking keeps choices private');
PokeDraw(1,2,false); verifyDragapult(array_values(array_map(fn($o)=>$o->CardID,PokeObjects(1,'Hand')))===['mee-002','mee-006'],'Codebreaking order survives actual draws');
lucarioBoard(); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Deck','mee-006'); dragapultPlay('sv05-145'); dragapultAnswer('p1Deck-0'); dragapultAnswer('p1TempZone-0');
verifyDragapult(PokeCount(1,'Deck')===1 && GetZoneObject(PokeFirstRef(1,'Deck'))->CardID==='mee-006','Codebreaking with only one card');
lucarioBoard(); dragapultPlay('sv05-145'); dragapultAnswer('-'); verifyDragapult(PokeCount(1,'Deck')===20 && PokeDecisionOptions(1)===null,'Codebreaking may fail its hidden search');

lucarioBoard(); dragapultPlay('me01-124'); PokeDealAttackDamage(1,'p1Active-0',100);
verifyDragapult(GetZoneObject('p2Active-0')->Damage===130,'Premium Power boosts Fighting attack');
dragapultBench(2,'me02.5-160'); PokeDealAttackDamage(1,'p1Active-0',100,false,'p2Bench-0'); verifyDragapult(GetZoneObject('p2Bench-0')->Damage===0,'Premium Power does not override Tera Bench protection');
GetZoneObject('p2Bench-0')->CardID='me01-073'; PokeDealAttackDamage(1,'p1Active-0',10,false,'p2Bench-0'); verifyDragapult(GetZoneObject('p2Bench-0')->Damage===10,'Premium Power excludes Bench damage');
PokePlaceDamageCounters('p1Active-0','p2Active-0',1); verifyDragapult(GetZoneObject('p2Active-0')->Damage===140,'Premium Power excludes damage counters');
SetTurnNumber(8); PokeDealAttackDamage(1,'p1Active-0',10); verifyDragapult(GetZoneObject('p2Active-0')->Damage===150,'Premium Power expires');
lucarioBoard('me02.5-160'); dragapultPlay('me01-124'); PokeDealAttackDamage(1,'p1Active-0',10); verifyDragapult(GetZoneObject('p2Active-0')->Damage===10,'Premium Power requires Fighting attacker');
lucarioBoard(); dragapultPlay('me01-124'); GetZoneObject('p2Active-0')->CardID='sv05-128'; PokeDealAttackDamage(1,'p1Active-0',10);
verifyDragapult(GetZoneObject('p2Active-0')->Damage===80,'Premium Power applies before Fighting weakness');
echo 'PASS '.($checks-$beforeChecks)." Lucario package checks\n";
