<?php
require_once __DIR__.'/arc_test.php';
$failures=[];
foreach([2,4] as $p)foreach(['red'=>3,'yellow'=>2,'blue'=>1] as $color=>$limit)foreach([0,1,4] as $existing)foreach([false,true] as $decline)foreach([false,true] as $reverse){
    $arcReset($p);SetTurnPlayer($p);SetPriorityPlayer($p);
    $arena=&GetArena($p);$arena=[];
    FaBWTRCreateArena(1,'ash');$opposing=FaBUPRUIDs(FaBUPRAsh(1));
    for($i=0;$i<$existing;++$i){FaBWTRCreateArena($p,'ash');FaBWTRCreateArena($p,'aether_ashwing');}
    $id='rake_the_embers_'.$color;$card=AddGraveyard($p,CardID:$id);
    FaBRunSourceMacro('ResolveCard',$p,$id,['mzID'=>FaBFindUID(intval($card->UniqueID))['mzID']]);
    $d=GetDecisionQueue($p)[0]??null;
    $check($d!==null&&$d->Type==='MZMULTICHOOSE','Rake must offer a single multiselect.');
    if(!$d||$d->Type!=='MZMULTICHOOSE')continue;
    [$min,$max,$refs]=explode('|',$d->Param,3);
    $check(intval($min)===0&&intval($max)===min($limit,$existing+1),'Wrong Rake selection limits.');
    $all=FaBUPRUIDs($refs);
    $choices=explode('&',$refs);if($reverse)$choices=array_reverse($choices);
    $selected=$decline?[]:array_slice($choices,0,intval($max));
    $selectedUIDs=FaBUPRUIDs(implode('&',$selected));
    $answer($p,$selected?implode('&',$selected):'-');
    $check(!FaBHasPendingDecision(),'Rake left a second prompt pending.');
    $check(count(FaBMONArena($p,'aether_ashwing'))===$existing+count($selected),'Rake transformed the wrong number of Ash.');
    $remaining=FaBUPRUIDs(FaBUPRAsh($p));
    $expected=array_values(array_diff($all,$selectedUIDs));sort($remaining);sort($expected);
    $check($remaining===$expected,'Rake transformed the wrong Ash after zone indices shifted.');
    $check(FaBUPRUIDs(FaBUPRAsh(1))===$opposing,'Rake affected opposing Ash.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "Rake multiselect passed for all colors, optional selections, and Ash counts in duels and UPF.\n";
