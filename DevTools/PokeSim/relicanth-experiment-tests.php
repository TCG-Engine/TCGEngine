<?php
require __DIR__.'/relicanth-packages.php';
$checks=0;
function experimentCheck(bool $ok,string $why): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($why);}
$base=PokeNamedDeck('relicanth-v2-draw');
experimentCheck(CardName('experiment-blank-0')===null&&!PokeCardImplemented('experiment-blank-0'),'Synthetic cards are absent from normal runtime');
$slots=[];foreach($base as $e)for($i=0;$i<$e['count'];++$i)$slots[]=$e['id'];
foreach($base as $e){
    $deck=relicanthExperimentDeck($base,[$e['id']=>-1]);
    $changed=[];foreach($deck as $i=>$c)if($c['id']!==$slots[$i])$changed[]=$i;
    experimentCheck(count($changed)===1&&$slots[$changed[0]]===$e['id'],'One-copy cut preserves every other shuffle slot');
    experimentCheck(count($deck)===60&&!PokeValidateDeck($deck),'Ablated list remains sixty cards');
    InitializeGamestate();$blank=PokeAdd(1,'Hand',$deck[$changed[0]]['id']);
    experimentCheck(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$blank->mzIndex)),'Blank cannot be played');
    experimentCheck(PokeCandidates(1,'Hand','nonRuleBox')===''&&PokeCandidates(1,'Hand','basicEnergy')==='','Blank cannot be searched as Pokemon or Energy');
}
$deck=relicanthExperimentDeck($base,['sv06-158'=>-3,'me01-125'=>2,'me05-062'=>1]);
experimentCheck(count(array_filter($deck,fn($e)=>$e['id']==='me01-125'))===2,'Package quantities are exact');
foreach([1,2,3,4] as $cut){
    $deck=relicanthExperimentDeck($base,['mee-003'=>-$cut]);$ids=array_column($deck,'id');
    experimentCheck(count(array_filter($ids,fn($id)=>$id==='mee-003'))===7-$cut,'Water reduction is exact');
    experimentCheck(count(array_filter($ids,fn($id)=>$id==='sv06-167'))===1,'Legacy is retained during Water ablations');
    experimentCheck(count(array_filter($ids,fn($id)=>str_starts_with($id,'experiment-blank-'))) ===$cut,'Each removed Water becomes its own blank');
    experimentCheck(!PokeValidateDeck($deck)&&count($ids)===60,'Multi-Water ablation remains valid and sixty cards');
}
foreach([1,2] as $cut){
    $ids=array_column(relicanthExperimentDeck($base,['sv10.5w-082'=>-$cut]),'id');
    experimentCheck(count(array_filter($ids,fn($id)=>$id==='sv10.5w-082'))===2-$cut,'Retrieval reduction is exact');
    experimentCheck(count(array_filter($ids,fn($id)=>$id==='mee-003'))===7,'Retrieval ablation retains all Water');
    experimentCheck(count(array_filter($ids,fn($id)=>$id==='me02.5-196'))===4,'Retrieval ablation retains all Stretcher');
    experimentCheck(count(array_filter($ids,fn($id)=>str_starts_with($id,'experiment-blank-'))) ===$cut,'Retrieval copies become blanks');
}
try{relicanthExperimentDeck($base,['me05-017'=>-5]);experimentCheck(false,'Excess cut rejected');}catch(InvalidArgumentException $e){experimentCheck(true,'Excess cut rejected');}
try{relicanthExperimentDeck($base,['me05-062'=>1]);experimentCheck(false,'Excess addition rejected');}catch(InvalidArgumentException $e){experimentCheck(true,'Excess addition rejected');}
$plain=relicanthExperimentDeck($base,[]);
foreach([1,2] as $first){
    $x=PokeSimulateGame(98765,$first,1500,'relicanth-v2-draw','dhelmise-v2',$base);
    $y=PokeSimulateGame(98765,$first,1500,'relicanth-v2-draw','dhelmise-v2',$plain);
    foreach(['x','y'] as $var)foreach(${$var}['openingStats'] as &$opening)unset($opening['deckHash']);unset($opening);
    experimentCheck($x===$y&&$x['status']==='complete','Expanded baseline reproduces identical game for starting order '.$first);
}
echo "$checks experiment checks passed\n";
