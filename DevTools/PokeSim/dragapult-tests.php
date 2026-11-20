<?php
require dirname(__DIR__, 2).'/PokeSim/Runtime.php';
set_error_handler(function($s,$m,$f,$l) { if (error_reporting() & $s) throw new ErrorException($m,0,$s,$f,$l); });
$checks = 0;
function verifyDragapult($ok, string $label): void { global $checks; ++$checks; if (!$ok) throw new RuntimeException($label); }
function dragapultBoard(string $own = 'me02.5-160'): void {
    InitializeGamestate(); $GLOBALS['playerID']=1; $GLOBALS['currentPlayer']=1;
    SetCurrentPhase('MAIN'); SetTurnNumber(6); SetTurnPlayer(1); SetFirstPlayer(2); SetRandomState(42);
    foreach ([1,2] as $p) {
        AddPlayerTurns($p,3); AddSetupReady($p,true);
        $obj=PokeAdd($p,'Active',$p===1?$own:'me02.5-160'); $obj->Controller=$p;
        $obj->Energy=['mee-002','mee-005','mee-005'];
        for ($i=0;$i<6;++$i) PokeAdd($p,'Prizes','mee-005');
        for ($i=0;$i<20;++$i) PokeAdd($p,'Deck','mee-005');
    }
}
function dragapultBench(int $p, string $id): string { $obj=PokeAdd($p,'Bench',$id); $obj->Controller=$p; return PokeRef($p,'Bench',$obj->mzIndex); }
function dragapultAction(string $type, int $index = 0): void { PokeApplyAction(['player'=>1,'type'=>$type,'source'=>'p1Active-0','index'=>$index]); }
function dragapultAnswer(string $value, int $player = 1): void {
    PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
    PokeApplyAction(['player'=>$player,'type'=>'decision','value'=>$value]);
}
function dragapultPlay(string $id): void { $obj=PokeAdd(1,'Hand',$id); PokeApplyAction(['player'=>1,'type'=>'trainer','source'=>PokeRef(1,'Hand',$obj->mzIndex)]); }
function dragapultHasAction(string $type, int $p = 1): bool { return (bool)array_filter(PokeLegalActions($p), fn($a)=>$a['type']===$type); }
foreach (['me02.5-159','me02.5-160','sv06.5-018','sv06.5-019','sv06.5-020','sv02-185','sv01-189','sv05-157'] as $id) verifyDragapult(PokeCardImplemented($id),'Implemented '.$id);
$entries=array_map(fn($id)=>['id'=>$id,'count'=>1], ['me02.5-159','me02.5-160','sv06.5-018','sv06.5-019','sv06.5-020','sv02-185','sv01-189','sv05-157']);
$entries[]=['id'=>'mee-005','count'=>52];
verifyDragapult(PokeValidateDeck($entries)===[],'All eight requested printings pass deck validation');
$entries[7]['count']=2; $entries[8]['count']=51;
verifyDragapult(in_array('Deck may have at most one ACE SPEC card',PokeValidateDeck($entries),true),'Prime Catcher retains ACE SPEC deck restriction');

dragapultBoard('me02.5-159'); $GLOBALS['p1Deck']=[];
foreach (['mee-002','mee-005','mee-008'] as $id) PokeAdd(1,'Deck',$id);
dragapultAction('ability');
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-002','mee-005'],'Recon sees only top two');
dragapultAnswer('p1TempZone-1');
verifyDragapult(array_values(array_map(fn($o)=>$o->CardID,PokeObjects(1,'Hand')))===['mee-005'],'Recon takes chosen card');
verifyDragapult(array_values(array_map(fn($o)=>$o->CardID,PokeObjects(1,'Deck')))===['mee-008','mee-002'],'Recon bottoms other card without shuffling');
verifyDragapult(!dragapultHasAction('ability'),'Recon once per Pokémon per turn');
dragapultBench(1,'me02.5-159'); verifyDragapult(dragapultHasAction('ability'),'Second Drakloak can use Recon');
dragapultBoard('me02.5-159'); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Deck','mee-002'); dragapultAction('ability'); dragapultAnswer('p1TempZone-0');
verifyDragapult(PokeCount(1,'Hand')===1 && PokeCount(1,'Deck')===0,'Recon with one remaining card');
dragapultBoard('me02.5-159'); $GLOBALS['p1Deck']=[]; verifyDragapult(!dragapultHasAction('ability'),'Recon unavailable on empty deck');
dragapultBoard('me02.5-159'); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===70,'Dragon Headbutt');
dragapultBoard(); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===70,'Jet Headbutt');
dragapultBoard(); dragapultBench(2,'me02.5-160'); dragapultBench(2,'me02.5-160'); dragapultAction('attack',1);
verifyDragapult(GetZoneObject('p2Active-0')->Damage===200 && GetTurnPlayer()===1,'Phantom Dive waits after Active damage');
for ($i=0;$i<6;++$i) dragapultAnswer($i<2?'p2Bench-0':'p2Bench-1');
verifyDragapult(GetZoneObject('p2Bench-0')->Damage===20 && GetZoneObject('p2Bench-1')->Damage===40,'Phantom Dive distributes exactly six counters through snapshots');
verifyDragapult(GetTurnPlayer()===2,'Phantom Dive finishes turn after placement');
dragapultBoard(); dragapultAction('attack',1); verifyDragapult(GetTurnPlayer()===2 && GetZoneObject('p2Active-0')->Damage===200,'Phantom Dive without Bench');
dragapultBoard(); dragapultBench(2,'me05-005'); dragapultAction('attack',1); for ($i=0;$i<6;++$i) dragapultAnswer('p2Bench-0');
verifyDragapult(GetZoneObject('p2Bench-0')->Damage===0,'Phantom Dive respects attack effect protection');

dragapultBoard('sv06.5-018');
for ($i=0;$i<4;++$i) PokeAdd(1,'Discard','sv06.5-018');
PokeAdd(1,'Discard','sv06.5-019'); dragapultAction('attack');
verifyDragapult(count(PokeDecisionOptions(1)['choices'])===4 && PokeDecisionOptions(1)['max']===3,'Come and Get You only offers Duskull, up to three');
dragapultAnswer('p1Discard-0&p1Discard-1&p1Discard-2');
verifyDragapult(PokeCount(1,'Bench')===3 && PokeCount(1,'Discard')===2,'Duskull goes directly from discard to Bench');
dragapultBoard('sv06.5-018'); for ($i=0;$i<4;++$i) dragapultBench(1,'sv06.5-018'); PokeAdd(1,'Discard','sv06.5-018'); dragapultAction('attack');
verifyDragapult(PokeDecisionOptions(1)['max']===1,'Duskull respects Bench capacity'); dragapultAnswer('-'); verifyDragapult(PokeCount(1,'Bench')===4,'Duskull allows zero');
dragapultBoard('sv06.5-018'); dragapultAction('attack'); verifyDragapult(GetTurnPlayer()===2,'Duskull with no discard targets');
dragapultBoard('sv06.5-018'); for ($i=0;$i<5;++$i) dragapultBench(1,'sv06.5-018'); PokeAdd(1,'Discard','sv06.5-018'); dragapultAction('attack');
verifyDragapult(GetTurnPlayer()===2 && PokeCount(1,'Bench')===5 && PokeCount(1,'Discard')===1,'Duskull does not overfill a full Bench');
dragapultBoard('sv06.5-018'); dragapultAction('attack',1); verifyDragapult(GetZoneObject('p2Active-0')->Damage===30,'Mumble');
dragapultBoard('sv06.5-019'); dragapultAction('attack'); verifyDragapult(GetZoneObject('p2Active-0')->Damage===50,'Will-O-Wisp');
foreach (['sv06.5-019'=>50,'sv06.5-020'=>130] as $id=>$damage) {
    dragapultBoard($id); dragapultBench(1,'me02.5-160');
    GetZoneObject('p1Active-0')->Evolutions=['sv06.5-018']; GetZoneObject('p1Active-0')->Tool='sv06-148';
    dragapultAction('ability'); dragapultAnswer('p2Active-0');
    verifyDragapult(GetZoneObject('p2Active-0')->Damage===$damage,'Cursed Blast counters '.$id);
    verifyDragapult(PokeCount(1,'Active')===0 && PokeCount(1,'Discard')===6,'Cursed Blast discards self, evolution, Energy and Tool');
    verifyDragapult(GetPrizeClaims(2)===1,'Cursed Blast gives opponent a Prize');
}
dragapultBoard('sv06.5-020'); dragapultBench(1,'me02.5-160'); dragapultBench(2,'me05-005');
dragapultAction('ability'); dragapultAnswer('p2Bench-0'); verifyDragapult(GetZoneObject('p2Bench-0')->Damage===0 && PokeCount(1,'Active')===0,'Protection does not prevent Cursed Blast self knockout');
dragapultBoard('sv06.5-020'); dragapultBench(2,'me02.5-039'); verifyDragapult(!dragapultHasAction('ability'),'Damp blocks Cursed Blast');
dragapultBoard('sv06.5-020'); dragapultBench(1,'me02.5-160'); dragapultBench(2,'me02.5-160'); GetZoneObject('p2Active-0')->Damage=200;
dragapultAction('ability'); dragapultAnswer('p2Active-0'); verifyDragapult(GetPrizeClaims(1)===2 && GetPrizeClaims(2)===1,'Cursed Blast resolves both knockouts and Prize claims together');

dragapultBoard('sv06.5-020'); dragapultBench(2,'me02.5-160'); dragapultAction('attack');
verifyDragapult(GetZoneObject('p2Active-0')->Damage===150 && !dragapultHasAction('retreat',2),'Shadow Bind damage and retreat lock next turn');
SetTurnNumber(9); verifyDragapult(dragapultHasAction('retreat',2),'Shadow Bind expires');
SetTurnNumber(7); PokeSwitch(2,'p2Bench-0'); PokeCompact(); PokeSwitch(2,'p2Bench-0'); PokeCompact();
verifyDragapult(dragapultHasAction('retreat',2),'Switching clears Shadow Bind');
dragapultBoard('sv06.5-020'); GetZoneObject('p2Active-0')->Energy[]='sv05-161'; dragapultBench(2,'me02.5-160'); dragapultAction('attack');
verifyDragapult(dragapultHasAction('retreat',2),'Mist Energy prevents Shadow Bind effect');

dragapultBoard(); PokeAdd(1,'Hand','mee-002'); PokeAdd(2,'Hand','mee-008'); $GLOBALS['p2Prizes']=array_slice($GLOBALS['p2Prizes'],0,3); dragapultPlay('sv02-185');
verifyDragapult(PokeCount(1,'Hand')===6 && PokeCount(2,'Hand')===3,'Iono draws each remaining Prize count');
verifyDragapult(array_values(PokeObjects(1,'Deck'))[PokeCount(1,'Deck')-1]->CardID==='mee-002','Iono bottoms own hand');
verifyDragapult(array_values(PokeObjects(2,'Deck'))[PokeCount(2,'Deck')-1]->CardID==='mee-008','Iono bottoms opponent hand');
dragapultBoard(); PokeAdd(2,'Hand','mee-008'); dragapultPlay('sv02-185'); verifyDragapult(PokeCount(1,'Hand')===6 && PokeCount(2,'Hand')===6,'Iono draws for empty-handed player when opponent returned cards');
dragapultBoard(); PokeAdd(1,'Hand','mee-002'); dragapultPlay('sv02-185'); verifyDragapult(PokeCount(2,'Hand')===6,'Iono draws for empty-handed opponent');
dragapultBoard(); PokeAdd(1,'Hand','sv02-185'); verifyDragapult(!dragapultHasAction('trainer'),'Iono unavailable if neither player can return cards');
dragapultBoard(); PokeAdd(1,'Hand','mee-002'); PokeAdd(1,'Hand','mee-008'); dragapultPlay('sv01-189'); verifyDragapult(PokeCount(1,'Hand')===7 && PokeCount(1,'Discard')===3 && GetSupporterUsed(1),'Research discards hand then draws seven and uses Supporter');
dragapultBoard(); $GLOBALS['p1Deck']=array_slice($GLOBALS['p1Deck'],0,2); dragapultPlay('sv01-189'); verifyDragapult(PokeCount(1,'Hand')===2 && !GetWinner(),'Research draws available cards without deck-out loss');
dragapultBoard(); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Hand','sv01-189'); verifyDragapult(!dragapultHasAction('trainer'),'Research requires cards in deck');

dragapultBoard(); dragapultBench(1,'sv06.5-018'); dragapultBench(2,'sv06.5-019'); dragapultPlay('sv05-157'); dragapultAnswer('p2Bench-0');
verifyDragapult(GetZoneObject(PokeFirstRef(2,'Active'))->CardID==='sv06.5-019' && PokeDecisionOptions(1)!==null,'Prime Catcher switches opponent first');
dragapultAnswer('p1Bench-0'); verifyDragapult(GetZoneObject(PokeFirstRef(1,'Active'))->CardID==='sv06.5-018' && !GetSupporterUsed(1),'Prime Catcher then switches self without using Supporter');
dragapultBoard(); dragapultBench(2,'sv06.5-019'); dragapultPlay('sv05-157'); dragapultAnswer('p2Bench-0'); verifyDragapult(GetZoneObject(PokeFirstRef(2,'Active'))->CardID==='sv06.5-019' && PokeDecisionOptions(1)===null,'Prime Catcher works without own Bench');
dragapultBoard(); PokeAdd(1,'Hand','sv05-157'); verifyDragapult(!dragapultHasAction('trainer'),'Prime Catcher requires opposing Bench');
echo "PASS $checks Dragapult package checks\n";
