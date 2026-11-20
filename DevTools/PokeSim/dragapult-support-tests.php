<?php
require __DIR__.'/dragapult-tests.php';
$beforeChecks=$checks;
foreach (['me02.5-099','sv05-128','sv07-133','me03-084','me03-071','me01-127'] as $id) verifyDragapult(PokeCardImplemented($id),'Implemented '.$id);
dragapultBoard('me02.5-099');
verifyDragapult(!dragapultHasAction('ability'),'Adrena-Brain requires Darkness Energy');
GetZoneObject('p1Active-0')->Energy[]='mee-007';
verifyDragapult(!dragapultHasAction('ability'),'Adrena-Brain requires damage to move');
GetZoneObject('p1Active-0')->Damage=40; dragapultAction('ability'); dragapultAnswer('p1Active-0');
verifyDragapult(PokeDecisionOptions(1)['max']===3,'Adrena-Brain caps at three counters');
dragapultAnswer('3'); dragapultAnswer('p2Active-0');
verifyDragapult(GetZoneObject('p1Active-0')->Damage===10 && GetZoneObject('p2Active-0')->Damage===30,'Adrena-Brain moves counters');
verifyDragapult(!dragapultHasAction('ability'),'Adrena-Brain once per Pokémon per turn');
dragapultBoard('me02.5-099'); GetZoneObject('p1Active-0')->Energy[]='mee-007';
$ref=dragapultBench(1,'sv05-128'); GetZoneObject($ref)->Damage=20; dragapultBench(2,'me05-005');
dragapultAction('ability'); dragapultAnswer($ref); verifyDragapult(PokeDecisionOptions(1)['max']===2,'Adrena-Brain cannot move more counters than source has');
dragapultAnswer('2'); dragapultAnswer('p2Bench-0');
verifyDragapult(GetZoneObject('p1Bench-0')->Damage===0 && GetZoneObject('p2Bench-0')->Damage===0,'Protected target prevents placement but source still loses counters');
dragapultBoard('me02.5-099'); GetZoneObject('p1Active-0')->Energy[]='mee-007'; GetZoneObject('p1Active-0')->Damage=10;
dragapultAction('ability'); dragapultAnswer('p1Active-0'); dragapultAnswer('0'); verifyDragapult(GetZoneObject('p1Active-0')->Damage===10 && PokeDecisionOptions(1)===null,'Adrena-Brain permits zero');
dragapultBoard('me02.5-099'); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===60 && !empty(GetZoneObject('p2Active-0')->Conditions['Confused']),'Mind Bend damages and confuses');

foreach ([1=>true,2=>false] as $seed=>$heads) {
    dragapultBoard('sv05-128'); SetRandomState($seed); dragapultAction('attack',1);
    verifyDragapult(GetZoneObject('p2Active-0')->Damage===30,'Dig deals thirty');
    PokeDealAttackDamage(2,'p2Active-0',40);
    PokePlaceDamageCounters('p2Active-0','p1Active-0',1);
    verifyDragapult(GetZoneObject('p1Active-0')->Damage===($heads?0:50),'Dig coin controls damage and effect protection');
    if ($heads) {
        PokePlaceDamageCounters('p2Active-0','p1Active-0',1,'Ability'); verifyDragapult(GetZoneObject('p1Active-0')->Damage===10,'Dig does not block abilities');
        SetTurnNumber(9); PokeDealAttackDamage(2,'p2Active-0',10); verifyDragapult(GetZoneObject('p1Active-0')->Damage===20,'Dig protection expires');
    }
}
dragapultBoard('sv05-128'); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===10,'Gnaw');

dragapultBoard(); $GLOBALS['p1Deck']=[];
foreach (['mee-002','mee-002','mee-005','sv05-161'] as $id) PokeAdd(1,'Deck',$id);
dragapultPlay('sv07-133'); dragapultAnswer('p1Deck-0');
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-005'],'Crispin excludes same type and Special Energy');
dragapultAnswer('p1Deck-2'); dragapultAnswer('p1TempZone-0'); dragapultAnswer('p1Active-0');
verifyDragapult(array_values(PokeObjects(1,'Hand'))[0]->CardID==='mee-002' && count(GetZoneObject('p1Active-0')->Energy)===4,'Crispin puts one in hand and attaches other');
verifyDragapult(!GetEnergyUsed(1) && PokeCount(1,'TempZone')===0 && GetSupporterUsed(1),'Crispin preserves manual attachment and clears temporary cards');
dragapultBoard(); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Deck','mee-002'); dragapultPlay('sv07-133'); dragapultAnswer('p1Deck-0');
dragapultAnswer('p1TempZone-0');
verifyDragapult(PokeCount(1,'Hand')===1 && count(GetZoneObject('p1Active-0')->Energy)===3,'Single Crispin Energy goes to hand only');
dragapultBoard(); dragapultPlay('sv07-133'); dragapultAnswer('-'); verifyDragapult(PokeCount(1,'Hand')===0 && PokeDecisionOptions(1)===null,'Crispin may find zero');
dragapultBoard(); PokeAdd(1,'Deck','mee-002'); dragapultPlay('sv07-133'); dragapultAnswer('p1Deck-20'); dragapultAnswer('-'); dragapultAnswer('p1TempZone-0');
verifyDragapult(PokeCount(1,'Hand')===1 && count(GetZoneObject('p1Active-0')->Energy)===3,'Crispin may decline second Energy');

dragapultBoard(); $target=dragapultBench(1,'me02.5-160');
foreach (['mee-002','mee-005','sv05-161'] as $id) PokeAdd(1,'Discard',$id);
$trainer=PokeAdd(1,'Hand','me03-084'); verifyDragapult(!dragapultHasAction('trainer'),'Rosa requires more remaining Prizes');
$GLOBALS['p2Prizes']=array_slice($GLOBALS['p2Prizes'],0,5);
PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>PokeRef(1,'Hand',$trainer->mzIndex)]);
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-002','mee-005'],'Rosa excludes Special Energy');
dragapultAnswer('p1Discard-0&p1Discard-1');
verifyDragapult(count(PokeDecisionOptions(1)['choices'])===2,'Rosa offers Stage 2 Pokémon'); dragapultAnswer($target);
verifyDragapult(GetZoneObject('p1Bench-0')->Energy===['mee-002','mee-005'] && !GetEnergyUsed(1),'Rosa attaches both to one Pokémon without using manual attachment');
dragapultBoard('sv05-128'); PokeAdd(1,'Discard','mee-002'); $GLOBALS['p2Prizes']=array_slice($GLOBALS['p2Prizes'],0,5); PokeAdd(1,'Hand','me03-084'); verifyDragapult(!dragapultHasAction('trainer'),'Rosa requires a Stage 2');
dragapultBoard(); PokeAdd(1,'Discard','mee-002'); $GLOBALS['p2Prizes']=array_slice($GLOBALS['p2Prizes'],0,5); dragapultPlay('me03-084'); dragapultAnswer('-'); verifyDragapult(PokeCount(1,'Discard')===2 && PokeDecisionOptions(1)===null,'Rosa allows zero Energy');

foreach ([1=>true,2=>false] as $seed=>$heads) {
    dragapultBoard(); GetZoneObject('p2Active-0')->Energy=['mee-002','sv05-161']; SetRandomState($seed); dragapultPlay('me03-071');
    if ($heads) { dragapultAnswer('p2Active-0'); dragapultAnswer('1'); }
    verifyDragapult(count(GetZoneObject('p2Active-0')->Energy)===($heads?1:2),'Hammer coin controls discard');
    verifyDragapult(PokeCount(2,'Discard')===($heads?1:0),'Hammer discards selected Special Energy');
}
dragapultBoard(); GetZoneObject('p2Active-0')->Energy=[]; PokeAdd(1,'Hand','me03-071'); verifyDragapult(!dragapultHasAction('trainer'),'Hammer requires attached opposing Energy');

dragapultBoard(); dragapultPlay('me01-127');
$card=PokeAdd(1,'Hand','sv05-128'); PokeApplyAction(['player'=>1,'type'=>'bench','source'=>PokeRef(1,'Hand',$card->mzIndex)]);
verifyDragapult(GetZoneObject('p1Bench-0')->Damage===20,'Risky Ruins damages hand-played Basic');
$card=PokeAdd(1,'Discard','sv06.5-018'); PokePlayBasicFromZone(1,PokeRef(1,'Discard',$card->mzIndex));
verifyDragapult(GetZoneObject('p1Bench-1')->Damage===20,'Risky Ruins damages recovered Basic');
$card=PokeAdd(1,'Deck','me05-005'); PokePlayBasicFromZone(1,PokeRef(1,'Deck',$card->mzIndex));
verifyDragapult(GetZoneObject('p1Bench-2')->Damage===20,'Risky Ruins damages searched Basic despite ability protection');
$card=PokeAdd(1,'Hand','me02.5-142'); PokePlayBasicFromZone(1,PokeRef(1,'Hand',$card->mzIndex));
verifyDragapult(GetZoneObject('p1Bench-3')->Damage===0,'Risky Ruins excludes Darkness Pokémon');
SetTurnPlayer(2); $card=PokeAdd(2,'Deck','sv05-128'); PokePlayBasicFromZone(2,PokeRef(2,'Deck',$card->mzIndex));
verifyDragapult(GetZoneObject('p2Bench-0')->Damage===20,'Risky Ruins applies during either player turn');
$card=PokeAdd(1,'Hand','sv05-128'); PokePlayBasicFromZone(1,PokeRef(1,'Hand',$card->mzIndex));
verifyDragapult(GetZoneObject('p1Bench-4')->Damage===0,'Risky Ruins excludes benching outside own turn');
dragapultBoard(); dragapultPlay('me01-127'); SetCurrentPhase('SETUP'); $card=PokeAdd(1,'Hand','sv05-128'); PokePlayBasicFromZone(1,PokeRef(1,'Hand',$card->mzIndex));
verifyDragapult(GetZoneObject('p1Bench-0')->Damage===0,'Risky Ruins excludes setup');
dragapultBoard(); dragapultPlay('me01-127'); PokeDiscardStadium(); $card=PokeAdd(1,'Deck','sv05-128'); PokePlayBasicFromZone(1,PokeRef(1,'Deck',$card->mzIndex));
verifyDragapult(GetZoneObject('p1Bench-0')->Damage===0,'Discarded Risky Ruins stops applying');
echo 'PASS '.($checks-$beforeChecks)." additional support card checks\n";
