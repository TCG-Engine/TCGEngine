<?php
require __DIR__.'/dragapult-tests.php';
require_once dirname(__DIR__,2).'/PokeSim/Import/TCGdex.php';
$beforeChecks=$checks;
$raw=['id'=>'30th-092','name'=>'Umbreon ex','category'=>'Pokemon','set'=>['id'=>'30th'],'localId'=>'092',
    'stage'=>'Basic','evolveFrom'=>null,'regulationMark'=>null,'suffix'=>'EX','legal'=>['standard'=>false,'expanded'=>true],
    'attacks'=>[['name'=>'Lunatic Claw','damage'=>'100+']]];
$normalized=PokeTCGdexNormalize($raw);
verifyDragapult($normalized['stage']==='Stage1' && $normalized['evolveFrom']==='Eevee','Fresh imports correct evolution');
verifyDragapult($normalized['regulationMark']==='J' && $normalized['legal']['standard']===true && $normalized['suffix']==='ex','Fresh imports correct legality, mark and suffix');
$cached=$normalized; $cached['stage']='Basic'; $cached['evolveFrom']=null; $cached['legal']['standard']=false;
verifyDragapult(PokeTCGdexApplyCorrections($cached)===$normalized,'Cached imports receive the same corrections');
verifyDragapult($normalized['attacks']===$raw['attacks'],'Correction preserves printing-specific attack');
verifyDragapult(CardStage('30th-092')==='Stage1' && CardEvolveFrom('30th-092')==='Eevee','Generated dictionary has corrected evolution');
verifyDragapult(CardRegulationMark('30th-092')==='J' && CardLegal('30th-092')['standard']===true && CardSuffix('30th-092')==='ex','Generated dictionary has corrected legality and suffix');
dragapultBoard('sv06-135'); GetZoneObject('p1Active-0')->EnteredTurn=1;
$card=PokeAdd(1,'Hand','30th-092'); $ref=PokeRef(1,'Hand',$card->mzIndex);
verifyDragapult((bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='evolve' && $a['source']===$ref && $a['target']==='p1Active-0'),'Umbreon can legally evolve from Eevee');
verifyDragapult(!(bool)array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='bench' && $a['source']===$ref),'Umbreon cannot be played as a Basic');
PokeApplyAction(['type'=>'evolve','player'=>1,'source'=>$ref,'target'=>'p1Active-0']);
verifyDragapult(GetZoneObject('p1Active-0')->CardID==='30th-092' && GetZoneObject('p1Active-0')->Evolutions===['sv06-135'],'Normal evolution succeeds');
dragapultBoard('sv06-135'); PokeAdd(1,'Deck','30th-092'); dragapultAction('attack');
verifyDragapult(in_array('30th-092',array_column(PokeDecisionOptions(1)['choices'],'card'),true),'Ascension can search this Umbreon printing');
dragapultAnswer('p1Deck-20'); verifyDragapult(GetZoneObject('p1Active-0')->CardID==='30th-092','Ascension evolves to Umbreon');
verifyDragapult(PokeValidateDeck(PokeNamedDeck('umbreon-darkrai'))===[],'Saved deck remains valid');
echo 'PASS '.($checks-$beforeChecks)." Umbreon data checks\n";
