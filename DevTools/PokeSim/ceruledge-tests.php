<?php
require dirname(__DIR__, 2).'/PokeSim/Runtime.php';
require dirname(__DIR__, 2).'/PokeSim/Import/TCGdex.php';
set_error_handler(function($s,$m,$f,$l) { if (error_reporting() & $s) throw new ErrorException($m,0,$s,$f,$l); });
$checks = 0;
function ceruledgeverify($ok, string $label): void { global $checks; ++$checks; if (!$ok) throw new RuntimeException($label); }
function ceruledgeboard(string $id = 'sv08-036'): void {
    InitializeGamestate(); $GLOBALS['playerID']=1; $GLOBALS['currentPlayer']=1;
    SetCurrentPhase('MAIN'); SetTurnNumber(6); SetTurnPlayer(1); SetFirstPlayer(2); SetRandomState(42);
    foreach ([1,2] as $p) {
        AddPlayerTurns($p,3); AddSetupReady($p,true);
        $obj=PokeAdd($p,'Active',$p===1?$id:'me02-084'); $obj->Controller=$p;
        $obj->Energy=['mee-002','mee-005','mee-008'];
        for ($i=0;$i<6;++$i) PokeAdd($p,'Prizes','mee-005');
        for ($i=0;$i<20;++$i) PokeAdd($p,'Deck','mee-005');
    }
}
function ceruledgeattack(int $index = 0): void { PokeApplyAction(['player'=>1,'type'=>'attack','source'=>'p1Active-0','index'=>$index]); }
function ceruledgechoose(string $value): void {
    PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
    PokeApplyAction(['player'=>1,'type'=>'decision','value'=>$value]);
}
function ceruledgeplay(string $id): void {
    $card=PokeAdd(1,'Hand',$id);
    PokeApplyAction(['player'=>1,'type'=>'trainer','source'=>PokeRef(1,'Hand',$card->mzIndex)]);
}
foreach (['me02-019','sv08-036','me02-020','sv06-040','me02.5-142','sv06-145','me04-086'] as $id) ceruledgeverify(PokeCardImplemented($id),'Implemented '.$id);
ceruledgeverify(CardEnergyType('me04-086')==='Special','Nitro is Special Energy');
$normalized=PokeTCGdexNormalize(['id'=>'me04-086','name'=>'Nitro Fire Energy','category'=>'Energy','set'=>['id'=>'me04'],'localId'=>'086','energyType'=>'Normal']);
ceruledgeverify($normalized['energyType']==='Special','Future imports correct Nitro classification');
ceruledgeboard('me02-019'); PokeAdd(1,'Deck','me04-086'); ceruledgeattack();
$options=PokeDecisionOptions(1);
ceruledgeverify($options['min']===0 && $options['max']===2,'Gather Strength allows up to two');
ceruledgeverify(count($options['choices'])===20 && !in_array('me04-086',array_column($options['choices'],'card'),true),'Search excludes Nitro');
ceruledgechoose($options['choices'][0]['value'].'&'.$options['choices'][1]['value']);
ceruledgeverify(PokeCount(1,'Hand')===2 && GetTurnPlayer()===2,'Gather Strength resumes and ends the attack');
ceruledgeboard('me02-019'); ceruledgeattack(); ceruledgechoose('-');
ceruledgeverify(PokeCount(1,'Hand')===0 && GetTurnPlayer()===2,'Gather Strength may find zero cards');
ceruledgeboard('me02-019'); $GLOBALS['p1Deck']=[]; ceruledgeattack();
ceruledgeverify(PokeCount(1,'Hand')===0 && GetTurnPlayer()===2,'Gather Strength works with an empty deck');
ceruledgeboard('me02-019'); ceruledgeattack(1); ceruledgeverify(GetZoneObject('p2Active-0')->Damage===10,'Chop deals ten');
ceruledgeboard(); ceruledgeattack(); ceruledgeverify(GetZoneObject('p2Active-0')->Damage===30,'Abyssal Flames base damage');
ceruledgeboard(); foreach (['mee-002','me04-086','sv09-159','sv06-145'] as $id) PokeAdd(1,'Discard',$id);
ceruledgeattack(); ceruledgeverify(GetZoneObject('p2Active-0')->Damage===90,'Abyssal Flames counts basic and Special Energy, excludes Trainers');
ceruledgeboard(); GetZoneObject('p1Active-0')->Energy=['me04-086','me04-086','mee-005','mee-008'];
ceruledgeverify(PokeHasAttackEnergy(GetZoneObject('p1Active-0'),['Fire','Psychic','Metal']),'Nitro provides Fire');
ceruledgeattack(1);
ceruledgeverify(GetZoneObject('p2Active-0')->Damage===280,'Raging Amethyst deals 280');
ceruledgeverify(GetZoneObject('p1Active-0')->Energy===[],'Raging Amethyst discards every attachment');
ceruledgeverify(PokeCount(1,'Hand')===2 && PokeCount(1,'Discard')===2,'Multiple Nitro return; basic Energy stays discarded');
foreach ([0,3,4,5] as $count) {
    ceruledgeboard('me02-020'); for ($i=0;$i<$count;++$i) PokeAdd(1,'Hand','mee-002');
    PokeAdd(1,'Hand','me04-086'); PokeAdd(1,'Hand','mee-005'); ceruledgeattack();
    if ($count>=4) {
        $options=PokeDecisionOptions(1);
        ceruledgeverify(count($options['choices'])===$count && $options['min']===4,'Infernal Slash offers only Basic Fire');
        ceruledgechoose(implode('&',array_column(array_slice($options['choices'],0,4),'value')));
    }
    ceruledgeverify(GetZoneObject('p2Active-0')->Damage===($count>=4?220:0),'Infernal Slash four-card requirement '.$count);
    ceruledgeverify(PokeCount(1,'Discard')===($count>=4?4:0),'Infernal Slash does not partially discard '.$count);
}
ceruledgeboard('sv06-040'); GetZoneObject('p1Active-0')->Damage=50; ceruledgeattack();
ceruledgeverify(GetZoneObject('p2Active-0')->Damage===100,'Wrathful Hearth uses damage counters');
ceruledgeboard('sv06-040'); GetZoneObject('p1Active-0')->Damage=0; ceruledgeattack();
ceruledgeverify(GetZoneObject('p2Active-0')->Damage===0,'Wrathful Hearth zero counters');
foreach ([false,true] as $evolved) {
    ceruledgeboard('sv06-040'); $source=GetZoneObject('p1Active-0'); $source->Energy=['me04-086','mee-002','mee-002'];
    GetZoneObject('p2Active-0')->CardID=$evolved?'me02-084':'me02.5-142'; ceruledgeattack(1);
    ceruledgeverify(GetZoneObject('p2Active-0')->Damage===($evolved?280:140),'Dynamic Blaze evolution bonus '.$evolved);
    ceruledgeverify(count($source->Energy)===($evolved?0:3),'Dynamic Blaze discards only against evolution '.$evolved);
    ceruledgeverify(PokeCount(1,'Hand')===($evolved?1:0),'Dynamic Blaze returns Nitro only when discarded '.$evolved);
}
ceruledgeboard('me02.5-142'); $b=PokeAdd(2,'Bench','sv06-040'); $b->Controller=2; $b->Counters['noAbilities']=true;
PokeDealAttackDamage(1,'p1Active-0',100,false,'p2Bench-0'); ceruledgeverify($b->Damage===0,'Tera Bench rule survives ability suppression');
PokePlaceDamageCounters('p1Active-0','p2Bench-0',2); ceruledgeverify($b->Damage===20,'Tera does not prevent counters');
PokeDealAttackDamage(1,'p1Active-0',100,true,'p2Bench-0'); ceruledgeverify($b->Damage===120,'Ignoring effects bypasses Tera protection');
ceruledgeboard('me02.5-142'); $b=PokeAdd(2,'Bench','me02-084'); $b->Controller=2; ceruledgeattack();
ceruledgechoose('p2Bench-0'); ceruledgeverify(GetZoneObject('p2Bench-0')->Damage===100 && GetZoneObject('p2Active-0')->Damage===0,'Cruel Arrow targets Bench');
ceruledgeboard('me02.5-142'); ceruledgeattack(); ceruledgechoose('p2Active-0'); ceruledgeverify(GetZoneObject('p2Active-0')->Damage===100,'Cruel Arrow targets Active');
ceruledgeboard('me02.5-142');
ceruledgeverify(!array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Flip the Script requires a prior knockout');
// Record a real opposing-turn knockout, then check the next turn's ability.
SetTurnNumber(5); SetTurnPlayer(2);
$victim=PokeAdd(1,'Bench','me02-019'); $victim->Controller=1; $victim->Damage=999;
PokeResolveKnockouts(); $GLOBALS['p2DecisionQueue']=[]; AddPrizeClaims(2,0); PokeCompact();
SetTurnNumber(6); SetTurnPlayer(1);
ceruledgeverify((bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Actual opposing-turn knockout enables Flip the Script');
PokeSetVar('knockedOutOnOpponentTurn:1',5);
$b=PokeAdd(1,'Bench','me02.5-142'); $b->Controller=1;
PokeStateImport(PokeStateExport());
PokeApplyAction(['player'=>1,'type'=>'ability','source'=>'p1Bench-0','index'=>0]);
ceruledgeverify(PokeCount(1,'Hand')===3,'Flip the Script draws three');
ceruledgeverify(!array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Flip the Script shared limit across copies');
PokeStateImport(PokeStateExport()); ceruledgeverify(!array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Shared limit survives serialization');
SetTurnNumber(8); ceruledgeverify(!array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Old knockout expires');
foreach ([1,2] as $first) {
    ceruledgeboard(); SetFirstPlayer($first); SetTurnNumber(1); AddPlayerTurns(1,1);
    PokeAdd(1,'Hand','mee-002'); ceruledgeplay('sv06-145');
    ceruledgeverify(PokeCount(1,'Hand')===5 && PokeCount(1,'Discard')===2,'Carmine draws five on first turn, first player '.$first);
    ceruledgeverify(GetSupporterUsed(1),'Carmine spends Supporter use');
    $second=PokeAdd(1,'Hand','sv06-145'); ceruledgeverify(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$second->mzIndex)),'Second Carmine is blocked');
}
ceruledgeboard(); SetFirstPlayer(1); AddPlayerTurns(1,1); $trainer=PokeAdd(1,'Hand','me01-119');
ceruledgeverify(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$trainer->mzIndex)),'Other Supporters remain blocked going first');
ceruledgeboard(); PokeAdd(1,'Hand','me04-086'); ceruledgeplay('sv06-145');
ceruledgeverify(count(array_filter(PokeObjects(1,'Discard'),fn($o)=>$o->CardID==='me04-086'))===1,'Hand discard does not return Nitro');
ceruledgeboard('me02.5-142'); GetZoneObject('p1Active-0')->Energy=['me04-086'];
PokeDiscardAttackEnergy(1,'p1Active-0'); PokeReturnAttackEnergy(1);
ceruledgeverify(PokeCount(1,'Hand')===0 && PokeCount(1,'Discard')===1,'Non-Fire attack discards do not return Nitro');
ceruledgeboard(); GetZoneObject('p1Active-0')->Energy=['me04-086']; PokeDiscardAttackEnergy(1,'p1Active-0');
ceruledgeverify(PokeCount(1,'Hand')===0 && PokeCount(1,'Discard')===1,'Nitro stays discarded during attack effects');
PokeStateImport(PokeStateExport()); PokeReturnAttackEnergy(1);
ceruledgeverify(PokeCount(1,'Hand')===1 && PokeCount(1,'Discard')===0,'Deferred Nitro return survives serialization');
PokeReturnAttackEnergy(1); ceruledgeverify(PokeCount(1,'Hand')===1,'Nitro returns exactly once');
ceruledgeboard(); $b=PokeAdd(1,'Bench','me02-019'); $b->Controller=1;
GetZoneObject('p1Active-0')->Energy=['me04-086','mee-002'];
PokeApplyAction(['player'=>1,'type'=>'retreat','target'=>'p1Bench-0','payment'=>[0,1]]);
ceruledgeverify(PokeCount(1,'Hand')===0 && PokeCount(1,'Discard')===2,'Retreat payment does not return Nitro');
ceruledgeboard(); $b=PokeAdd(1,'Bench','me02-019'); $b->Controller=1;
GetZoneObject('p1Active-0')->Energy=['me04-086']; GetZoneObject('p1Active-0')->Damage=999;
PokeResolveKnockouts();
ceruledgeverify(count(array_filter(PokeObjects(1,'Discard'),fn($o)=>$o->CardID==='me04-086'))===1 && PokeCount(1,'Hand')===0,'Knockout does not return Nitro');
foreach (['me04-080','me02-089'] as $id) ceruledgeverify(PokeCardImplemented($id),'Implemented '.$id);
foreach ([0,3,8] as $available) {
    ceruledgeboard();
    for ($i=0;$i<$available;++$i) PokeAdd(1,'Deck','mee-002');
    PokeAdd(1,'Deck','me04-086');
    ceruledgeplay('me02-089');
    ceruledgeverify(GetSupporterUsed(1),'Firebreather uses the turn Supporter');
    if ($available>0) {
        $options=PokeDecisionOptions(1);
        ceruledgeverify($options['min']===0 && $options['max']===7,'Firebreather may search up to seven');
        ceruledgeverify(count($options['choices'])===$available && count(array_unique(array_column($options['choices'],'card')))===1 && $options['choices'][0]['card']==='mee-002','Firebreather excludes Psychic and Nitro');
        ceruledgechoose(implode('&',array_column(array_slice($options['choices'],0,7),'value')));
    }
    ceruledgeverify(PokeCount(1,'Hand')===min(7,$available) && PokeDecisionOptions(1)===null,'Firebreather resolves with '.$available.' Basic Fire available');
    ceruledgeverify(count(array_filter(PokeVar('log',[]),fn($entry)=>$entry['event']==='reveal-search'))===min(7,$available),'Firebreather reveals selected Energy');
}
ceruledgeboard(); PokeAdd(1,'Deck','mee-002'); ceruledgeplay('me02-089'); ceruledgechoose('-');
ceruledgeverify(PokeCount(1,'Hand')===0,'Firebreather may find zero cards');
ceruledgeboard(); SetFirstPlayer(1); AddPlayerTurns(1,1);
$card=PokeAdd(1,'Hand','me02-089');
ceruledgeverify(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$card->mzIndex)),'Firebreather cannot be played going first on turn one');

ceruledgeboard(); ceruledgeplay('me04-080');
ceruledgeverify(PokeStadium()->CardID==='me04-080' && PokeCount(1,'Discard')===0,'Prism Tower enters the Stadium zone');
ceruledgeverify(PokeObservation(1)['stadium']['abilities']===[['name'=>'Prism Tower']],'Stadium observation exposes the correct action name');
$prismAction=['type'=>'ability','source'=>'Stadium-0','index'=>0,'player'=>1];
ceruledgeverify(!in_array($prismAction,PokeLegalActions(1),true),'Prism Tower requires two cards in hand');
PokeAdd(1,'Hand','me04-086');
ceruledgeverify(!in_array($prismAction,PokeLegalActions(1),true),'One card cannot pay Prism Tower');
PokeAdd(1,'Hand','mee-002'); PokeAdd(1,'Hand','mee-005');
ceruledgeverify(in_array($prismAction,PokeLegalActions(1),true),'Prism Tower may be used immediately after playing');
PokeApplyAction($prismAction); $options=PokeDecisionOptions(1);
ceruledgeverify($options['min']===2 && $options['max']===2 && count($options['choices'])===3,'Prism Tower requires exactly two discards');
ceruledgechoose($options['choices'][0]['value'].'&'.$options['choices'][1]['value']);
ceruledgeverify(PokeCount(1,'Hand')===2 && PokeCount(1,'Discard')===2 && PokeCount(1,'Deck')===19,'Prism Tower pays two cards and draws one');
ceruledgeverify(count(array_filter(PokeObjects(1,'Discard'),fn($o)=>$o->CardID==='me04-086'))===1,'Prism Tower does not return discarded Nitro');
PokeStateImport(PokeStateExport());
ceruledgeverify(!in_array($prismAction,PokeLegalActions(1),true),'Prism Tower once-per-turn limit survives serialization');
SetTurnPlayer(2); SetTurnNumber(7);
PokeAdd(2,'Hand','mee-002'); PokeAdd(2,'Hand','mee-005');
$opponentAction=['type'=>'ability','source'=>'Stadium-0','index'=>0,'player'=>2];
ceruledgeverify(in_array($opponentAction,PokeLegalActions(2),true),'Opponent may use a Stadium owned by player one');
PokeApplyAction($opponentAction); $options=PokeDecisionOptions(2);
PokeStateImport(PokeStateExport());
PokeApplyAction(['player'=>2,'type'=>'decision','value'=>implode('&',array_column($options['choices'],'value'))]);
ceruledgeverify(PokeCount(2,'Hand')===1 && PokeCount(2,'Discard')===2,'Opponent pays their own hand and draws from their own deck');
SetTurnPlayer(1); SetTurnNumber(8);
ceruledgeverify(in_array($prismAction,PokeLegalActions(1),true),'Prism Tower resets on the next turn');
$GLOBALS['p1Deck']=[];
ceruledgeverify(!in_array($prismAction,PokeLegalActions(1),true),'Prism Tower cannot be used with an empty deck');
PokeDiscardStadium();
ceruledgeverify(!in_array($prismAction,PokeLegalActions(1),true),'Discarded Prism Tower has no action');

echo "PASS $checks Ceruledge card checks\n";
