<?php
require __DIR__.'/dragapult-tests.php';
require_once dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require_once dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
$beforeChecks=$checks;
function lanternBoard(string $active='sv08-036'): void {
    dragapultBoard($active);GetZoneObject('p1Active-0')->Energy=['mee-002'];
    PokeSetVar('deckKey:1','ceruledge-lantern');PokeSetVar('initialBasicFire:1',7);PokeSetVar('initialEnergy:1',22);PokeSetVar('initialBasicFighting:1',11);
}
function lanternStep(): array {
    $action=PokeBotChoose(PokeObservation(PokePendingPlayer()));
    PokeStateImport(json_decode(json_encode(PokeStateExport()),true));PokeApplyAction($action);return $action;
}
function lanternCard(array $action): string { return GetZoneObject($action['source'])->CardID; }
$deck=PokeNamedDeck('ceruledge-lantern');$counts=array_column($deck,'count','id');
verifyDragapult(array_sum($counts)===60 && PokeValidateDeck($deck)===[],'Exact 60-card Lantern deck validates');
verifyDragapult($counts['mee-002']===7 && $counts['base1-97']===11 && $counts['base1-100']===4 && $counts['me06-090']===4,'Exact Energy split and four Lantern preserved');
verifyDragapult(PokeDetectDeck(array_reverse($deck))==='ceruledge-lantern','Deck detection precedes broad Fighting archetype detection');
PokeCreateGame($deck,PokeNamedDeck('sinistcha'),42,1);
verifyDragapult(PokeObservation(1)['players'][1]['deckKey']==='ceruledge-lantern','Named deck initializes profile');
verifyDragapult(PokeObservation(1)['players'][1]['initialEnergy']===22,'Bot receives correct starting Energy total');
lanternBoard(); verifyDragapult(PokeBotChoose(PokeObservation(1))===PokeCeruledgeLanternChoose(PokeObservation(1)),'Dispatcher uses Lantern policy');
lanternBoard();SetCurrentPhase('SETUP');AddSetupReady(1,false);$GLOBALS['p1Active']=[];
foreach(['me02-019','me01-075','me01-074'] as $id)PokeAdd(1,'Hand',$id);
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='setup-active' && lanternCard($a)==='me01-075','Going second starts Solrock over Charcadet for a first-turn attack');
SetFirstPlayer(1);$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='setup-active' && lanternCard($a)==='me02-019','Going first keeps Charcadet evolution setup');
lanternBoard('me01-075');AddPlayerTurns(1,1);GetZoneObject('p1Active-0')->Energy=[];dragapultBench(1,'me01-074');PokeAdd(1,'Hand','base1-97');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='attach' && lanternCard($a)==='base1-97','Power Active Solrock before Lunar Cycle can discard last Fighting');
lanternStep();$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='attack' && $a['source']==='p1Active-0','Powered Solrock attacks rather than ending with zero damage');
lanternBoard('me01-075');AddPlayerTurns(1,1);GetZoneObject('p1Active-0')->Energy=[];dragapultBench(1,'me01-074');PokeAdd(1,'Hand','base1-97');PokeAdd(1,'Hand','base1-97');
lanternStep();verifyDragapult(PokeBotChoose(PokeObservation(1))['type']==='ability','After reserving attack Energy, extra Fighting can still pay Lunar Cycle');
lanternBoard();
GetZoneObject('p1Active-0')->Energy=[];
foreach(['me06-090','sv06-145','me02.5-192'] as $id) PokeAdd(1,'Hand',$id);
PokeAdd(1,'Deck','mee-002');PokeAdd(1,'Deck','base1-100');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='trainer' && lanternCard($a)==='me06-090','Lantern finds missing Fire before hand-reset Supporter');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(1));verifyDragapult(GetZoneObject($a['value'])->CardID==='mee-002','Bot takes Lantern Fire even with optional search');lanternStep();
$a=PokeBotChoose(PokeObservation(1));verifyDragapult(GetZoneObject($a['value'])->CardID==='base1-100','Bot takes Lantern Lightning instead of skipping it');lanternStep();
verifyDragapult(PokeCount(1,'Hand')===4 && GetTurnPlayer()===1,'Both searches resolve across saved requests');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='attach' && lanternCard($a)==='mee-002','Bot powers Ceruledge with Fire before discarding Lightning');

lanternBoard();dragapultBench(1,'me01-074');dragapultBench(1,'me01-075');
foreach(['me01-131','base1-100','base1-100','mee-002','base1-97'] as $id)PokeAdd(1,'Hand',$id);
dragapultPlay('me01-131');$a=PokeBotChoose(PokeObservation(1));
$ids=array_map(fn($ref)=>GetZoneObject($ref)->CardID,explode('&',$a['value']));
verifyDragapult($ids===['base1-100','base1-100'],'Ultra Ball discards Lightning ahead of Fire and Lunar payment');lanternStep();
verifyDragapult(PokeCount(1,'Hand')===3 && count(array_filter(PokeObjects(1,'Discard'),fn($c)=>$c->CardID==='base1-100'))===2,'Lightning discard resolves and increases Abyssal Flames fuel');
lanternBoard();GetZoneObject('p1Active-0')->Energy=[];PokeAdd(1,'Hand','base1-100');
verifyDragapult(PokeBotChoose(PokeObservation(1))['type']!=='attach','Lightning is not used as Fire on an unpowered Ceruledge');
lanternBoard('me02-019');GetZoneObject('p1Active-0')->Energy=[];dragapultBench(1,'me01-074');$sol=dragapultBench(1,'me01-075');GetZoneObject($sol)->Energy=['base1-97'];
PokeAdd(1,'Hand','base1-100');PokeAdd(1,'Hand','base1-97');
verifyDragapult(PokeBotChoose(PokeObservation(1))['type']==='ability','Draw before wasting one Lightning on an unaffordable two-Energy retreat');
lanternBoard();dragapultBench(1,'me01-074');dragapultBench(1,'me01-075');PokeAdd(1,'Hand','base1-100');
verifyDragapult(!(bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Lightning cannot pay Lunar Cycle');
lanternBoard('me01-074');GetZoneObject('p1Active-0')->Energy=[];$ex=dragapultBench(1,'sv08-036');GetZoneObject($ex)->Energy=['mee-002'];
PokeAdd(1,'Discard','mee-002');
PokeAdd(1,'Hand','base1-100');PokeAdd(1,'Hand','base1-97');dragapultBench(1,'me01-075');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='attach' && lanternCard($a)==='base1-100' && $a['target']==='p1Active-0','Lightning pays retreat while preserving Fighting for Lunar Cycle');
lanternStep();verifyDragapult((bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='retreat' && $a['target']===$ex) && PokeCeruledgeLanternContext(PokeObservation(1))['fighting']===1,'Lightning enables retreat while leaving Fighting available for Lunar Cycle');

lanternBoard();GetZoneObject('p1Active-0')->Energy=[];dragapultBench(1,'me01-074');dragapultBench(1,'me01-075');
$GLOBALS['p1Deck']=[];foreach(['mee-002','base1-97','base1-100','base1-100','base1-100','base1-100'] as $id)PokeAdd(1,'Deck',$id);
dragapultPlay('sv08-164');$a=PokeBotChoose(PokeObservation(1));$ids=array_map(fn($ref)=>GetZoneObject($ref)->CardID,explode('&',$a['value']));
verifyDragapult($ids===array_fill(0,4,'base1-100'),'Blender mills Lightning while preserving last Fire and Lunar payment');lanternStep();
verifyDragapult(PokeCandidates(1,'Deck','basicFire')!=='' && PokeCandidates(1,'Deck','basicFighting')!=='','Both needed Energy types remain after Blender');
lanternBoard();foreach(['base1-100','base1-100','sv06-145','me02.5-192'] as $id)PokeAdd(1,'Hand',$id);
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='trainer' && lanternCard($a)==='sv06-145','Carmine discards Lightning fuel rather than Lillie shuffling it back');
lanternBoard();$backup=dragapultBench(1,'sv08-036');GetZoneObject($backup)->Energy=['mee-002'];dragapultBench(1,'me01-074');dragapultBench(1,'me01-075');
foreach(['base1-100','base1-100','sv06-145','me02.5-192','me03-081','me03-081'] as $id)PokeAdd(1,'Hand',$id);
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='trainer' && lanternCard($a)==='sv06-145','Carmine recognizes Lightning fuel even when legacy policy rejects a large established hand');
$GLOBALS['p1Deck']=array_slice($GLOBALS['p1Deck'],0,6);$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']!=='trainer' || lanternCard($a)!=='sv06-145','Lightning-heavy Carmine still respects its two-card draw buffer');
lanternBoard();$backup=dragapultBench(1,'sv08-036');GetZoneObject($backup)->Energy=['mee-002'];dragapultBench(1,'me01-074');dragapultBench(1,'me01-075');
foreach(['base1-100','base1-100','sv06-145','me01-116'] as $id)PokeAdd(1,'Hand',$id);PokeAdd(1,'Deck','base1-97');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='trainer' && lanternCard($a)==='me01-116','Gong fetches extra Energy before Carmine discards the hand');lanternStep();
$a=PokeBotChoose(PokeObservation(1));verifyDragapult(GetZoneObject($a['value'])->CardID==='base1-97','Gong actually takes Fighting as Carmine fuel');
lanternBoard('me01-075');AddPlayerTurns(1,1);PokeAdd(1,'Hand','sv06-145');PokeAdd(1,'Deck','base1-97');PokeAdd(1,'Deck','me01-074');dragapultPlay('me01-116');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult(GetZoneObject($a['value'])->CardID==='me01-074','Gong completes Solrock draw engine before taking Carmine fuel without a Charcadet line');
lanternBoard('me02-019');foreach(['me02-019','sv08-036','me01-074','me01-075'] as $id)PokeAdd(1,'Deck',$id);
foreach(['base1-100','base1-100'] as $id)PokeAdd(1,'Hand',$id);dragapultPlay('me01-131');lanternStep();
$a=PokeBotChoose(PokeObservation(1));verifyDragapult(GetZoneObject($a['value'])->CardID==='sv08-036','Search completes the existing Ceruledge attacker before taking another Charcadet');lanternStep();
verifyDragapult(PokeBotChoose(PokeObservation(1))['type']==='evolve','Searched Ceruledge is evolved before the attack');
lanternBoard('me01-074');GetZoneObject('p1Active-0')->Energy=[];$char=dragapultBench(1,'me02-019');GetZoneObject($char)->Energy=['mee-002'];dragapultBench(1,'me02-019');dragapultBench(1,'me01-075');PokeAdd(1,'Discard','mee-002');
foreach(['me01-131','base1-100','base1-100','base1-97','mee-002'] as $id)PokeAdd(1,'Hand',$id);PokeAdd(1,'Deck','sv08-036');
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='trainer' && lanternCard($a)==='me01-131','Tutor first Ceruledge before prematurely attaching Fire to a backup');
lanternStep();lanternStep();lanternStep();$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='evolve','Tutor sequence evolves the powered Charcadet');
lanternStep();$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='attach' && $a['target']==='p1Active-0' && lanternCard($a)==='base1-97','After evolution, attachment pays Active retreat rather than powering a backup');
lanternBoard('me02-019');foreach(['me01-131','base1-100','me06-090'] as $id)PokeAdd(1,'Hand',$id);
$a=PokeBotChoose(PokeObservation(1));verifyDragapult($a['type']==='trainer' && lanternCard($a)==='me06-090','Use last Lantern to generate discard fuel before Ultra Ball can sacrifice it');
lanternBoard();$GLOBALS['p1Deck']=[];foreach(['mee-002','base1-100','mee-005'] as $id)PokeAdd(1,'Deck',$id);dragapultPlay('me06-090');lanternStep();
verifyDragapult(PokeBotChoose(PokeObservation(1))['value']==='-','Lantern can decline second search to preserve last two turn draws');lanternStep();
verifyDragapult(PokeCount(1,'Deck')===2,'Lantern respects low-deck buffer');
lanternBoard();$GLOBALS['p1Deck']=array_slice($GLOBALS['p1Deck'],0,2);PokeAdd(1,'Hand','me06-090');
verifyDragapult(PokeBotChoose(PokeObservation(1))['type']==='attack','Bot does not play Lantern into two-card draw buffer');
lanternBoard();PokeAdd(1,'Hand','me06-090');$view=PokeObservation(1);$action=PokeBotChoose($view);
$GLOBALS['p1Deck']=[];$GLOBALS['p1Prizes']=[];PokeAdd(2,'Hand','me06-090');
verifyDragapult(PokeBotChoose($view)===$action,'Lantern policy uses supplied observations, not live hidden state');
foreach($deck as $entry)verifyDragapult(is_file(dirname(__DIR__,2).'/PokeSim/WebpImages/'.$entry['id'].'.webp'),'Artwork available '.$entry['id']);
$lanterns=0;$lightning=0;$cerAttacks=0;
foreach(['sinistcha','brisbane-lopunny','ceruledge-fighting'] as $opponent)foreach([1,42] as $seed)foreach([1,2] as $first) {
    $r=PokeSimulateGame($seed,$first,1500,'ceruledge-lantern',$opponent);
    verifyDragapult($r['status']==='complete','Lantern match completes vs '.$opponent.' seed '.$seed.' first '.$first.': '.$r['reason']);
    foreach(PokeVar('log',[]) as $event)if(($event['player']??0)===1) {
        if($event['event']==='play-trainer' && $event['card']==='me06-090')++$lanterns;
        if($event['event']==='reveal-search' && $event['card']==='base1-100')++$lightning;
        if($event['event']==='attack-damage' && $event['source']==='sv08-036')++$cerAttacks;
    }
}
verifyDragapult($lanterns>0 && $lightning>0 && $cerAttacks>0,'Full matches play Lantern, find Lightning and attack with Ceruledge');
echo 'PASS '.($checks-$beforeChecks)." Lantern deck/bot checks; 12 complete matches; $lanterns Lanterns; $lightning Lightning searches; $cerAttacks Ceruledge attacks\n";
