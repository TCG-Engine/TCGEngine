<?php
require_once __DIR__.'/mpg_rules_test.php';
foreach([2,4] as $seats){
 $reset($seats);$owner=$seats;$gameName='9801300'.$seats;$GLOBALS['gameName']=$gameName;$GLOBALS['playerID']=$owner;
 SetTurnPlayer(1);SetPriorityPlayer($owner);GetHero($owner)[0]->CardID=$seats===4?'valda_brightaxe':'valda_seismic_impact';
 GetHero(1)[0]->CardID='bravo';$a=$attack(1,'aftershock_red',$owner);
 $s=FaBGetState();$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);
 AddHand($owner,CardID:'clash_of_bravado_yellow',Owner:$owner,Controller:$owner);
 AddHand($owner,CardID:'zap_blue',Owner:$owner,Controller:$owner);
 AddDeck($owner,CardID:'rubble_raiser_red',Owner:$owner,Controller:$owner);AddDeck(1,CardID:'zap_blue',Owner:1,Controller:1);
 AddArena(1,CardID:'seismic_surge',Owner:1,Controller:1);
 AddArena($owner,CardID:'geyser_of_seismic_stirrings_yellow',Owner:$owner,Controller:$owner,Counters:['ENERGY'=>2]);
 AddArena($owner,CardID:'frostbite',Owner:$owner,Controller:$owner,Counters:['MPG_SLOT'=>'Head']);
 $path=__DIR__.'/../../FaBSim/Games/'.$gameName;if(is_dir($path)&&!is_file($path.'/.mpg-test-fixture'))throw new RuntimeException('Fixture directory already in use');if(!is_dir($path))mkdir($path,0777,true);file_put_contents($path.'/.mpg-test-fixture','Mastery Pack Guardian UI test');
 $currentPlayer=$owner;$updateNumber=1;WriteGamestate(__DIR__.'/../../FaBSim/');echo "MPG browser fixture $gameName, defender $owner.\n";
}
