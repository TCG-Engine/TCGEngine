<?php
// Reserved browser fixtures; refuse to overwrite a directory without our marker.
require_once __DIR__.'/sea_rules_test.php';
foreach([2,4] as $seats){
 $reset($seats);$owner=$seats===2?1:4;$gameName='9802650'.$seats;$GLOBALS['gameName']=$gameName;$GLOBALS['playerID']=$owner;
 SetTurnPlayer($owner);SetPriorityPlayer($owner);GetHero($owner)[0]->CardID='gravy_bones';
 AddArena($owner,CardID:'gold',Owner:$owner,Controller:$owner);
 AddHand($owner,CardID:'fools_gold_yellow',Owner:$owner,Controller:$owner);
 AddHand($owner,CardID:'zap_blue',Owner:$owner,Controller:$owner);
 AddDeck($owner,CardID:'barnacle_yellow',Owner:$owner,Controller:$owner);
 AddGraveyard($owner,CardID:'wailer_humperdinck_yellow',Owner:$owner,Controller:$owner,FaceDown:1);
 AddGraveyard($owner,CardID:'oysten_heart_of_gold_yellow',Owner:$owner,Controller:$owner);
 $path=__DIR__.'/../../FaBSim/Games/'.$gameName;if(is_dir($path)&&!is_file($path.'/.sea-test-fixture'))throw new RuntimeException('Fixture directory is already in use');if(!is_dir($path))mkdir($path,0777,true);file_put_contents($path.'/.sea-test-fixture','High Seas UI test');
 $currentPlayer=$owner;$updateNumber=1;WriteGamestate(__DIR__.'/../../FaBSim/');
 echo "Browser fixture $gameName, owner seat $owner.\n";
}
