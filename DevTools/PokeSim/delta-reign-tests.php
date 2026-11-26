<?php
require __DIR__.'/dragapult-tests.php';
require_once dirname(__DIR__,2).'/PokeSim/Import/TCGdex.php';
$beforeChecks=$checks;
verifyDragapult(CardName('me06-090')==='Adventure Lantern' && PokeCardImplemented('me06-090'),'Adventure Lantern imported and implemented');
verifyDragapult(CardTrainerType('me06-090')==='Item' && str_contains(CardEffect('me06-090'),'Lightning'),'Lantern imported text and Item type');
verifyDragapult(CardRegulationMark('me06-090')==='J' && CardLegal('me06-090')['standard']===true,'Lantern corrected current legality');
$set=json_decode(file_get_contents(dirname(__DIR__,2).'/PokeSim/GeneratedCode/tcgdex/sets/me06.json'),true,512,JSON_THROW_ON_ERROR);
verifyDragapult(count($set['cards'])===141,'Live set snapshot contains 141 printings');
foreach ($set['cards'] as $card) verifyDragapult(CardName($card['id'])!==null,'Imported '.$card['id']);
verifyDragapult(PokeParseDeckText('1 Adventure Lantern DLR 90')===[['id'=>'me06-090','count'=>1]],'DLR deck export abbreviation resolves');
$raw=['id'=>'me06-090','name'=>'Adventure Lantern','category'=>'Trainer','set'=>['id'=>'me06'],'localId'=>'090','trainerType'=>'Item','legal'=>['standard'=>false,'expanded'=>true]];
$corrected=PokeTCGdexNormalize($raw);
verifyDragapult(str_contains($corrected['effect'],'Fire') && $corrected['legal']['standard']===true,'Missing live metadata corrected on future imports');
$raw['effect']='Future upstream rules text'; verifyDragapult(PokeTCGdexNormalize($raw)['effect']===$raw['effect'],'Later upstream text takes precedence over missing-text fallback');

dragapultBoard(); $GLOBALS['p1Deck']=[];
foreach (['mee-002','mee-002','mee-004','mee-004','mee-005','sv05-161','me04-086'] as $id) PokeAdd(1,'Deck',$id);
dragapultPlay('me06-090');
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-002','mee-002'],'First search offers only Basic Fire');
dragapultAnswer('p1Deck-0');
verifyDragapult(array_column(PokeDecisionOptions(1)['choices'],'card')===['mee-004','mee-004'],'Second search offers only Basic Lightning');
verifyDragapult(PokeCount(1,'Hand')===1 && PokeCount(1,'Deck')===6,'Fire leaves deck before second choice');
dragapultAnswer('p1Deck-2');
verifyDragapult(array_values(array_map(fn($o)=>$o->CardID,PokeObjects(1,'Hand')))===['mee-002','mee-004'],'One Energy of each type enters hand across saved choices');
verifyDragapult(PokeCount(1,'Deck')===5 && PokeCount(1,'Discard')===1 && !GetSupporterUsed(1),'Lantern is an Item and conserves cards');
verifyDragapult(count(array_filter(PokeVar('log',[]),fn($event)=>($event['event']??'')==='reveal-search'))===2,'Both found Energy cards are revealed');
verifyDragapult(GetTurnPlayer()===1 && PokeDecisionOptions(1)===null,'Lantern returns to main phase');

foreach (['mee-002','mee-004'] as $id) {
    dragapultBoard(); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Deck',$id); dragapultPlay('me06-090'); dragapultAnswer('p1Deck-0');
    verifyDragapult(PokeCount(1,'Hand')===1 && PokeDecisionOptions(1)===null,'Lantern can find only '.$id);
}
dragapultBoard(); $GLOBALS['p1Deck']=[]; PokeAdd(1,'Deck','mee-002'); PokeAdd(1,'Deck','mee-004'); dragapultPlay('me06-090'); dragapultAnswer('-'); dragapultAnswer('-');
verifyDragapult(PokeCount(1,'Hand')===0 && PokeCount(1,'Deck')===2,'Hidden search may fail both types');
dragapultBoard(); dragapultPlay('me06-090'); verifyDragapult(PokeDecisionOptions(1)===null && PokeCount(1,'Deck')===20,'Lantern with no matching Energy shuffles without empty prompts');
dragapultBoard(); $GLOBALS['p1Deck']=[]; $card=PokeAdd(1,'Hand','me06-090'); verifyDragapult(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$card->mzIndex)),'Cannot search an empty deck');
dragapultBoard(); PokeSetVar('itemLock:1',GetTurnNumber()); $card=PokeAdd(1,'Hand','me06-090'); verifyDragapult(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$card->mzIndex)),'Item lock blocks Lantern');
$image=dirname(__DIR__,2).'/PokeSim/WebpImages/me06-090.webp'; verifyDragapult(is_file($image) && getimagesize($image)['mime']==='image/webp','Lantern has valid local WebP artwork');
echo 'PASS '.($checks-$beforeChecks)." Delta Reign checks\n";
