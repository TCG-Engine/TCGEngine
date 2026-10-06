<?php
require __DIR__.'/relicanth-draw-tests.php';
$checks=0;
$key='relicanth-v3-meta-tune';
$deck=PokeNamedDeck($key);
$counts=static function(array $deck): array {
    $out=[];foreach($deck as $card)$out[$card['id']]=($out[$card['id']]??0)+$card['count'];
    ksort($out);return $out;
};
$base=$counts(PokeNamedDeck('relicanth-v2-draw'));
$expected=$base;unset($expected['me03-076']);
$expected['sv10.5w-080']=2;$expected['sv10.5w-082']=1;$expected['me01-114']=2;ksort($expected);
drawCheck($counts($deck)===$expected,'Only the four requested quantity changes');
drawCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'All 60 cards supported');
drawCheck($base['me03-076']===2&&$base['sv10.5w-082']===2&&$base['me01-114']===1,'V2 quantities preserved');
drawCheck(PokeDeckName($key)==='Relicanth-v3-meta-tune','Requested display name');
drawCheck(PokeDetectDeck(array_reverse($deck))===$key,'Detect reordered import');
$split=$deck;$split[0]['count']=2;$split[]=['id'=>'me05-017','count'=>2];
drawCheck(PokeDetectDeck($split)===$key,'Detect import with duplicate rows');
$variant=$deck;$variant[0]['count']=3;
drawCheck(PokeDetectDeck($variant)==='relicanth-v2-draw','Other experiments retain draw policy classification');
drawCheck(PokeOpeningProfile($key)['targets']===PokeOpeningProfile('relicanth-v2-draw')['targets'],'Fossil opening targets retained');
foreach([1,2] as $seat){
    drawBoard($seat);PokeSetVar('deckKey:'.$seat,$key);
    GetZoneObject(PokeFirstRef(3-$seat,'Active'))->CardID='me02-084';
    PokeAdd($seat,'Hand','sv06-158');PokeAdd($seat,'Hand','sv10.5w-080');
    $view=PokeObservation($seat);$action=PokeBotChoose($view);
    drawCheck($action===PokeRelicanthMetaTuneChoose($view),'Dispatch separate meta-tune bot');
    drawCheck(($action['type']??'')==='trainer'&&GetZoneObject($action['source'])->CardID==='sv10.5w-080','Meta-tune prioritizes Bangle against ex');
    PokeApplyAction($action);drawDrain();
    drawCheck(GetZoneObject(PokeFirstRef($seat,'Active'))->Tool==='sv10.5w-080','Bangle equips after serialized decision');
}
foreach(['dhelmise-v2','brisbane-lopunny'] as $opponent)foreach([1,2] as $seat)foreach([1,2] as $first){
    $game=PokeSimulateGame(42,$first,1500,$seat===1?$key:$opponent,$seat===2?$key:$opponent);
    drawCheck($game['status']==='complete','V3 completes '.$opponent.' seat '.$seat.' first '.$first.': '.$game['reason']);
    drawCheck(PokeVar('deckKey:'.$seat)===$key,'Runtime selects v3 policy and stats identity');
}
foreach(['menu.php'=>4,'index.php'=>2] as $file=>$count){
    drawCheck(substr_count(file_get_contents(dirname(__DIR__,2).'/PokeSim/'.$file),'value="'.$key.'"')===$count,'V3 in all '.$file.' selectors');
}
echo "$checks Relicanth meta-tune checks passed\n";
