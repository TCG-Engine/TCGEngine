<?php
// Deterministic opening sequence: run with php DevTools/FaB/bot_benchmark.php.
// Reports server logic only, excluding HTTP, polling, and browser animations.
require_once __DIR__.'/upf_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$reset();
$deck=FaBFaiBotDeck();
foreach([1,2,3,4] as $p){
    $hero=&GetHero($p);$hero=[];AddHero($p,CardID:'fai',Owner:$p,Controller:$p,Status:2);
    foreach($deck['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p,Status:2);
    foreach($deck['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p,Status:2);
    foreach($deck['mainDeck'] as $id)AddDeck($p,CardID:$id);
    FaBFaiSetup($p,true);
}
$s=FaBGetState();$s['botProfiles']=[1=>'fai',2=>'fai',3=>'fai',4=>'fai'];FaBSetState($s);
foreach(['state'=>fn()=>FaBGetState(),'heroActive'=>fn()=>FaBWTRHeroActive(1),'type'=>fn()=>EffectiveCardType(GetHand(1)[0]),'canPlay'=>fn()=>CanPlayCard(1,'p1Hand-0')] as $label=>$fn){
    $t=microtime(true);for($i=0;$i<100;$i++)$fn();printf("%s: %.3f ms/call\n",$label,(microtime(true)-$t)*10);
}
for($i=0;$i<20;$i++){
    $window=FaBGetState()['window'];$t=microtime(true);$r=ProcessBotControllerStep(1,'FaBSim');
    printf("step %d %s: %.1f ms\n",$i,$window,(microtime(true)-$t)*1000);
    if(empty($r['applied']))break;
}
